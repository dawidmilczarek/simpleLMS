<?php
/**
 * Template engine for course display.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LMS_Templates class.
 */
class LMS_Templates {

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'single_template', array( $this, 'load_course_template' ) );
        add_filter( 'the_content', array( $this, 'render_course_content' ) );
    }

    /**
     * Load custom template for single course.
     *
     * @param string $template Template path.
     * @return string
     */
    public function load_course_template( $template ) {
        if ( is_singular( 'simple_lms_course' ) ) {
            $custom_template = SIMPLE_LMS_PLUGIN_DIR . 'templates/single-simple_lms_course.php';
            if ( file_exists( $custom_template ) ) {
                return $custom_template;
            }
        }
        return $template;
    }

    /**
     * Render course content using template.
     *
     * @param string $content Original content.
     * @return string
     */
    public function render_course_content( $content ) {
        static $is_rendering = false;

        // Prevent infinite recursion when processing {{LMS_CONTENT}} placeholder.
        if ( $is_rendering ) {
            return $content;
        }

        if ( ! is_singular( 'simple_lms_course' ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $is_rendering = true;

        $post_id  = get_the_ID();
        $template = $this->get_template_for_course( $post_id );
        $output   = $this->parse_template( $template, $post_id );

        /**
         * Fires before course content.
         *
         * @param int $post_id Course post ID.
         */
        do_action( 'lms_before_course_content', $post_id );

        /**
         * Filter the final template output.
         *
         * @param string $output  Parsed template HTML.
         * @param int    $post_id Course post ID.
         */
        $output = apply_filters( 'lms_template_output', $output, $post_id );

        /**
         * Fires after course content.
         *
         * @param int $post_id Course post ID.
         */
        do_action( 'lms_after_course_content', $post_id );

        $is_rendering = false;

        return $output;
    }

    /**
     * Get the appropriate template for a course.
     *
     * @param int $post_id Course post ID.
     * @return string
     */
    public function get_template_for_course( $post_id ) {
        // Check for status-specific template.
        $statuses = wp_get_post_terms( $post_id, 'simple_lms_status', array( 'fields' => 'ids' ) );
        if ( ! empty( $statuses ) && ! is_wp_error( $statuses ) ) {
            $status_templates = get_option( 'simple_lms_status_templates', array() );
            foreach ( $statuses as $status_id ) {
                if ( ! empty( $status_templates[ $status_id ] ) ) {
                    return $status_templates[ $status_id ];
                }
            }
        }

        // Fall back to default template.
        $default_template = get_option( 'simple_lms_default_template', '' );
        if ( ! empty( $default_template ) ) {
            return $default_template;
        }

        // Built-in fallback.
        return $this->get_fallback_template();
    }

    /**
     * Get fallback template.
     *
     * @return string
     */
    private function get_fallback_template() {
        return '{{#IF_LIVE_LINK}}
<p>{{LMS_LIVE_LINK}}</p>
{{/IF_LIVE_LINK}}

{{#IF_VIDEOS}}
{{LMS_VIDEOS}}
{{/IF_VIDEOS}}

{{#IF_CONTENT}}
{{LMS_CONTENT}}
{{/IF_CONTENT}}

{{#IF_MATERIALS}}
{{LMS_MATERIALS}}
{{/IF_MATERIALS}}

{{#IF_CERTIFICATE}}
{{LMS_CERTIFICATE}}
{{/IF_CERTIFICATE}}';
    }

    /**
     * Parse template and replace placeholders.
     *
     * @param string $template Template HTML.
     * @param int    $post_id  Course post ID.
     * @return string
     */
    public function parse_template( $template, $post_id ) {
        // Get course data with content included.
        $data = LMS_Course_Data::get( $post_id, true );

        // Add certificate availability.
        $data['certificate_available'] = $this->is_certificate_available( $post_id );

        // Process conditional blocks first.
        $template = $this->process_conditionals( $template, $data );

        // Replace placeholders.
        $placeholders = $this->get_placeholders( $data );

        /**
         * Filter available placeholders.
         *
         * @param array $placeholders Placeholder => value pairs.
         * @param int   $post_id      Course post ID.
         * @param array $data         Course data.
         */
        $placeholders = apply_filters( 'lms_placeholders', $placeholders, $post_id, $data );

        foreach ( $placeholders as $placeholder => $value ) {
            $template = str_replace( $placeholder, $value, $template );
        }

        return $template;
    }

    /**
     * Get placeholder => value pairs.
     *
     * @param array $data Course data.
     * @return array
     */
    private function get_placeholders( $data ) {
        return array(
            '{{LMS_TITLE}}'       => esc_html( $data['title'] ),
            '{{LMS_DATE}}'        => esc_html( $data['date'] ),
            '{{LMS_TIME}}'        => esc_html( $data['time'] ),
            '{{LMS_DURATION}}'    => esc_html( $data['duration'] ),
            '{{LMS_LECTURER}}'    => esc_html( $data['lecturer'] ),
            '{{LMS_LIVE_LINK}}'   => $this->render_live_link( $data['live_link'] ),
            '{{LMS_VIDEOS}}'      => $this->render_videos( $data['videos'] ),
            '{{LMS_MATERIALS}}'   => $this->render_materials( $data['materials'] ),
            '{{LMS_CATEGORY}}'    => esc_html( $data['category'] ),
            '{{LMS_TAGS}}'        => esc_html( $data['tags'] ),
            '{{LMS_STATUS}}'      => esc_html( $data['status'] ),
            '{{LMS_CONTENT}}'     => $data['content'],
            '{{LMS_CERTIFICATE}}' => $this->render_certificate_button(),
        );
    }

    /**
     * Process conditional blocks.
     *
     * @param string $template Template HTML.
     * @param array  $data     Course data.
     * @return string
     */
    private function process_conditionals( $template, $data ) {
        $conditions = array(
            'DATE'        => ! empty( $data['date'] ),
            'TIME'        => ! empty( $data['time'] ),
            'DURATION'    => ! empty( $data['duration'] ),
            'LECTURER'    => ! empty( $data['lecturer'] ),
            'LIVE_LINK'   => ! empty( $data['live_link']['url'] ),
            'VIDEOS'      => ! empty( $data['videos'] ),
            'MATERIALS'   => ! empty( $data['materials'] ),
            'CATEGORY'    => ! empty( $data['category'] ),
            'TAGS'        => ! empty( $data['tags'] ),
            'STATUS'      => ! empty( $data['status'] ),
            'CONTENT'     => ! empty( trim( $data['raw_content'] ) ),
            'CERTIFICATE' => ! empty( $data['certificate_available'] ),
        );

        foreach ( $conditions as $key => $is_true ) {
            $pattern = '/\{\{#IF_' . $key . '\}\}(.*?)\{\{\/IF_' . $key . '\}\}/s';

            if ( $is_true ) {
                // Keep the content, remove the tags.
                $template = preg_replace( $pattern, '$1', $template );
            } else {
                // Remove the entire block.
                $template = preg_replace( $pattern, '', $template );
            }
        }

        return $template;
    }

    /**
     * Render videos HTML.
     *
     * @param array $videos Videos array.
     * @return string
     */
    private function render_videos( $videos ) {
        if ( empty( $videos ) ) {
            return '';
        }

        $output = '';
        foreach ( $videos as $video ) {
            if ( empty( $video['vimeo_url'] ) ) {
                continue;
            }

            $title     = ! empty( $video['title'] ) ? $video['title'] : '';
            $embed_url = $this->get_vimeo_embed_url( $video['vimeo_url'] );

            if ( ! $embed_url ) {
                continue;
            }

            $video_html = '<div class="lms-video-item">';
            if ( $title ) {
                $video_html .= '<h3>' . esc_html( $title ) . '</h3>';
            }
            $video_html .= '<div class="lms-video-embed">';
            $video_html .= '<iframe src="' . esc_url( $embed_url ) . '" width="640" height="360" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
            $video_html .= '</div>';
            $video_html .= '</div>';

            /**
             * Filter video embed HTML.
             *
             * @param string $video_html Video HTML.
             * @param array  $video      Video data.
             */
            $output .= apply_filters( 'lms_video_embed_html', $video_html, $video );
        }

        return $output;
    }

    /**
     * Render live event link HTML.
     *
     * @param array $live_link Live link data.
     * @return string
     */
    private function render_live_link( $live_link ) {
        if ( empty( $live_link['url'] ) ) {
            return '';
        }

        $label = ! empty( $live_link['label'] ) ? $live_link['label'] : $live_link['url'];
        $output = '<a href="' . esc_url( $live_link['url'] ) . '" class="lms-live-link" target="_blank">' . esc_html( $label ) . '</a>';

        /**
         * Filter live link HTML.
         *
         * @param string $output    Live link HTML.
         * @param array  $live_link Live link data.
         */
        return apply_filters( 'lms_live_link_html', $output, $live_link );
    }

    /**
     * Convert Vimeo URL to embed URL.
     *
     * @param string $url Vimeo URL.
     * @return string|false
     */
    private function get_vimeo_embed_url( $url ) {
        // Match Vimeo video ID.
        if ( preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches ) ) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        return false;
    }

    /**
     * Render materials HTML.
     *
     * @param array $materials Materials array.
     * @return string
     */
    private function render_materials( $materials ) {
        if ( empty( $materials ) ) {
            return '';
        }

        $output = '<ul class="lms-materials-list">';
        foreach ( $materials as $material ) {
            if ( empty( $material['url'] ) ) {
                continue;
            }

            $label = ! empty( $material['label'] ) ? $material['label'] : $material['url'];
            $output .= '<li><a href="' . esc_url( $material['url'] ) . '" target="_blank">' . esc_html( $label ) . '</a></li>';
        }
        $output .= '</ul>';

        /**
         * Filter materials list HTML.
         *
         * @param string $output    Materials HTML.
         * @param array  $materials Materials data.
         */
        return apply_filters( 'lms_materials_html', $output, $materials );
    }

    /**
     * Check if certificate is available for current course.
     *
     * @param int $post_id Course post ID.
     * @return bool
     */
    private function is_certificate_available( $post_id ) {
        // Check if user is logged in.
        if ( ! is_user_logged_in() ) {
            return false;
        }

        // Check if certificate is enabled for this course.
        $certificate_enabled = get_post_meta( $post_id, '_simple_lms_certificate_enabled', true );
        if ( '0' === $certificate_enabled ) {
            return false;
        }

        // Check user access.
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        if ( class_exists( 'LMS_Access_Control' ) ) {
            return LMS_Access_Control::user_has_access( $post_id, get_current_user_id() );
        }

        return true;
    }

    /**
     * Render certificate button HTML.
     *
     * @return string
     */
    private function render_certificate_button() {
        $post_id = get_the_ID();

        if ( ! $this->is_certificate_available( $post_id ) ) {
            return '';
        }

        // Use LMS_Certificates class to render.
        $lms = Simple_LMS::instance();
        if ( isset( $lms->certificates ) ) {
            return $lms->certificates->render_certificate_button( $post_id );
        }

        return '';
    }
}
