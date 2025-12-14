<?php
/**
 * Main plugin class.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Simple_LMS class.
 */
class Simple_LMS {

    /**
     * Single instance of the class.
     *
     * @var Simple_LMS
     */
    private static $instance = null;

    /**
     * Post types class instance.
     *
     * @var LMS_Post_Types
     */
    public $post_types;

    /**
     * Meta boxes class instance.
     *
     * @var LMS_Meta_Boxes
     */
    public $meta_boxes;

    /**
     * Admin class instance.
     *
     * @var LMS_Admin
     */
    public $admin;

    /**
     * Access control class instance.
     *
     * @var LMS_Access_Control
     */
    public $access_control;

    /**
     * Templates class instance.
     *
     * @var LMS_Templates
     */
    public $templates;

    /**
     * Shortcodes class instance.
     *
     * @var LMS_Shortcodes
     */
    public $shortcodes;

    /**
     * Public class instance.
     *
     * @var LMS_Public
     */
    public $public;

    /**
     * Returns the single instance of the class.
     *
     * @return Simple_LMS
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files.
     */
    private function includes() {
        // Core classes.
        require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-lms-post-types.php';
        require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-lms-meta-boxes.php';
        require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-lms-access-control.php';
        require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-lms-templates.php';
        require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-lms-shortcodes.php';

        // Admin classes.
        if ( is_admin() ) {
            require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-lms-admin.php';
        }

        // Public classes.
        if ( ! is_admin() ) {
            require_once SIMPLE_LMS_PLUGIN_DIR . 'public/class-lms-public.php';
        }
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'init' ), 0 );
        add_action( 'admin_notices', array( $this, 'dependency_notices' ) );
    }

    /**
     * Initialize classes on init.
     */
    public function init() {
        // Initialize post types and taxonomies.
        $this->post_types = new LMS_Post_Types();

        // Initialize meta boxes.
        $this->meta_boxes = new LMS_Meta_Boxes();

        // Initialize access control.
        $this->access_control = new LMS_Access_Control();

        // Initialize templates.
        $this->templates = new LMS_Templates();

        // Initialize shortcodes.
        $this->shortcodes = new LMS_Shortcodes();

        // Initialize admin.
        if ( is_admin() ) {
            $this->admin = new LMS_Admin();
        }

        // Initialize public.
        if ( ! is_admin() ) {
            $this->public = new LMS_Public();
        }
    }

    /**
     * Display admin notices for missing dependencies.
     */
    public function dependency_notices() {
        // Check for WooCommerce.
        if ( ! class_exists( 'WooCommerce' ) ) {
            ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e( 'Simple LMS: WooCommerce is not active. Some features may not work correctly.', 'simple-lms' ); ?></p>
            </div>
            <?php
        }

        // Check for WooCommerce Memberships.
        $has_memberships = function_exists( 'wc_memberships' );

        // Check for WooCommerce Subscriptions.
        $has_subscriptions = class_exists( 'WC_Subscriptions' );

        if ( ! $has_memberships && ! $has_subscriptions ) {
            ?>
            <div class="notice notice-info">
                <p><?php esc_html_e( 'Simple LMS: Neither WooCommerce Memberships nor WooCommerce Subscriptions is active. Access control features are disabled.', 'simple-lms' ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Get plugin settings.
     *
     * @param string $key Optional specific setting key.
     * @param mixed  $default Default value if key not found.
     * @return mixed
     */
    public static function get_setting( $key = null, $default = '' ) {
        $settings = get_option( 'simple_lms_settings', array() );

        if ( null === $key ) {
            return $settings;
        }

        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }

    /**
     * Update plugin settings.
     *
     * @param string $key Setting key.
     * @param mixed  $value Setting value.
     */
    public static function update_setting( $key, $value ) {
        $settings         = get_option( 'simple_lms_settings', array() );
        $settings[ $key ] = $value;
        update_option( 'simple_lms_settings', $settings );
    }
}
