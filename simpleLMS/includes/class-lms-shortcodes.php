<?php
/**
 * Shortcode functionality.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LMS_Shortcodes class.
 */
class LMS_Shortcodes {

    /**
     * Constructor.
     */
    public function __construct() {
        add_shortcode( 'lms_courses', array( $this, 'render_courses_shortcode' ) );
    }

    /**
     * Render [lms_courses] shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_courses_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'preset' => 'all',
            ),
            $atts,
            'lms_courses'
        );

        $preset = $this->get_preset( $atts['preset'] );
        if ( ! $preset ) {
            return '';
        }

        $query_args = $this->build_query_args( $preset );

        /**
         * Filter the course query arguments.
         *
         * @param array  $query_args WP_Query arguments.
         * @param array  $preset     Preset configuration.
         * @param string $preset_name Preset name.
         */
        $query_args = apply_filters( 'lms_course_query_args', $query_args, $preset, $atts['preset'] );

        $courses = new WP_Query( $query_args );

        if ( ! $courses->have_posts() ) {
            return '';
        }

        $elements = isset( $preset['elements'] ) ? $preset['elements'] : array( 'title', 'status', 'date', 'time', 'duration', 'lecturer' );

        $output = '<ul class="lms-courses-list" data-preset="' . esc_attr( $atts['preset'] ) . '">';

        while ( $courses->have_posts() ) {
            $courses->the_post();
            $output .= $this->render_course_list_item( get_the_ID(), $elements );
        }

        $output .= '</ul>';

        wp_reset_postdata();

