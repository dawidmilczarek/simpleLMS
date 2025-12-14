<?php
/**
 * Admin functionality.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LMS_Admin class.
 */
class LMS_Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'handle_course_form' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 100 );
        add_action( 'wp_ajax_simple_lms_save_shortcode_preset', array( $this, 'ajax_save_shortcode_preset' ) );
        add_action( 'wp_ajax_simple_lms_delete_shortcode_preset', array( $this, 'ajax_delete_shortcode_preset' ) );
        add_action( 'wp_ajax_simple_lms_delete_course', array( $this, 'ajax_delete_course' ) );
        add_action( 'wp_ajax_simple_lms_reset_default_template', array( $this, 'ajax_reset_default_template' ) );
        add_filter( 'parent_file', array( $this, 'fix_parent_menu' ) );
        add_filter( 'submenu_file', array( $this, 'fix_submenu_file' ) );
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        // Main menu - points to courses list.
        add_menu_page(
            __( 'simpleLMS', 'simple-lms' ),
            __( 'simpleLMS', 'simple-lms' ),
            'edit_posts',
            'simple-lms',
            array( $this, 'render_courses_page' ),
            'dashicons-welcome-learn-more',
            30
        );

        // Courses submenu (same as main - will be renamed).
        add_submenu_page(
            'simple-lms',
            __( 'Courses', 'simple-lms' ),
            __( 'Courses', 'simple-lms' ),
            'edit_posts',
            'simple-lms',
            array( $this, 'render_courses_page' )
        );

        // Add New Course submenu.
        add_submenu_page(
            'simple-lms',
            __( 'Add New Course', 'simple-lms' ),
            __( 'Add New Course', 'simple-lms' ),
            'edit_posts',
            'simple-lms-add',
            array( $this, 'render_course_form' )
        );

        // Settings submenu.
        add_submenu_page(
            'simple-lms',
            __( 'Settings', 'simple-lms' ),
            __( 'Settings', 'simple-lms' ),
            'manage_options',
            'simple-lms-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Add admin bar menu items.
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
     */
    public function add_admin_bar_menu( $wp_admin_bar ) {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        // Main LMS menu.
        $wp_admin_bar->add_node(
            array(
                'id'    => 'simple-lms',
                'title' => __( 'LMS', 'simple-lms' ),
                'href'  => admin_url( 'admin.php?page=simple-lms' ),
            )
        );

        // Add New Course.
        $wp_admin_bar->add_node(
            array(
                'id'     => 'simple-lms-add',
                'parent' => 'simple-lms',
                'title'  => __( 'Add New Course', 'simple-lms' ),
                'href'   => admin_url( 'admin.php?page=simple-lms-add' ),
            )
        );

        // All Courses.
        $wp_admin_bar->add_node(
            array(
                'id'     => 'simple-lms-courses',
                'parent' => 'simple-lms',
                'title'  => __( 'All Courses', 'simple-lms' ),
                'href'   => admin_url( 'admin.php?page=simple-lms' ),
            )
        );

        // Edit current course (only on single course page).
        if ( is_singular( 'simple_lms_course' ) ) {
            $wp_admin_bar->add_node(
                array(
                    'id'     => 'simple-lms-edit',
                    'parent' => 'simple-lms',
                    'title'  => __( 'Edit This Course', 'simple-lms' ),
                    'href'   => admin_url( 'admin.php?page=simple-lms-add&course_id=' . get_the_ID() ),
                )
            );
        }
    }

    /**
     * Fix parent menu highlighting.
     *
     * @param string $parent_file Parent file.
     * @return string
     */
    public function fix_parent_menu( $parent_file ) {
        global $current_screen;

        if ( isset( $current_screen->post_type ) && 'simple_lms_course' === $current_screen->post_type ) {
            return 'simple-lms';
        }

        if ( isset( $current_screen->taxonomy ) ) {
            $lms_taxonomies = array( 'simple_lms_category', 'simple_lms_tag', 'simple_lms_status' );
            if ( in_array( $current_screen->taxonomy, $lms_taxonomies, true ) ) {
                return 'simple-lms';
            }
        }

        return $parent_file;
    }

    /**
     * Fix submenu highlighting.
     *
     * @param string $submenu_file Submenu file.
     * @return string
     */
    public function fix_submenu_file( $submenu_file ) {
        global $current_screen;

        if ( isset( $current_screen->taxonomy ) ) {
            $lms_taxonomies = array( 'simple_lms_category', 'simple_lms_tag', 'simple_lms_status' );
            if ( in_array( $current_screen->taxonomy, $lms_taxonomies, true ) ) {
                return 'simple-lms-settings';
            }
        }

        return $submenu_file;
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting( 'simple_lms_settings_group', 'simple_lms_settings', array( $this, 'sanitize_settings' ) );
        register_setting( 'simple_lms_templates_group', 'simple_lms_default_template', 'wp_kses_post' );
        register_setting( 'simple_lms_templates_group', 'simple_lms_status_templates', array( $this, 'sanitize_status_templates' ) );
    }

    /**
     * Sanitize general settings.
     *
     * @param array $input Input data.
     * @return array
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        $sanitized['redirect_url']           = isset( $input['redirect_url'] ) ? esc_url_raw( $input['redirect_url'] ) : '/sklep/';
        $sanitized['date_format']            = isset( $input['date_format'] ) ? sanitize_text_field( $input['date_format'] ) : 'd.m.Y';
        $sanitized['default_material_label'] = isset( $input['default_material_label'] ) ? sanitize_text_field( $input['default_material_label'] ) : '';
        $sanitized['default_video_title']    = isset( $input['default_video_title'] ) ? sanitize_text_field( $input['default_video_title'] ) : '';
        $sanitized['default_lecturer']       = isset( $input['default_lecturer'] ) ? sanitize_text_field( $input['default_lecturer'] ) : '';
        $sanitized['default_time_start']     = isset( $input['default_time_start'] ) ? sanitize_text_field( $input['default_time_start'] ) : '';
        $sanitized['default_time_end']       = isset( $input['default_time_end'] ) ? sanitize_text_field( $input['default_time_end'] ) : '';
        $sanitized['default_duration']       = isset( $input['default_duration'] ) ? sanitize_text_field( $input['default_duration'] ) : '';
        $sanitized['default_status']         = isset( $input['default_status'] ) ? sanitize_text_field( $input['default_status'] ) : '';

        return $sanitized;
    }

    /**
     * Sanitize status templates.
     *
     * @param array $input Input data.
     * @return array
     */
    public function sanitize_status_templates( $input ) {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $sanitized = array();
        foreach ( $input as $status_id => $template ) {
            $sanitized[ absint( $status_id ) ] = wp_kses_post( $template );
        }

        return $sanitized;
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_scripts( $hook ) {
        // Check if we're on a simpleLMS admin page.
        $lms_pages = array(
            'toplevel_page_simple-lms',
            'simplelms_page_simple-lms-add',
            'simplelms_page_simple-lms-settings',
        );

        $is_lms_page = in_array( $hook, $lms_pages, true );

        // Also check for edit page (when editing a course).
        if ( isset( $_GET['page'] ) && 'simple-lms-add' === $_GET['page'] ) {
            $is_lms_page = true;
        }

        if ( $is_lms_page ) {
            wp_enqueue_style(
                'simple-lms-admin',
                SIMPLE_LMS_PLUGIN_URL . 'admin/css/admin.css',
                array(),
                SIMPLE_LMS_VERSION
            );

            wp_enqueue_script(
                'simple-lms-admin',
                SIMPLE_LMS_PLUGIN_URL . 'admin/js/admin.js',
                array( 'jquery', 'jquery-ui-sortable' ),
                SIMPLE_LMS_VERSION,
                true
            );

            wp_localize_script(
                'simple-lms-admin',
                'simpleLMS',
                array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'simple_lms_admin' ),
                    'i18n'    => array(
                        'confirmDelete'        => __( 'Are you sure you want to delete this preset?', 'simple-lms' ),
                        'confirmDeleteCourse'  => __( 'Are you sure you want to delete this course?', 'simple-lms' ),
                        'confirmResetTemplate' => __( 'Are you sure you want to reset the default template? Your current template will be lost.', 'simple-lms' ),
                        'saving'               => __( 'Saving...', 'simple-lms' ),
                        'saved'                => __( 'Saved!', 'simple-lms' ),
                        'error'                => __( 'Error saving. Please try again.', 'simple-lms' ),
                        'templateReset'        => __( 'Template has been reset.', 'simple-lms' ),
                    ),
                )
            );

            // Enqueue WordPress editor for course content.
            if ( 'simplelms_page_simple-lms-add' === $hook || ( isset( $_GET['page'] ) && 'simple-lms-add' === $_GET['page'] ) ) {
                wp_enqueue_editor();
                wp_enqueue_media();
            }
        }

        // Enqueue code editor for templates tab.
        if ( in_array( $hook, $lms_pages, true ) && isset( $_GET['tab'] ) && 'templates' === $_GET['tab'] ) {
            wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
        }
    }

    /**
     * Render courses list page.
     */
    public function render_courses_page() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        include SIMPLE_LMS_PLUGIN_DIR . 'admin/views/courses-list.php';
    }

    /**
     * Render course form page.
     */
    public function render_course_form() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        include SIMPLE_LMS_PLUGIN_DIR . 'admin/views/course-form.php';
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        include SIMPLE_LMS_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Handle course form submission.
     */
    public function handle_course_form() {
        if ( ! isset( $_POST['simple_lms_course_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['simple_lms_course_nonce'] ) ), 'simple_lms_save_course' ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $post_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
        $is_new  = 0 === $post_id;

        // Prepare post data.
        $post_data = array(
            'post_title'   => isset( $_POST['course_title'] ) ? sanitize_text_field( wp_unslash( $_POST['course_title'] ) ) : '',
            'post_content' => isset( $_POST['course_content'] ) ? wp_kses_post( wp_unslash( $_POST['course_content'] ) ) : '',
            'post_status'  => isset( $_POST['course_status'] ) && 'draft' === $_POST['course_status'] ? 'draft' : 'publish',
            'post_type'    => 'simple_lms_course',
        );

        if ( $is_new ) {
            $post_id = wp_insert_post( $post_data );
        } else {
            $post_data['ID'] = $post_id;
            wp_update_post( $post_data );
        }

        if ( is_wp_error( $post_id ) ) {
            wp_die( esc_html( $post_id->get_error_message() ) );
        }

        // Save meta fields.
        $this->save_course_meta( $post_id );

        // Save taxonomies.
        $this->save_course_taxonomies( $post_id );

        // Redirect to edit page with success message.
        wp_safe_redirect( admin_url( 'admin.php?page=simple-lms-add&course_id=' . $post_id . '&message=saved' ) );
        exit;
    }

    /**
     * Save course meta fields.
     *
     * @param int $post_id Post ID.
     */
    private function save_course_meta( $post_id ) {
        // Course details.
        if ( isset( $_POST['simple_lms_date'] ) ) {
            update_post_meta( $post_id, '_simple_lms_date', sanitize_text_field( wp_unslash( $_POST['simple_lms_date'] ) ) );
        }

        if ( isset( $_POST['simple_lms_time_start'] ) ) {
            update_post_meta( $post_id, '_simple_lms_time_start', sanitize_text_field( wp_unslash( $_POST['simple_lms_time_start'] ) ) );
        }

        if ( isset( $_POST['simple_lms_time_end'] ) ) {
            update_post_meta( $post_id, '_simple_lms_time_end', sanitize_text_field( wp_unslash( $_POST['simple_lms_time_end'] ) ) );
        }

        if ( isset( $_POST['simple_lms_duration'] ) ) {
            update_post_meta( $post_id, '_simple_lms_duration', sanitize_text_field( wp_unslash( $_POST['simple_lms_duration'] ) ) );
        }

        if ( isset( $_POST['simple_lms_lecturer'] ) ) {
            update_post_meta( $post_id, '_simple_lms_lecturer', sanitize_text_field( wp_unslash( $_POST['simple_lms_lecturer'] ) ) );
        }

        // Videos.
        if ( isset( $_POST['simple_lms_videos'] ) && is_array( $_POST['simple_lms_videos'] ) ) {
            $videos = array();
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            foreach ( wp_unslash( $_POST['simple_lms_videos'] ) as $video ) {
                if ( ! empty( $video['title'] ) || ! empty( $video['vimeo_url'] ) ) {
                    $videos[] = array(
                        'title'     => sanitize_text_field( $video['title'] ),
                        'vimeo_url' => esc_url_raw( $video['vimeo_url'] ),
                    );
                }
            }
            update_post_meta( $post_id, '_simple_lms_videos', $videos );
        } else {
            delete_post_meta( $post_id, '_simple_lms_videos' );
        }

        // Materials.
        if ( isset( $_POST['simple_lms_materials'] ) && is_array( $_POST['simple_lms_materials'] ) ) {
            $materials = array();
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            foreach ( wp_unslash( $_POST['simple_lms_materials'] ) as $material ) {
                if ( ! empty( $material['label'] ) || ! empty( $material['url'] ) ) {
                    $materials[] = array(
                        'label' => sanitize_text_field( $material['label'] ),
                        'url'   => esc_url_raw( $material['url'] ),
                    );
                }
            }
            update_post_meta( $post_id, '_simple_lms_materials', $materials );
        } else {
            delete_post_meta( $post_id, '_simple_lms_materials' );
        }

        // Access control.
        if ( isset( $_POST['simple_lms_access_memberships'] ) && is_array( $_POST['simple_lms_access_memberships'] ) ) {
            $memberships = array_map( 'absint', wp_unslash( $_POST['simple_lms_access_memberships'] ) );
            update_post_meta( $post_id, '_simple_lms_access_memberships', $memberships );
        } else {
            delete_post_meta( $post_id, '_simple_lms_access_memberships' );
        }

        if ( isset( $_POST['simple_lms_access_products'] ) && is_array( $_POST['simple_lms_access_products'] ) ) {
            $products = array_map( 'absint', wp_unslash( $_POST['simple_lms_access_products'] ) );
            update_post_meta( $post_id, '_simple_lms_access_products', $products );
        } else {
            delete_post_meta( $post_id, '_simple_lms_access_products' );
        }

        if ( isset( $_POST['simple_lms_redirect_url'] ) ) {
            update_post_meta( $post_id, '_simple_lms_redirect_url', esc_url_raw( wp_unslash( $_POST['simple_lms_redirect_url'] ) ) );
        }
    }

    /**
     * Save course taxonomies.
     *
     * @param int $post_id Post ID.
     */
    private function save_course_taxonomies( $post_id ) {
        // Categories.
        if ( isset( $_POST['simple_lms_categories'] ) ) {
            $categories = array_map( 'absint', (array) $_POST['simple_lms_categories'] );
            wp_set_post_terms( $post_id, $categories, 'simple_lms_category' );
        } else {
            wp_set_post_terms( $post_id, array(), 'simple_lms_category' );
        }

        // Tags.
        if ( isset( $_POST['simple_lms_tags'] ) ) {
            $tags = sanitize_text_field( wp_unslash( $_POST['simple_lms_tags'] ) );
            wp_set_post_terms( $post_id, $tags, 'simple_lms_tag' );
        } else {
            wp_set_post_terms( $post_id, array(), 'simple_lms_tag' );
        }

        // Status.
        if ( isset( $_POST['simple_lms_course_status_tax'] ) ) {
            $status = array_map( 'absint', (array) $_POST['simple_lms_course_status_tax'] );
            wp_set_post_terms( $post_id, $status, 'simple_lms_status' );
        } else {
            wp_set_post_terms( $post_id, array(), 'simple_lms_status' );
        }
    }

    /**
     * AJAX handler for deleting a course.
     */
    public function ajax_delete_course() {
        check_ajax_referer( 'simple_lms_admin', 'nonce' );

        if ( ! current_user_can( 'delete_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-lms' ) ) );
        }

        $course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
        if ( ! $course_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid course ID.', 'simple-lms' ) ) );
        }

        $result = wp_delete_post( $course_id, true );
        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Course deleted successfully.', 'simple-lms' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete course.', 'simple-lms' ) ) );
        }
    }

    /**
     * AJAX handler for saving shortcode preset.
     */
    public function ajax_save_shortcode_preset() {
        check_ajax_referer( 'simple_lms_admin', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-lms' ) ) );
        }

        $preset_name = isset( $_POST['preset_name'] ) ? sanitize_key( $_POST['preset_name'] ) : '';
        if ( empty( $preset_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Preset name is required.', 'simple-lms' ) ) );
        }

        $preset = array(
            'name'       => $preset_name,
            'label'      => isset( $_POST['preset_label'] ) ? sanitize_text_field( wp_unslash( $_POST['preset_label'] ) ) : '',
            'statuses'   => isset( $_POST['statuses'] ) ? array_map( 'absint', (array) $_POST['statuses'] ) : array(),
            'categories' => isset( $_POST['categories'] ) ? array_map( 'absint', (array) $_POST['categories'] ) : array(),
            'tags'       => isset( $_POST['tags'] ) ? array_map( 'absint', (array) $_POST['tags'] ) : array(),
            'order'      => isset( $_POST['order'] ) && 'ASC' === $_POST['order'] ? 'ASC' : 'DESC',
            'orderby'    => isset( $_POST['orderby'] ) ? sanitize_key( $_POST['orderby'] ) : 'date',
            'limit'      => isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : -1,
            'columns'    => isset( $_POST['columns'] ) ? absint( $_POST['columns'] ) : 3,
            'elements'   => isset( $_POST['elements'] ) ? array_map( 'sanitize_key', (array) $_POST['elements'] ) : array( 'title', 'status', 'date', 'time', 'duration', 'lecturer' ),
        );

        $presets                 = get_option( 'simple_lms_shortcode_presets', array() );
        $presets[ $preset_name ] = $preset;
        update_option( 'simple_lms_shortcode_presets', $presets );

        wp_send_json_success( array( 'message' => __( 'Preset saved successfully.', 'simple-lms' ) ) );
    }

    /**
     * AJAX handler for deleting shortcode preset.
     */
    public function ajax_delete_shortcode_preset() {
        check_ajax_referer( 'simple_lms_admin', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-lms' ) ) );
        }

        $preset_name = isset( $_POST['preset_name'] ) ? sanitize_key( $_POST['preset_name'] ) : '';
        if ( empty( $preset_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Preset name is required.', 'simple-lms' ) ) );
        }

        $presets = get_option( 'simple_lms_shortcode_presets', array() );
        if ( isset( $presets[ $preset_name ] ) ) {
            unset( $presets[ $preset_name ] );
            update_option( 'simple_lms_shortcode_presets', $presets );
        }

        wp_send_json_success( array( 'message' => __( 'Preset deleted successfully.', 'simple-lms' ) ) );
    }

    /**
     * Get membership plans.
     *
     * @return array
     */
    public static function get_membership_plans() {
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
     * Get subscription products.
     *
     * @return array
     */
    public static function get_subscription_products() {
        if ( ! class_exists( 'WC_Subscriptions' ) ) {
            return array();
        }

        $products = wc_get_products(
            array(
                'type'   => array( 'subscription', 'variable-subscription' ),
                'limit'  => -1,
                'status' => 'publish',
            )
        );

        return $products;
    }

    /**
     * AJAX handler for resetting default template.
     */
    public function ajax_reset_default_template() {
        check_ajax_referer( 'simple_lms_admin', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-lms' ) ) );
        }

        $default_template = $this->get_builtin_default_template();
        update_option( 'simple_lms_default_template', $default_template );

        wp_send_json_success(
            array(
                'message'  => __( 'Default template has been reset.', 'simple-lms' ),
                'template' => $default_template,
            )
        );
    }

    /**
     * Get the built-in default template.
     *
     * @return string
     */
    public static function get_builtin_default_template() {
        return '<ul>
{{#IF_DATE}}<li>Data: {{LMS_DATE}}</li>{{/IF_DATE}}
{{#IF_TIME}}<li>Godzina: {{LMS_TIME}}</li>{{/IF_TIME}}
{{#IF_LECTURER}}<li>Wykładowca: {{LMS_LECTURER}}</li>{{/IF_LECTURER}}
</ul>

{{#IF_VIDEOS}}
{{LMS_VIDEOS}}
{{/IF_VIDEOS}}

{{#IF_CONTENT}}
{{LMS_CONTENT}}
{{/IF_CONTENT}}

{{#IF_MATERIALS}}
{{LMS_MATERIALS}}
{{/IF_MATERIALS}}';
    }
}
