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
        if ( ! is_singular( 'simple_lms_course' ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

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
        return '<div class="lms-course-single">
  <div class="lms-course-header">
    <p>
      <strong>Wykładowca:</strong> {{LMS_LECTURER}}<br>
      <strong>Data szkolenia:</strong> {{LMS_DATE}}<br>
      <strong>Godziny:</strong> {{LMS_TIME}}<br>
      <strong>Czas trwania:</strong> {{LMS_DURATION}}
    </p>
  </div>

  {{#IF_VIDEOS}}
  <div class="lms-video-section">
    <h2>Nagrania</h2>
    {{LMS_VIDEOS}}
  </div>
  {{/IF_VIDEOS}}

  {{#IF_CONTENT}}
  <div class="lms-content-section">
    {{LMS_CONTENT}}
  </div>
  {{/IF_CONTENT}}

  {{#IF_MATERIALS}}
  <div class="lms-materials-section">
    <h2>Materiały szkoleniowe</h2>
    {{LMS_MATERIALS}}
  </div>
  {{/IF_MATERIALS}}
</div>';
    }

    /**
     * Parse template and replace placeholders.
     *
     * @param string $template Template HTML.
     * @param int    $post_id  Course post ID.
     * @return string
     */
    public function parse_template( $template, $post_id ) {
        $data = $this->get_course_data( $post_id );

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
     * Get course data for template.
     *
     * @param int $post_id Course post ID.
     * @return array
     */
    private function get_course_data( $post_id ) {
        $post = get_post( $post_id );

        $date       = get_post_meta( $post_id, '_simple_lms_date', true );
        $time_start = get_post_meta( $post_id, '_simple_lms_time_start', true );
        $time_end   = get_post_meta( $post_id, '_simple_lms_time_end', true );
        $duration   = get_post_meta( $post_id, '_simple_lms_duration', true );
        $lecturer   = get_post_meta( $post_id, '_simple_lms_lecturer', true );
        $videos     = get_post_meta( $post_id, '_simple_lms_videos', true );
        $materials  = get_post_meta( $post_id, '_simple_lms_materials', true );

        // Get taxonomies.
        $categories = wp_get_post_terms( $post_id, 'simple_lms_category' );
        $tags       = wp_get_post_terms( $post_id, 'simple_lms_tag' );
        $statuses   = wp_get_post_terms( $post_id, 'simple_lms_status' );

        // Format date.
        $date_format   = Simple_LMS::get_setting( 'date_format', 'd.m.Y' );
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

        return array(
            'title'      => get_the_title( $post_id ),
            'date'       => $formatted_date,
            'time'       => $time_range,
            'duration'   => $duration,
            'lecturer'   => $lecturer,
            'videos'     => is_array( $videos ) ? $videos : array(),
            'materials'  => is_array( $materials ) ? $materials : array(),
            'category'   => ! empty( $categories ) && ! is_wp_error( $categories ) ? $categories[0]->name : '',
            'tags'       => ! empty( $tags ) && ! is_wp_error( $tags ) ? implode( ', ', wp_list_pluck( $tags, 'name' ) ) : '',
            'status'     => ! empty( $statuses ) && ! is_wp_error( $statuses ) ? $statuses[0]->name : '',
            'content'    => apply_filters( 'the_content', $post->post_content ),
            'raw_content' => $post->post_content,
        );
    }

    /**
     * Get placeholder => value pairs.
     *
     * @param array $data Course data.
     * @return array
     */
    private function get_placeholders( $data ) {
        return array(
            '{{LMS_TITLE}}'     => esc_html( $data['title'] ),
            '{{LMS_DATE}}'      => esc_html( $data['date'] ),
            '{{LMS_TIME}}'      => esc_html( $data['time'] ),
            '{{LMS_DURATION}}'  => esc_html( $data['duration'] ),
            '{{LMS_LECTURER}}'  => esc_html( $data['lecturer'] ),
            '{{LMS_VIDEOS}}'    => $this->render_videos( $data['videos'] ),
            '{{LMS_MATERIALS}}' => $this->render_materials( $data['materials'] ),
            '{{LMS_CATEGORY}}'  => esc_html( $data['category'] ),
            '{{LMS_TAGS}}'      => esc_html( $data['tags'] ),
            '{{LMS_STATUS}}'    => esc_html( $data['status'] ),
            '{{LMS_CONTENT}}'   => $data['content'],
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
            'DATE'      => ! empty( $data['date'] ),
            'TIME'      => ! empty( $data['time'] ),
            'DURATION'  => ! empty( $data['duration'] ),
            'LECTURER'  => ! empty( $data['lecturer'] ),
            'VIDEOS'    => ! empty( $data['videos'] ),
            'MATERIALS' => ! empty( $data['materials'] ),
            'CATEGORY'  => ! empty( $data['category'] ),
            'TAGS'      => ! empty( $data['tags'] ),
            'STATUS'    => ! empty( $data['status'] ),
            'CONTENT'   => ! empty( trim( $data['raw_content'] ) ),
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
}
