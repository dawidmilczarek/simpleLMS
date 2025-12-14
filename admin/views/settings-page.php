<?php
/**
 * Settings page template.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
?>
<div class="wrap simple-lms-settings">
    <h1><?php esc_html_e( 'simpleLMS Settings', 'simple-lms' ); ?></h1>

    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=general' ) ); ?>" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'General', 'simple-lms' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=templates' ) ); ?>" class="nav-tab <?php echo 'templates' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Templates', 'simple-lms' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=shortcodes' ) ); ?>" class="nav-tab <?php echo 'shortcodes' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Shortcodes', 'simple-lms' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=categories' ) ); ?>" class="nav-tab <?php echo 'categories' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Categories', 'simple-lms' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=tags' ) ); ?>" class="nav-tab <?php echo 'tags' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Tags', 'simple-lms' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=statuses' ) ); ?>" class="nav-tab <?php echo 'statuses' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Statuses', 'simple-lms' ); ?>
        </a>
    </nav>

    <div class="tab-content">
        <?php
        switch ( $active_tab ) {
            case 'templates':
                include SIMPLE_LMS_PLUGIN_DIR . 'admin/views/tab-templates.php';
                break;
            case 'shortcodes':
                include SIMPLE_LMS_PLUGIN_DIR . 'admin/views/tab-shortcodes.php';
                break;
            case 'categories':
            case 'tags':
            case 'statuses':
                include SIMPLE_LMS_PLUGIN_DIR . 'admin/views/tab-taxonomy.php';
                break;
            default:
                include SIMPLE_LMS_PLUGIN_DIR . 'admin/views/tab-general.php';
                break;
        }
        ?>
    </div>
</div>
