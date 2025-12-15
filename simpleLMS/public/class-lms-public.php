<?php
/**
 * Public-facing functionality.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LMS_Public class.
 */
class LMS_Public {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Enqueue public scripts and styles.
     */
    public function enqueue_scripts() {
        // Only enqueue on course pages or pages with shortcode.
        if ( is_singular( 'simple_lms_course' ) || $this->page_has_shortcode() ) {
            wp_enqueue_style(
                'simple-lms-public',
                SIMPLE_LMS_PLUGIN_URL . 'public/css/public.css',
                array(),
                SIMPLE_LMS_VERSION
            );

            wp_enqueue_script(
                'simple-lms-public',
                SIMPLE_LMS_PLUGIN_URL . 'public/js/public.js',
                array( 'jquery' ),
                SIMPLE_LMS_VERSION,
                true
            );
        }
    }

    /**
     * Check if current page has our shortcode.
     *
     * @return bool
     */
    private function page_has_shortcode() {
        global $post;

        if ( ! is_a( $post, 'WP_Post' ) ) {
            return false;
        }

        return has_shortcode( $post->post_content, 'lms_courses' ) || has_shortcode( $post->post_content, 'lms_certificate' );
    }
}
