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
$statuses = get_terms(
    array(
        'taxonomy'   => 'simple_lms_status',
        'hide_empty' => false,
    )
);
?>
<form method="post" action="options.php">
    <?php settings_fields( 'simple_lms_settings_group' ); ?>

    <h2><?php esc_html_e( 'General Settings', 'simple-lms' ); ?></h2>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="redirect_url"><?php esc_html_e( 'Default Redirect URL', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="redirect_url" name="simple_lms_settings[redirect_url]" value="<?php echo esc_attr( isset( $settings['redirect_url'] ) ? $settings['redirect_url'] : '/sklep/' ); ?>" class="regular-text">
                <p class="description"><?php esc_html_e( 'Where to redirect users without access to a course.', 'simple-lms' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="date_format"><?php esc_html_e( 'Date Format', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="date_format" name="simple_lms_settings[date_format]" value="<?php echo esc_attr( isset( $settings['date_format'] ) ? $settings['date_format'] : 'd.m.Y' ); ?>" class="regular-text">
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
    </table>

    <h2><?php esc_html_e( 'Default Values for New Courses', 'simple-lms' ); ?></h2>
    <p class="description"><?php esc_html_e( 'These values will be pre-filled when creating new courses.', 'simple-lms' ); ?></p>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="default_lecturer"><?php esc_html_e( 'Default Lecturer', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="default_lecturer" name="simple_lms_settings[default_lecturer]" value="<?php echo esc_attr( isset( $settings['default_lecturer'] ) ? $settings['default_lecturer'] : '' ); ?>" class="regular-text">
            </td>
        </tr>
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
                <input type="text" id="default_video_title" name="simple_lms_settings[default_video_title]" value="<?php echo esc_attr( isset( $settings['default_video_title'] ) ? $settings['default_video_title'] : '' ); ?>" class="regular-text">
                <p class="description"><?php esc_html_e( 'Pre-filled title when adding a new video.', 'simple-lms' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="default_material_label"><?php esc_html_e( 'Default Material Label', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="default_material_label" name="simple_lms_settings[default_material_label]" value="<?php echo esc_attr( isset( $settings['default_material_label'] ) ? $settings['default_material_label'] : '' ); ?>" class="regular-text">
                <p class="description"><?php esc_html_e( 'Pre-filled label when adding a new material.', 'simple-lms' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="default_status"><?php esc_html_e( 'Default Status', 'simple-lms' ); ?></label>
            </th>
            <td>
                <select id="default_status" name="simple_lms_settings[default_status]">
                    <option value=""><?php esc_html_e( '— None —', 'simple-lms' ); ?></option>
                    <?php
                    if ( ! empty( $statuses ) && ! is_wp_error( $statuses ) ) {
                        foreach ( $statuses as $status ) {
                            $selected = isset( $settings['default_status'] ) && $settings['default_status'] === $status->slug ? 'selected' : '';
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr( $status->slug ),
                                esc_attr( $selected ),
                                esc_html( $status->name )
                            );
                        }
                    }
                    ?>
                </select>
                <p class="description"><?php esc_html_e( 'Pre-selected status for new courses.', 'simple-lms' ); ?></p>
            </td>
        </tr>
    </table>

    <?php submit_button(); ?>
</form>
