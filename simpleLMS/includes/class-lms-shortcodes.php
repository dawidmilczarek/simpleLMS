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

        $elements    = isset( $preset['elements'] ) ? $preset['elements'] : array( 'title', 'status', 'date', 'time', 'duration', 'lecturer' );
        $link_titles = isset( $preset['link_titles'] ) ? $preset['link_titles'] : true;

        $output = '<ul class="lms-courses-list" data-preset="' . esc_attr( $atts['preset'] ) . '">';

        while ( $courses->have_posts() ) {
            $courses->the_post();
            $output .= $this->render_course_list_item( get_the_ID(), $elements, $link_titles );
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
     * @param int   $post_id     Course post ID.
     * @param array $elements    Elements to display.
     * @param bool  $link_titles Whether to make titles clickable links.
     * @return string
     */
    private function render_course_list_item( $post_id, $elements, $link_titles = true ) {
        $data = LMS_Course_Data::get( $post_id );

        $parts = array();

        foreach ( $elements as $element ) {
            switch ( $element ) {
                case 'title':
                    if ( $link_titles ) {
                        $parts[] = '<a href="' . esc_url( get_permalink( $post_id ) ) . '" class="lms-course-link">' . esc_html( $data['title'] ) . '</a>';
                    } else {
                        $parts[] = '<span class="lms-course-title">' . esc_html( $data['title'] ) . '</span>';
                    }
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
}
