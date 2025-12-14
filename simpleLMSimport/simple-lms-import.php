<?php
/**
 * Plugin Name: SimpleLMS Import
 * Description: Temporary plugin to import courses from Course Manager to SimpleLMS.
 * Version: 1.0
 * Author: Dawid Milczarek
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SimpleLMS_Import {

    /**
     * Old course manager table name.
     */
    private $old_table;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->old_table = $wpdb->prefix . 'dmeight_cm_courses';

        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
        add_action( 'admin_post_simple_lms_import', array( $this, 'handle_import' ) );
    }

    /**
     * Add admin menu under simpleLMS.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'simple-lms',
            __( 'Import Courses', 'simple-lms' ),
            __( 'Import', 'simple-lms' ),
            'manage_options',
            'simple-lms-import',
            array( $this, 'render_import_page' )
        );
    }

    /**
     * Render the import page.
     */
    public function render_import_page() {
        // Check if simpleLMS is active.
        if ( ! post_type_exists( 'simple_lms_course' ) ) {
            echo '<div class="wrap"><h1>' . esc_html__( 'Import Courses', 'simple-lms' ) . '</h1>';
            echo '<div class="notice notice-error"><p>' . esc_html__( 'SimpleLMS plugin must be active to use this importer.', 'simple-lms' ) . '</p></div>';
            echo '</div>';
            return;
        }

        // Check if old table exists.
        if ( ! $this->old_table_exists() ) {
            echo '<div class="wrap"><h1>' . esc_html__( 'Import Courses', 'simple-lms' ) . '</h1>';
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Course Manager database table not found.', 'simple-lms' ) . '</p></div>';
            echo '</div>';
            return;
        }

        include plugin_dir_path( __FILE__ ) . 'admin/views/import-page.php';
    }

    /**
     * Check if old table exists.
     */
    private function old_table_exists() {
        global $wpdb;
        $result = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->old_table ) );
        return $result === $this->old_table;
    }

    /**
     * Get all courses from old table.
     */
    public function get_old_courses() {
        global $wpdb;
        $courses = $wpdb->get_results( "SELECT * FROM {$this->old_table} ORDER BY original_date DESC", ARRAY_A );

        foreach ( $courses as &$course ) {
            $course['video_links'] = ! empty( $course['video_links'] ) ? json_decode( $course['video_links'], true ) : array();
            $course['training_material_links'] = ! empty( $course['training_material_links'] ) ? json_decode( $course['training_material_links'], true ) : array();
        }

        return $courses;
    }

    /**
     * Get available statuses from simpleLMS.
     */
    public function get_available_statuses() {
        $terms = get_terms( array(
            'taxonomy'   => 'simple_lms_status',
            'hide_empty' => false,
        ) );

        if ( is_wp_error( $terms ) ) {
            return array();
        }

        return $terms;
    }

    /**
     * Get available membership plans from WooCommerce Memberships.
     *
     * @return array
     */
    public function get_membership_plans() {
        if ( ! function_exists( 'wc_memberships' ) ) {
            return array();
        }

        return get_posts(
            array(
                'post_type'      => 'wc_membership_plan',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            )
        );
    }

    /**
     * Deduplicate courses by title.
     * When multiple courses have the same title, keep the one with more videos/materials.
     * If counts are equal, keep the first one.
     *
     * @param array $course_ids Array of course IDs to deduplicate.
     * @return array Deduplicated array of course IDs.
     */
    private function deduplicate_courses( $course_ids ) {
        $courses_by_title = array();

        // Group courses by title.
        foreach ( $course_ids as $course_id ) {
            $course = $this->get_course_by_id( $course_id );
            if ( ! $course ) {
                continue;
            }

            $title = strtolower( trim( $course['title'] ) );
            $video_count = is_array( $course['video_links'] ) ? count( $course['video_links'] ) : 0;
            $material_count = is_array( $course['training_material_links'] ) ? count( $course['training_material_links'] ) : 0;
            $total_count = $video_count + $material_count;

            if ( ! isset( $courses_by_title[ $title ] ) ) {
                // First course with this title.
                $courses_by_title[ $title ] = array(
                    'id'    => $course_id,
                    'count' => $total_count,
                );
            } else {
                // Duplicate found - keep the one with more videos/materials.
                if ( $total_count > $courses_by_title[ $title ]['count'] ) {
                    $courses_by_title[ $title ] = array(
                        'id'    => $course_id,
                        'count' => $total_count,
                    );
                }
                // If counts are equal, keep the first one (do nothing).
            }
        }

        // Extract deduplicated IDs.
        $deduplicated_ids = array();
        foreach ( $courses_by_title as $data ) {
            $deduplicated_ids[] = $data['id'];
        }

        return $deduplicated_ids;
    }

    /**
     * Handle the import action.
     */
    public function handle_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'simple-lms' ) );
        }

        check_admin_referer( 'simple_lms_import_action' );

        $status_id = isset( $_POST['import_status'] ) ? intval( $_POST['import_status'] ) : 0;
        $course_ids = isset( $_POST['course_ids'] ) ? array_map( 'intval', $_POST['course_ids'] ) : array();
        $membership_ids = isset( $_POST['import_memberships'] ) ? array_map( 'intval', $_POST['import_memberships'] ) : array();

        if ( ! $status_id ) {
            wp_redirect( admin_url( 'admin.php?page=simple-lms-import&error=no_status' ) );
            exit;
        }

        if ( empty( $course_ids ) ) {
            wp_redirect( admin_url( 'admin.php?page=simple-lms-import&error=no_courses' ) );
            exit;
        }

        // Deduplicate courses by title - keep the one with more videos/materials.
        $original_count = count( $course_ids );
        $course_ids = $this->deduplicate_courses( $course_ids );
        $skipped = $original_count - count( $course_ids );

        $imported = 0;
        $errors = 0;

        foreach ( $course_ids as $course_id ) {
            $course = $this->get_course_by_id( $course_id );
            if ( $course ) {
                $result = $this->import_single_course( $course, $status_id, $membership_ids );
                if ( $result ) {
                    $imported++;
                } else {
                    $errors++;
                }
            } else {
                $errors++;
            }
        }

        wp_redirect( admin_url( 'admin.php?page=simple-lms-import&imported=' . $imported . '&errors=' . $errors . '&skipped=' . $skipped ) );
        exit;
    }

    /**
     * Get a single course by ID.
     *
     * @param int $id Course ID.
     * @return array|null Course data or null if not found.
     */
    public function get_course_by_id( $id ) {
        global $wpdb;
        $course = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->old_table} WHERE id = %d", $id ), ARRAY_A );

        if ( $course ) {
            $course['video_links'] = ! empty( $course['video_links'] ) ? json_decode( $course['video_links'], true ) : array();
            $course['training_material_links'] = ! empty( $course['training_material_links'] ) ? json_decode( $course['training_material_links'], true ) : array();
        }

        return $course;
    }

    /**
     * Import a single course.
     *
     * @param array $course Old course data.
     * @param int   $status_id Status term ID to assign.
     * @param array $membership_ids Membership plan IDs for access restriction.
     * @return int|false Post ID on success, false on failure.
     */
    private function import_single_course( $course, $status_id, $membership_ids = array() ) {
        // Create post.
        $post_data = array(
            'post_title'  => sanitize_text_field( $course['title'] ),
            'post_type'   => 'simple_lms_course',
            'post_status' => 'publish',
        );

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            return false;
        }

        // Set date.
        if ( ! empty( $course['original_date'] ) ) {
            update_post_meta( $post_id, '_simple_lms_date', sanitize_text_field( $course['original_date'] ) );
        }

        // Parse and set time (format: "10:00-15:00" or "10:00 - 15:00").
        if ( ! empty( $course['original_time'] ) ) {
            $time_parts = preg_split( '/\s*-\s*/', $course['original_time'] );
            if ( count( $time_parts ) >= 2 ) {
                update_post_meta( $post_id, '_simple_lms_time_start', sanitize_text_field( trim( $time_parts[0] ) ) );
                update_post_meta( $post_id, '_simple_lms_time_end', sanitize_text_field( trim( $time_parts[1] ) ) );
            }
        }

        // Set duration.
        if ( ! empty( $course['duration'] ) ) {
            update_post_meta( $post_id, '_simple_lms_duration', sanitize_text_field( $course['duration'] ) );
        }

        // Map videos: old format {title, url} -> new format {title, vimeo_url}.
        if ( ! empty( $course['video_links'] ) && is_array( $course['video_links'] ) ) {
            $videos = array();
            foreach ( $course['video_links'] as $video ) {
                if ( ! empty( $video['url'] ) ) {
                    $videos[] = array(
                        'title'     => ! empty( $video['title'] ) ? sanitize_text_field( $video['title'] ) : 'Recording',
                        'vimeo_url' => esc_url_raw( $video['url'] ),
                    );
                }
            }
            if ( ! empty( $videos ) ) {
                update_post_meta( $post_id, '_simple_lms_videos', $videos );
            }
        }

        // Map materials: old format {title, url} -> new format {label, url}.
        if ( ! empty( $course['training_material_links'] ) && is_array( $course['training_material_links'] ) ) {
            $materials = array();
            foreach ( $course['training_material_links'] as $material ) {
                if ( ! empty( $material['url'] ) ) {
                    $materials[] = array(
                        'label' => ! empty( $material['title'] ) ? sanitize_text_field( $material['title'] ) : 'Download',
                        'url'   => esc_url_raw( $material['url'] ),
                    );
                }
            }
            if ( ! empty( $materials ) ) {
                update_post_meta( $post_id, '_simple_lms_materials', $materials );
            }
        }

        // Handle lecturer taxonomy.
        if ( ! empty( $course['trainer_name'] ) ) {
            $lecturer_name = sanitize_text_field( $course['trainer_name'] );
            $term = term_exists( $lecturer_name, 'simple_lms_lecturer' );

            if ( ! $term ) {
                $term = wp_insert_term( $lecturer_name, 'simple_lms_lecturer' );
            }

            if ( ! is_wp_error( $term ) ) {
                $term_id = is_array( $term ) ? $term['term_id'] : $term;
                wp_set_object_terms( $post_id, intval( $term_id ), 'simple_lms_lecturer' );
            }
        }

        // Assign status.
        wp_set_object_terms( $post_id, $status_id, 'simple_lms_status' );

        // Enable certificate by default.
        update_post_meta( $post_id, '_simple_lms_certificate_enabled', '1' );

        // Set membership access restrictions.
        if ( ! empty( $membership_ids ) ) {
            update_post_meta( $post_id, '_simple_lms_access_memberships', $membership_ids );
        }

        return $post_id;
    }
}

new SimpleLMS_Import();
