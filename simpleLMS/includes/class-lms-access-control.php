<?php
/**
 * Access Control functionality.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LMS_Access_Control class.
 */
class LMS_Access_Control {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'template_redirect', array( $this, 'check_course_access' ) );
    }

    /**
     * Check if user has access to a course.
     */
    public function check_course_access() {
        if ( ! is_singular( 'simple_lms_course' ) ) {
            return;
        }

        $post_id = get_the_ID();

        if ( self::user_has_access( $post_id ) ) {
            return;
        }

        /**
         * Fires when access is denied.
         *
         * @param int $post_id Course post ID.
         */
        do_action( 'lms_access_denied', $post_id );

        // Get redirect URL (same for all users without access).
        $redirect_url = $this->get_redirect_url( $post_id );

        /**
         * Filter the redirect URL.
         *
         * @param string $redirect_url The URL to redirect to.
         * @param int    $post_id      Course post ID.
         */
        $redirect_url = apply_filters( 'lms_redirect_url', $redirect_url, $post_id );

        // Use wp_redirect for external URLs (wp_safe_redirect blocks external domains).
        if ( wp_http_validate_url( $redirect_url ) && ! $this->is_internal_url( $redirect_url ) ) {
            wp_redirect( $redirect_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
        } else {
            wp_safe_redirect( $redirect_url );
        }
        exit;
    }

    /**
     * Check if user has access to a course.
     *
     * @param int      $post_id Course post ID.
     * @param int|null $user_id User ID. Defaults to current user.
     * @return bool
     */
    public static function user_has_access( $post_id, $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

        // Administrators always have access.
        if ( user_can( $user_id, 'manage_options' ) ) {
            return true;
        }

        $memberships = get_post_meta( $post_id, '_simple_lms_access_memberships', true );
        $products    = get_post_meta( $post_id, '_simple_lms_access_products', true );

        $memberships = is_array( $memberships ) ? $memberships : array();
        $products    = is_array( $products ) ? $products : array();

        // If no access rules defined, course is public.
        if ( empty( $memberships ) && empty( $products ) ) {
            return true;
        }

        // Check memberships.
        if ( ! empty( $memberships ) && self::user_has_membership( $user_id, $memberships ) ) {
            return true;
        }

        // Check subscriptions.
        if ( ! empty( $products ) && self::user_has_subscription( $user_id, $products ) ) {
            return true;
        }

        /**
         * Filter whether user has access.
         *
         * @param bool $has_access Whether user has access.
         * @param int  $post_id    Course post ID.
         * @param int  $user_id    User ID.
         */
        return apply_filters( 'lms_user_has_access', false, $post_id, $user_id );
    }

    /**
     * Check if user has any of the specified memberships.
     *
     * @param int   $user_id User ID.
     * @param array $plan_ids Membership plan IDs.
     * @return bool
     */
    private static function user_has_membership( $user_id, $plan_ids ) {
        if ( ! function_exists( 'wc_memberships_is_user_active_member' ) ) {
            return false;
        }

        foreach ( $plan_ids as $plan_id ) {
            if ( wc_memberships_is_user_active_member( $user_id, $plan_id ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has active subscription to any of the specified products.
     *
     * @param int   $user_id User ID.
     * @param array $product_ids Subscription product IDs.
     * @return bool
     */
    private static function user_has_subscription( $user_id, $product_ids ) {
        if ( ! function_exists( 'wcs_user_has_subscription' ) ) {
            return false;
        }

        foreach ( $product_ids as $product_id ) {
            if ( wcs_user_has_subscription( $user_id, $product_id, 'active' ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get redirect URL for unauthorized access.
     *
     * @param int $post_id Course post ID.
     * @return string
     */
    private function get_redirect_url( $post_id ) {
        // Check course-specific redirect.
        $course_redirect = get_post_meta( $post_id, '_simple_lms_redirect_url', true );
        if ( ! empty( $course_redirect ) ) {
            return $this->normalize_redirect_url( $course_redirect );
        }

        // Fall back to global setting.
        $default_redirect = Simple_LMS::get_setting( 'redirect_url', '/' );

        return $this->normalize_redirect_url( $default_redirect );
    }

    /**
     * Normalize redirect URL - return as-is if external, or prepend home_url if relative.
     *
     * @param string $url The URL to normalize.
     * @return string
     */
    private function normalize_redirect_url( $url ) {
        // If it's already a full URL, return as-is.
        if ( preg_match( '#^https?://#i', $url ) ) {
            return $url;
        }

        // Otherwise, treat as relative path.
        return home_url( $url );
    }

    /**
     * Check if URL is internal (same host as site).
     *
     * @param string $url The URL to check.
     * @return bool
     */
    private function is_internal_url( $url ) {
        $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
        $url_host  = wp_parse_url( $url, PHP_URL_HOST );

        return $site_host === $url_host;
    }

    /**
     * Check if memberships plugin is active.
     *
     * @return bool
     */
    public static function has_memberships() {
        return function_exists( 'wc_memberships' );
    }

    /**
     * Check if subscriptions plugin is active.
     *
     * @return bool
     */
    public static function has_subscriptions() {
        return class_exists( 'WC_Subscriptions' );
    }

    /**
     * Check if any access control plugin is active.
     *
     * @return bool
     */
    public static function has_access_control() {
        return self::has_memberships() || self::has_subscriptions();
    }
}
