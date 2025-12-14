<?php
/**
 * Certificates settings tab.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get admin notices from LMS_Admin.
$lms     = Simple_LMS::instance();
$notices = $lms->admin->get_admin_notices();

// Display notices.
foreach ( $notices as $notice ) {
    echo '<div class="notice notice-' . esc_attr( $notice['type'] ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>';
}

// Get current values.
$logo_url      = get_option( 'simple_lms_certificate_logo_url', '' );
$signature_url = get_option( 'simple_lms_certificate_signature_url', '' );
$template      = get_option( 'simple_lms_certificate_template', LMS_Certificates::get_default_certificate_template() );

// Get frontend labels with defaults.
$frontend_labels = get_option( 'simple_lms_certificate_labels', array() );
$default_labels  = LMS_Certificates::get_default_labels();
$labels          = wp_parse_args( $frontend_labels, $default_labels );
?>

<form method="post" action="">
    <?php wp_nonce_field( 'simple_lms_certificate_settings' ); ?>

    <h2><?php esc_html_e( 'Images', 'simple-lms' ); ?></h2>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="certificate_logo_url"><?php esc_html_e( 'Logo', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="certificate_logo_url" name="certificate_logo_url" value="<?php echo esc_url( $logo_url ); ?>" class="regular-text">
                <button type="button" class="button simple-lms-media-upload" data-target="certificate_logo_url"><?php esc_html_e( 'Select image', 'simple-lms' ); ?></button>
                <?php if ( $logo_url ) : ?>
                    <br><br>
                    <img src="<?php echo esc_url( $logo_url ); ?>" style="max-width: 200px; height: auto;">
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="certificate_signature_url"><?php esc_html_e( 'Signature', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="certificate_signature_url" name="certificate_signature_url" value="<?php echo esc_url( $signature_url ); ?>" class="regular-text">
                <button type="button" class="button simple-lms-media-upload" data-target="certificate_signature_url"><?php esc_html_e( 'Select image', 'simple-lms' ); ?></button>
                <?php if ( $signature_url ) : ?>
                    <br><br>
                    <img src="<?php echo esc_url( $signature_url ); ?>" style="max-width: 150px; height: auto;">
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <h2><?php esc_html_e( 'Certificate template', 'simple-lms' ); ?></h2>

    <p>
        <textarea id="certificate_template" name="certificate_template" rows="15" class="large-text code"><?php echo esc_textarea( $template ); ?></textarea>
    </p>

    <p>
        <button type="submit" name="simple_lms_reset_certificate_template" class="button" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to reset the template to default?', 'simple-lms' ); ?>');">
            <?php esc_html_e( 'Reset to default', 'simple-lms' ); ?>
        </button>
    </p>

    <h3><?php esc_html_e( 'Available placeholders', 'simple-lms' ); ?></h3>

    <table class="widefat" style="max-width: 600px;">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Placeholder', 'simple-lms' ); ?></th>
                <th><?php esc_html_e( 'Description', 'simple-lms' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr><td><code>{{CERT_USER_NAME}}</code></td><td><?php esc_html_e( 'Full name', 'simple-lms' ); ?></td></tr>
            <tr><td><code>{{CERT_COURSE_TITLE}}</code></td><td><?php esc_html_e( 'Course title', 'simple-lms' ); ?></td></tr>
            <tr><td><code>{{CERT_LECTURER}}</code></td><td><?php esc_html_e( 'Lecturer', 'simple-lms' ); ?></td></tr>
            <tr><td><code>{{CERT_DURATION}}</code></td><td><?php esc_html_e( 'Duration', 'simple-lms' ); ?></td></tr>
            <tr><td><code>{{CERT_COMPLETION_DATE}}</code></td><td><?php esc_html_e( 'Completion date', 'simple-lms' ); ?></td></tr>
            <tr><td><code>{{CERT_LOGO_URL}}</code></td><td><?php esc_html_e( 'Logo URL', 'simple-lms' ); ?></td></tr>
            <tr><td><code>{{CERT_SIGNATURE_URL}}</code></td><td><?php esc_html_e( 'Signature URL', 'simple-lms' ); ?></td></tr>
        </tbody>
    </table>

    <h2><?php esc_html_e( 'Frontend Labels', 'simple-lms' ); ?></h2>
    <p class="description"><?php esc_html_e( 'Customize the labels displayed on the frontend for certificate-related elements.', 'simple-lms' ); ?></p>

    <h3><?php esc_html_e( 'Shortcode Table Headers', 'simple-lms' ); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="label_table_course"><?php esc_html_e( 'Course column', 'simple-lms' ); ?></label></th>
            <td><input type="text" id="label_table_course" name="certificate_labels[table_course]" value="<?php echo esc_attr( $labels['table_course'] ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="label_table_lecturer"><?php esc_html_e( 'Lecturer column', 'simple-lms' ); ?></label></th>
            <td><input type="text" id="label_table_lecturer" name="certificate_labels[table_lecturer]" value="<?php echo esc_attr( $labels['table_lecturer'] ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="label_table_date"><?php esc_html_e( 'Date column', 'simple-lms' ); ?></label></th>
            <td><input type="text" id="label_table_date" name="certificate_labels[table_date]" value="<?php echo esc_attr( $labels['table_date'] ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="label_table_certificate"><?php esc_html_e( 'Certificate column', 'simple-lms' ); ?></label></th>
            <td><input type="text" id="label_table_certificate" name="certificate_labels[table_certificate]" value="<?php echo esc_attr( $labels['table_certificate'] ); ?>" class="regular-text"></td>
        </tr>
    </table>

    <h3><?php esc_html_e( 'Buttons', 'simple-lms' ); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="label_btn_download"><?php esc_html_e( 'Download button (table)', 'simple-lms' ); ?></label></th>
            <td><input type="text" id="label_btn_download" name="certificate_labels[btn_download]" value="<?php echo esc_attr( $labels['btn_download'] ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="label_btn_download_cert"><?php esc_html_e( 'Download button (course page)', 'simple-lms' ); ?></label></th>
            <td><input type="text" id="label_btn_download_cert" name="certificate_labels[btn_download_certificate]" value="<?php echo esc_attr( $labels['btn_download_certificate'] ); ?>" class="regular-text"></td>
        </tr>
    </table>

    <h3><?php esc_html_e( 'Messages', 'simple-lms' ); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="label_msg_login"><?php esc_html_e( 'Login required', 'simple-lms' ); ?></label></th>
            <td><input type="text" id="label_msg_login" name="certificate_labels[msg_login_required]" value="<?php echo esc_attr( $labels['msg_login_required'] ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="label_msg_none"><?php esc_html_e( 'No certificates', 'simple-lms' ); ?></label></th>
            <td><input type="text" id="label_msg_none" name="certificate_labels[msg_no_certificates]" value="<?php echo esc_attr( $labels['msg_no_certificates'] ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="label_msg_after"><?php esc_html_e( 'Available after course (table)', 'simple-lms' ); ?></label></th>
            <td><input type="text" id="label_msg_after" name="certificate_labels[msg_available_after]" value="<?php echo esc_attr( $labels['msg_available_after'] ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="label_msg_after_long"><?php esc_html_e( 'Available after course (course page)', 'simple-lms' ); ?></label></th>
            <td><input type="text" id="label_msg_after_long" name="certificate_labels[msg_available_after_long]" value="<?php echo esc_attr( $labels['msg_available_after_long'] ); ?>" class="regular-text"></td>
        </tr>
    </table>

    <h3><?php esc_html_e( 'PDF Settings', 'simple-lms' ); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="label_pdf_filename"><?php esc_html_e( 'PDF filename', 'simple-lms' ); ?></label></th>
            <td>
                <input type="text" id="label_pdf_filename" name="certificate_labels[pdf_filename]" value="<?php echo esc_attr( $labels['pdf_filename'] ); ?>" class="regular-text">
                <p class="description"><?php esc_html_e( 'Without .pdf extension', 'simple-lms' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="label_pdf_title_prefix"><?php esc_html_e( 'PDF title prefix', 'simple-lms' ); ?></label></th>
            <td>
                <input type="text" id="label_pdf_title_prefix" name="certificate_labels[pdf_title_prefix]" value="<?php echo esc_attr( $labels['pdf_title_prefix'] ); ?>" class="regular-text">
                <p class="description"><?php esc_html_e( 'Prefix before course title in PDF metadata', 'simple-lms' ); ?></p>
            </td>
        </tr>
    </table>

    <p class="submit">
        <button type="submit" name="simple_lms_save_certificate_settings" class="button button-primary">
            <?php esc_html_e( 'Save settings', 'simple-lms' ); ?>
        </button>
    </p>
</form>

<script>
jQuery(document).ready(function($) {
    $('.simple-lms-media-upload').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var targetId = button.data('target');

        var mediaUploader = wp.media({
            title: '<?php esc_html_e( 'Select image', 'simple-lms' ); ?>',
            button: {
                text: '<?php esc_html_e( 'Use this image', 'simple-lms' ); ?>'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#' + targetId).val(attachment.url);
        });

        mediaUploader.open();
    });
});
</script>
