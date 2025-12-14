<?php
/**
 * General settings tab.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = get_option( 'simple_lms_settings', array() );
?>
<form method="post" action="options.php">
    <?php settings_fields( 'simple_lms_settings_group' ); ?>

    <h2><?php esc_html_e( 'General Settings', 'simple-lms' ); ?></h2>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="redirect_url"><?php esc_html_e( 'Redirect URL', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="redirect_url" name="simple_lms_settings[redirect_url]" value="<?php echo esc_attr( isset( $settings['redirect_url'] ) ? $settings['redirect_url'] : '/' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( '/ or https://example.com', 'simple-lms' ); ?>">
                <p class="description"><?php esc_html_e( 'Where to redirect users without access. Use relative path (e.g., /) or full URL (e.g., https://example.com). Can be overridden per course.', 'simple-lms' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="date_format"><?php esc_html_e( 'Date Format', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="date_format" name="simple_lms_settings[date_format]" value="<?php echo esc_attr( isset( $settings['date_format'] ) ? $settings['date_format'] : 'd.m.Y' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., d.m.Y', 'simple-lms' ); ?>">
                <p class="description">
                    <?php
                    printf(
                        /* translators: %s: link to PHP date format documentation */
                        esc_html__( 'PHP date format. See %s for reference.', 'simple-lms' ),
                        '<a href="https://www.php.net/manual/en/datetime.format.php" target="_blank">PHP Date Format</a>'
                    );
                    ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="product_status_filter"><?php esc_html_e( 'Product Status Filter', 'simple-lms' ); ?></label>
            </th>
            <td>
                <?php $product_status = isset( $settings['product_status_filter'] ) ? $settings['product_status_filter'] : 'publish'; ?>
                <select id="product_status_filter" name="simple_lms_settings[product_status_filter]">
                    <option value="publish" <?php selected( $product_status, 'publish' ); ?>><?php esc_html_e( 'Published only', 'simple-lms' ); ?></option>
                    <option value="any" <?php selected( $product_status, 'any' ); ?>><?php esc_html_e( 'All (including drafts and trash)', 'simple-lms' ); ?></option>
                </select>
                <p class="description"><?php esc_html_e( 'Which subscription products to show in the course access control.', 'simple-lms' ); ?></p>
            </td>
        </tr>
    </table>

    <h2><?php esc_html_e( 'Default Values for New Courses', 'simple-lms' ); ?></h2>
    <p class="description"><?php esc_html_e( 'These values will be pre-filled when creating new courses. Default taxonomy values can be set in their respective tabs.', 'simple-lms' ); ?></p>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="default_time_start"><?php esc_html_e( 'Default Time Range', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="time" id="default_time_start" name="simple_lms_settings[default_time_start]" value="<?php echo esc_attr( isset( $settings['default_time_start'] ) ? $settings['default_time_start'] : '' ); ?>" class="small-text">
                <span> - </span>
                <input type="time" id="default_time_end" name="simple_lms_settings[default_time_end]" value="<?php echo esc_attr( isset( $settings['default_time_end'] ) ? $settings['default_time_end'] : '' ); ?>" class="small-text">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="default_duration"><?php esc_html_e( 'Default Duration', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="default_duration" name="simple_lms_settings[default_duration]" value="<?php echo esc_attr( isset( $settings['default_duration'] ) ? $settings['default_duration'] : '' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., 5h', 'simple-lms' ); ?>">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="default_video_title"><?php esc_html_e( 'Default Video Title', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="default_video_title" name="simple_lms_settings[default_video_title]" value="<?php echo esc_attr( isset( $settings['default_video_title'] ) ? $settings['default_video_title'] : '' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Recording', 'simple-lms' ); ?>">
                <p class="description"><?php esc_html_e( 'Pre-filled title when adding a new video.', 'simple-lms' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="default_material_label"><?php esc_html_e( 'Default Material Label', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="default_material_label" name="simple_lms_settings[default_material_label]" value="<?php echo esc_attr( isset( $settings['default_material_label'] ) ? $settings['default_material_label'] : '' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Download', 'simple-lms' ); ?>">
                <p class="description"><?php esc_html_e( 'Pre-filled label when adding a new material.', 'simple-lms' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="default_live_link_label"><?php esc_html_e( 'Default Live Link Label', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="default_live_link_label" name="simple_lms_settings[default_live_link_label]" value="<?php echo esc_attr( isset( $settings['default_live_link_label'] ) ? $settings['default_live_link_label'] : '' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Join Zoom Meeting', 'simple-lms' ); ?>">
                <p class="description"><?php esc_html_e( 'Pre-filled label for live event link (e.g., Zoom, Teams).', 'simple-lms' ); ?></p>
            </td>
        </tr>
    </table>

    <?php submit_button(); ?>
</form>
