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

// Handle form submission.
if ( isset( $_POST['simple_lms_save_certificate_settings'] ) ) {
    // Verify nonce.
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'simple_lms_certificate_settings' ) ) {
        wp_die( esc_html__( 'Security check failed.', 'simple-lms' ) );
    }

    // Save settings.
    if ( isset( $_POST['certificate_logo_url'] ) ) {
        update_option( 'simple_lms_certificate_logo_url', esc_url_raw( wp_unslash( $_POST['certificate_logo_url'] ) ) );
    }
    if ( isset( $_POST['certificate_signature_url'] ) ) {
        update_option( 'simple_lms_certificate_signature_url', esc_url_raw( wp_unslash( $_POST['certificate_signature_url'] ) ) );
    }
    if ( isset( $_POST['certificate_issuer_company'] ) ) {
        update_option( 'simple_lms_certificate_issuer_company', sanitize_text_field( wp_unslash( $_POST['certificate_issuer_company'] ) ) );
    }
    if ( isset( $_POST['certificate_issuer_name'] ) ) {
        update_option( 'simple_lms_certificate_issuer_name', sanitize_text_field( wp_unslash( $_POST['certificate_issuer_name'] ) ) );
    }
    if ( isset( $_POST['certificate_issuer_title'] ) ) {
        update_option( 'simple_lms_certificate_issuer_title', sanitize_text_field( wp_unslash( $_POST['certificate_issuer_title'] ) ) );
    }
    if ( isset( $_POST['certificate_template'] ) ) {
        update_option( 'simple_lms_certificate_template', wp_kses_post( wp_unslash( $_POST['certificate_template'] ) ) );
    }

    echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'simple-lms' ) . '</p></div>';
}

// Handle reset template.
if ( isset( $_POST['simple_lms_reset_certificate_template'] ) ) {
    if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'simple_lms_certificate_settings' ) ) {
        update_option( 'simple_lms_certificate_template', LMS_Certificates::get_default_certificate_template() );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Template reset to default.', 'simple-lms' ) . '</p></div>';
    }
}

// Get current values.
$logo_url       = get_option( 'simple_lms_certificate_logo_url', '' );
$signature_url  = get_option( 'simple_lms_certificate_signature_url', '' );
$issuer_company = get_option( 'simple_lms_certificate_issuer_company', '' );
$issuer_name    = get_option( 'simple_lms_certificate_issuer_name', '' );
$issuer_title   = get_option( 'simple_lms_certificate_issuer_title', '' );
$template       = get_option( 'simple_lms_certificate_template', LMS_Certificates::get_default_certificate_template() );
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

    <h2><?php esc_html_e( 'Issuer', 'simple-lms' ); ?></h2>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="certificate_issuer_company"><?php esc_html_e( 'Company', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="certificate_issuer_company" name="certificate_issuer_company" value="<?php echo esc_attr( $issuer_company ); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="certificate_issuer_name"><?php esc_html_e( 'Signatory', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="certificate_issuer_name" name="certificate_issuer_name" value="<?php echo esc_attr( $issuer_name ); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="certificate_issuer_title"><?php esc_html_e( 'Title', 'simple-lms' ); ?></label>
            </th>
            <td>
                <input type="text" id="certificate_issuer_title" name="certificate_issuer_title" value="<?php echo esc_attr( $issuer_title ); ?>" class="regular-text">
                <p class="description"><?php esc_html_e( 'E.g. CEO, Director, etc.', 'simple-lms' ); ?></p>
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
            <tr><td><code>{{CERT_ISSUER_COMPANY}}</code></td><td><?php esc_html_e( 'Company name', 'simple-lms' ); ?></td></tr>
            <tr><td><code>{{CERT_ISSUER_NAME}}</code></td><td><?php esc_html_e( 'Signatory', 'simple-lms' ); ?></td></tr>
            <tr><td><code>{{CERT_ISSUER_TITLE}}</code></td><td><?php esc_html_e( 'Signatory title', 'simple-lms' ); ?></td></tr>
        </tbody>
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
