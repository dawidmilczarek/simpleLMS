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
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_simple_lms_save_shortcode_preset', array( $this, 'ajax_save_shortcode_preset' ) );
        add_action( 'wp_ajax_simple_lms_delete_shortcode_preset', array( $this, 'ajax_delete_shortcode_preset' ) );
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        // Main menu.
        add_menu_page(
            __( 'Simple LMS', 'simple-lms' ),
            __( 'simpleLMS', 'simple-lms' ),
            'manage_options',
            'simple-lms',
            array( $this, 'render_settings_page' ),
            'dashicons-welcome-learn-more',
            30
        );

        // Courses submenu.
        add_submenu_page(
            'simple-lms',
            __( 'Courses', 'simple-lms' ),
            __( 'Courses', 'simple-lms' ),
            'edit_posts',
            'edit.php?post_type=simple_lms_course'
        );

        // Add New Course submenu.
        add_submenu_page(
            'simple-lms',
            __( 'Add New Course', 'simple-lms' ),
            __( 'Add New Course', 'simple-lms' ),
            'edit_posts',
            'post-new.php?post_type=simple_lms_course'
        );

        // Categories submenu.
        add_submenu_page(
            'simple-lms',
            __( 'Categories', 'simple-lms' ),
            __( 'Categories', 'simple-lms' ),
            'manage_categories',
            'edit-tags.php?taxonomy=simple_lms_category&post_type=simple_lms_course'
        );

        // Tags submenu.
        add_submenu_page(
            'simple-lms',
            __( 'Tags', 'simple-lms' ),
            __( 'Tags', 'simple-lms' ),
            'manage_categories',
            'edit-tags.php?taxonomy=simple_lms_tag&post_type=simple_lms_course'
        );

        // Statuses submenu.
        add_submenu_page(
            'simple-lms',
            __( 'Statuses', 'simple-lms' ),
            __( 'Statuses', 'simple-lms' ),
            'manage_categories',
            'edit-tags.php?taxonomy=simple_lms_status&post_type=simple_lms_course'
        );

        // Settings submenu (points to main page).
        add_submenu_page(
            'simple-lms',
            __( 'Settings', 'simple-lms' ),
            __( 'Settings', 'simple-lms' ),
            'manage_options',
            'simple-lms'
        );
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
        global $post_type;

        // Enqueue on course edit screens and settings pages.
        $is_course_screen  = 'simple_lms_course' === $post_type;
        $is_settings_page  = 'toplevel_page_simple-lms' === $hook;

        if ( $is_course_screen || $is_settings_page ) {
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
                    'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'simple_lms_admin' ),
                    'i18n'     => array(
                        'confirmDelete' => __( 'Are you sure you want to delete this preset?', 'simple-lms' ),
                        'saving'        => __( 'Saving...', 'simple-lms' ),
                        'saved'         => __( 'Saved!', 'simple-lms' ),
                        'error'         => __( 'Error saving. Please try again.', 'simple-lms' ),
                    ),
                )
            );
        }

        // Enqueue code editor for templates tab.
        if ( $is_settings_page && isset( $_GET['tab'] ) && 'templates' === $_GET['tab'] ) {
            wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
        }
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

        $presets = get_option( 'simple_lms_shortcode_presets', array() );
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
}
