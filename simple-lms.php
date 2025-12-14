<?php
/**
 * Plugin Name: simpleLMS
 * Plugin URI: https://example.com/simple-lms
 * Description: A lightweight LMS plugin for WordPress/WooCommerce that integrates with WooCommerce Memberships and Subscriptions for access control.
 * Version: 1.0.0
 * Author: Dawid Milczarek
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-lms
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
define( 'SIMPLE_LMS_VERSION', '1.0.1' );
define( 'SIMPLE_LMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_LMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SIMPLE_LMS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Activation hook.
 */
function simple_lms_activate() {
    // Set default options if they don't exist.
    if ( false === get_option( 'simple_lms_settings' ) ) {
        $default_settings = array(
            'redirect_url'           => '/sklep/',
            'date_format'            => 'd.m.Y',
            'default_material_label' => '',
            'default_video_title'    => '',
            'default_lecturer'       => '',
            'default_time_start'     => '',
            'default_time_end'       => '',
            'default_duration'       => '',
            'default_status'         => '',
        );
        add_option( 'simple_lms_settings', $default_settings );
    }

    if ( false === get_option( 'simple_lms_default_template' ) ) {
        $default_template = '{{#IF_VIDEOS}}
{{LMS_VIDEOS}}
{{/IF_VIDEOS}}

{{#IF_CONTENT}}
{{LMS_CONTENT}}
{{/IF_CONTENT}}

{{#IF_MATERIALS}}
{{LMS_MATERIALS}}
{{/IF_MATERIALS}}';
        add_option( 'simple_lms_default_template', $default_template );
    }

    if ( false === get_option( 'simple_lms_status_templates' ) ) {
        add_option( 'simple_lms_status_templates', array() );
    }

    if ( false === get_option( 'simple_lms_shortcode_presets' ) ) {
        $default_presets = array(
            'all' => array(
                'name'       => 'all',
                'label'      => 'All Courses',
                'statuses'   => array(),
                'categories' => array(),
                'tags'       => array(),
                'order'      => 'DESC',
                'orderby'    => 'date',
                'limit'      => -1,
                'columns'    => 3,
                'elements'   => array( 'title', 'status', 'date', 'time', 'duration', 'lecturer' ),
            ),
        );
        add_option( 'simple_lms_shortcode_presets', $default_presets );
    }

    // Flush rewrite rules.
    require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-lms-post-types.php';
    LMS_Post_Types::register_post_types();
    LMS_Post_Types::register_taxonomies();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'simple_lms_activate' );

/**
 * Deactivation hook.
 */
function simple_lms_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'simple_lms_deactivate' );

/**
 * Load the main plugin class.
 */
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-simple-lms.php';

/**
 * Returns the main instance of Simple_LMS.
 *
 * @return Simple_LMS
 */
function simple_lms() {
    return Simple_LMS::instance();
}

// Initialize the plugin.
simple_lms();