        return $output;
    }

    /**
     * Get preset configuration.
     *
     * @param string $preset_name Preset name.
     * @return array|false
     */
    private function get_preset( $preset_name ) {
        $presets = get_option( 'simple_lms_shortcode_presets', array() );

        if ( isset( $presets[ $preset_name ] ) ) {
            return $presets[ $preset_name ];
        }

        // Return default preset if 'all' doesn't exist.
        if ( 'all' === $preset_name ) {
            return array(
                'name'       => 'all',
                'label'      => 'All Courses',
                'statuses'   => array(),
                'categories' => array(),
                'tags'       => array(),
                'order'      => 'DESC',
                'orderby'    => 'date',
                'limit'      => -1,
                'elements'   => array( 'title', 'status', 'date', 'time', 'duration', 'lecturer' ),
            );
        }

        return false;
    }

    /**
     * Build WP_Query arguments from preset.
     *
     * @param array $preset Preset configuration.
     * @return array
     */
    private function build_query_args( $preset ) {
        $args = array(
            'post_type'      => 'simple_lms_course',
            'post_status'    => 'publish',
            'posts_per_page' => isset( $preset['limit'] ) ? intval( $preset['limit'] ) : -1,
            'order'          => isset( $preset['order'] ) ? $preset['order'] : 'DESC',
        );

        // Handle orderby.
        $orderby = isset( $preset['orderby'] ) ? $preset['orderby'] : 'date';
        switch ( $orderby ) {
            case 'date':
                // Order by course meta date.
                $args['meta_key'] = '_simple_lms_date';
                $args['orderby']  = 'meta_value';
                break;
            case 'title':
                $args['orderby'] = 'title';
                break;
            case 'menu_order':
                $args['orderby'] = 'menu_order';
                break;
            default:
                $args['orderby'] = 'date';
        }

        // Build tax query.
        $tax_query = array();

        if ( ! empty( $preset['statuses'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'simple_lms_status',
                'field'    => 'term_id',
                'terms'    => array_map( 'absint', $preset['statuses'] ),
            );
        }

        if ( ! empty( $preset['categories'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'simple_lms_category',
                'field'    => 'term_id',
                'terms'    => array_map( 'absint', $preset['categories'] ),
            );
        }

        if ( ! empty( $preset['tags'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'simple_lms_tag',
                'field'    => 'term_id',
                'terms'    => array_map( 'absint', $preset['tags'] ),
            );
        }

        if ( ! empty( $tax_query ) ) {
            $tax_query['relation'] = 'AND';
            $args['tax_query']     = $tax_query;
        }

        return $args;
    }

    /**
     * Render a single course list item.
     *
     * @param int   $post_id  Course post ID.
     * @param array $elements Elements to display.
     * @return string
     */
    private function render_course_list_item( $post_id, $elements ) {
        $data = $this->get_course_data( $post_id );

        $parts = array();

        foreach ( $elements as $element ) {
            switch ( $element ) {
                case 'title':
                    $parts[] = '<a href="' . esc_url( get_permalink( $post_id ) ) . '" class="lms-course-link">' . esc_html( $data['title'] ) . '</a>';
                    break;

                case 'status':
                    if ( ! empty( $data['status'] ) ) {
                        $parts[] = '<span class="lms-meta-status">' . esc_html( $data['status'] ) . '</span>';
                    }
                    break;

                case 'date':
                    if ( ! empty( $data['date'] ) ) {
                        $parts[] = '<span class="lms-meta-date">' . esc_html( $data['date'] ) . '</span>';
                    }
                    break;

                case 'time':
                    if ( ! empty( $data['time'] ) ) {
                        $parts[] = '<span class="lms-meta-time">' . esc_html( $data['time'] ) . '</span>';
                    }
                    break;

                case 'duration':
                    if ( ! empty( $data['duration'] ) ) {
                        $parts[] = '<span class="lms-meta-duration">' . esc_html( $data['duration'] ) . '</span>';
                    }
                    break;

                case 'lecturer':
                    if ( ! empty( $data['lecturer'] ) ) {
                        $parts[] = '<span class="lms-meta-lecturer">' . esc_html( $data['lecturer'] ) . '</span>';
                    }
                    break;

                case 'category':
                    if ( ! empty( $data['category'] ) ) {
                        $parts[] = '<span class="lms-meta-category">' . esc_html( $data['category'] ) . '</span>';
                    }
                    break;

                case 'tags':
                    if ( ! empty( $data['tags'] ) ) {
                        $parts[] = '<span class="lms-meta-tags">' . esc_html( $data['tags'] ) . '</span>';
                    }
                    break;
            }
        }

        $output = '<li class="lms-course-item">';
        $output .= implode( ', ', $parts );
        $output .= '</li>';

        return $output;
    }

    /**
     * Get course data.
     *
     * @param int $post_id Course post ID.
     * @return array
     */
    private function get_course_data( $post_id ) {
        $date       = get_post_meta( $post_id, '_simple_lms_date', true );
        $time_start = get_post_meta( $post_id, '_simple_lms_time_start', true );
        $time_end   = get_post_meta( $post_id, '_simple_lms_time_end', true );
        $duration   = get_post_meta( $post_id, '_simple_lms_duration', true );

        // Get lecturer from taxonomy.
        $lecturer_terms = wp_get_post_terms( $post_id, 'simple_lms_lecturer', array( 'fields' => 'names' ) );
        $lecturer       = ! empty( $lecturer_terms ) && ! is_wp_error( $lecturer_terms ) ? implode( ', ', $lecturer_terms ) : '';

        // Format date.
        $date_format    = Simple_LMS::get_setting( 'date_format', 'd.m.Y' );
        $formatted_date = '';
        if ( ! empty( $date ) ) {
            $timestamp = strtotime( $date );
            if ( $timestamp ) {
                $formatted_date = date_i18n( $date_format, $timestamp );
            }
        }

        // Format time range.
        $time_range = '';
        if ( ! empty( $time_start ) && ! empty( $time_end ) ) {
            $time_range = $time_start . ' - ' . $time_end;
        } elseif ( ! empty( $time_start ) ) {
            $time_range = $time_start;
        }

        // Get taxonomies.
        $categories = wp_get_post_terms( $post_id, 'simple_lms_category' );
        $tags       = wp_get_post_terms( $post_id, 'simple_lms_tag' );
        $statuses   = wp_get_post_terms( $post_id, 'simple_lms_status' );

        return array(
            'title'    => get_the_title( $post_id ),
            'date'     => $formatted_date,
            'time'     => $time_range,
            'duration' => $duration,
            'lecturer' => $lecturer,
            'category' => ! empty( $categories ) && ! is_wp_error( $categories ) ? $categories[0]->name : '',
            'tags'     => ! empty( $tags ) && ! is_wp_error( $tags ) ? implode( ', ', wp_list_pluck( $tags, 'name' ) ) : '',
            'status'   => ! empty( $statuses ) && ! is_wp_error( $statuses ) ? $statuses[0]->name : '',
        );
    }
}
