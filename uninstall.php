<?php
/**
 * Uninstall script.
 *
 * This file runs when the plugin is deleted from WordPress.
 * It cleans up all plugin data from the database.
 *
 * @package SimpleLMS
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Delete all plugin options.
 */
function simple_lms_delete_options() {
    delete_option( 'simple_lms_settings' );
    delete_option( 'simple_lms_default_template' );
    delete_option( 'simple_lms_status_templates' );
    delete_option( 'simple_lms_shortcode_presets' );
}

/**
 * Delete all courses and their meta.
 */
function simple_lms_delete_courses() {
    $courses = get_posts(
        array(
            'post_type'      => 'simple_lms_course',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        )
    );

    foreach ( $courses as $course_id ) {
        wp_delete_post( $course_id, true );
    }
}

/**
 * Delete all plugin taxonomies terms.
 */
function simple_lms_delete_taxonomies() {
    $taxonomies = array(
        'simple_lms_category',
        'simple_lms_tag',
        'simple_lms_status',
    );

    foreach ( $taxonomies as $taxonomy ) {
        $terms = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'fields'     => 'ids',
            )
        );

        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term_id ) {
                wp_delete_term( $term_id, $taxonomy );
            }
        }
    }
}

// Run cleanup.
simple_lms_delete_options();
simple_lms_delete_courses();
simple_lms_delete_taxonomies();

// Clear any cached data.
wp_cache_flush();
