<?php
/**
 * Templates settings tab.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle template labels save.
if ( isset( $_POST['simple_lms_save_template_labels'] ) ) {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'simple_lms_template_labels' ) ) {
        wp_die( esc_html__( 'Security check failed.', 'simple-lms' ) );
    }

    if ( isset( $_POST['template_labels'] ) && is_array( $_POST['template_labels'] ) ) {
        $labels_to_save = array();
        foreach ( $_POST['template_labels'] as $key => $value ) {
            $labels_to_save[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
        }
        update_option( 'simple_lms_template_labels', $labels_to_save );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Template labels saved.', 'simple-lms' ) . '</p></div>';
    }
}

$default_template  = get_option( 'simple_lms_default_template', '' );
$status_templates  = get_option( 'simple_lms_status_templates', array() );
$statuses          = get_terms(
    array(
        'taxonomy'   => 'simple_lms_status',
        'hide_empty' => false,
    )
);

// Get template labels with defaults.
$template_labels = get_option( 'simple_lms_template_labels', array() );
$default_labels  = LMS_Admin::get_default_template_labels();
$labels          = wp_parse_args( $template_labels, $default_labels );

$placeholders = array(
    '{{LMS_TITLE}}'       => __( 'Course title', 'simple-lms' ),
    '{{LMS_DATE}}'        => __( 'Formatted course date', 'simple-lms' ),
    '{{LMS_TIME}}'        => __( 'Time range (e.g., 10:00 - 16:00)', 'simple-lms' ),
    '{{LMS_DURATION}}'    => __( 'Duration (e.g., 5 hours)', 'simple-lms' ),
    '{{LMS_LECTURER}}'    => __( 'Lecturer name', 'simple-lms' ),
    '{{LMS_VIDEOS}}'      => __( 'All videos (embedded)', 'simple-lms' ),
    '{{LMS_MATERIALS}}'   => __( 'All materials (links)', 'simple-lms' ),
    '{{LMS_CATEGORY}}'    => __( 'Primary category name', 'simple-lms' ),
    '{{LMS_TAGS}}'        => __( 'Course tags (comma-separated)', 'simple-lms' ),
    '{{LMS_STATUS}}'      => __( 'Course status', 'simple-lms' ),
    '{{LMS_CONTENT}}'     => __( 'Post editor content', 'simple-lms' ),
    '{{LMS_CERTIFICATE}}' => __( 'Certificate download form', 'simple-lms' ),
);

$conditionals = array(
    '{{#IF_DATE}}...{{/IF_DATE}}'               => __( 'Shows if date is set', 'simple-lms' ),
    '{{#IF_TIME}}...{{/IF_TIME}}'               => __( 'Shows if time range is set', 'simple-lms' ),
    '{{#IF_DURATION}}...{{/IF_DURATION}}'       => __( 'Shows if duration is set', 'simple-lms' ),
    '{{#IF_LECTURER}}...{{/IF_LECTURER}}'       => __( 'Shows if lecturer is set', 'simple-lms' ),
    '{{#IF_VIDEOS}}...{{/IF_VIDEOS}}'           => __( 'Shows if videos exist', 'simple-lms' ),
    '{{#IF_MATERIALS}}...{{/IF_MATERIALS}}'     => __( 'Shows if materials exist', 'simple-lms' ),
    '{{#IF_CATEGORY}}...{{/IF_CATEGORY}}'       => __( 'Shows if category is set', 'simple-lms' ),
    '{{#IF_TAGS}}...{{/IF_TAGS}}'               => __( 'Shows if tags exist', 'simple-lms' ),
    '{{#IF_STATUS}}...{{/IF_STATUS}}'           => __( 'Shows if status is set', 'simple-lms' ),
    '{{#IF_CONTENT}}...{{/IF_CONTENT}}'         => __( 'Shows if content exists', 'simple-lms' ),
    '{{#IF_CERTIFICATE}}...{{/IF_CERTIFICATE}}' => __( 'Shows if certificate is available', 'simple-lms' ),
);
?>

<h2><?php esc_html_e( 'Default Template Labels', 'simple-lms' ); ?></h2>
<p class="description"><?php esc_html_e( 'These labels are used in the built-in default template. Changing these will affect the template when reset to default.', 'simple-lms' ); ?></p>

<form method="post" action="">
    <?php wp_nonce_field( 'simple_lms_template_labels' ); ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="label_date"><?php esc_html_e( 'Date label', 'simple-lms' ); ?></label></th>
            <td><input type="text" id="label_date" name="template_labels[date]" value="<?php echo esc_attr( $labels['date'] ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="label_time"><?php esc_html_e( 'Time label', 'simple-lms' ); ?></label></th>
            <td><input type="text" id="label_time" name="template_labels[time]" value="<?php echo esc_attr( $labels['time'] ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th scope="row"><label for="label_lecturer"><?php esc_html_e( 'Lecturer label', 'simple-lms' ); ?></label></th>
            <td><input type="text" id="label_lecturer" name="template_labels[lecturer]" value="<?php echo esc_attr( $labels['lecturer'] ); ?>" class="regular-text"></td>
        </tr>
    </table>
    <p class="submit">
        <button type="submit" name="simple_lms_save_template_labels" class="button button-primary">
            <?php esc_html_e( 'Save Labels', 'simple-lms' ); ?>
        </button>
    </p>
</form>

<hr>

<form method="post" action="options.php">
    <?php settings_fields( 'simple_lms_templates_group' ); ?>

    <div class="simple-lms-template-reference">
        <h3><?php esc_html_e( 'Available Placeholders', 'simple-lms' ); ?></h3>
        <div class="placeholder-grid">
            <div class="placeholder-column">
                <h4><?php esc_html_e( 'Content Placeholders', 'simple-lms' ); ?></h4>
                <table class="widefat striped">
                    <?php foreach ( $placeholders as $tag => $desc ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( $tag ); ?></code></td>
                        <td><?php echo esc_html( $desc ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <div class="placeholder-column">
                <h4><?php esc_html_e( 'Conditional Blocks', 'simple-lms' ); ?></h4>
                <table class="widefat striped">
                    <?php foreach ( $conditionals as $tag => $desc ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( $tag ); ?></code></td>
                        <td><?php echo esc_html( $desc ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <h2><?php esc_html_e( 'Default Template', 'simple-lms' ); ?></h2>
    <p class="description"><?php esc_html_e( 'This template is used for courses without a status-specific template.', 'simple-lms' ); ?></p>

    <div class="simple-lms-template-editor">
        <textarea id="simple_lms_default_template" name="simple_lms_default_template" rows="20" class="large-text code"><?php echo esc_textarea( $default_template ); ?></textarea>
    </div>

    <p class="simple-lms-reset-template">
        <button type="button" id="simple-lms-reset-template-btn" class="button button-secondary">
            <?php esc_html_e( 'Reset to Default', 'simple-lms' ); ?>
        </button>
        <span class="description"><?php esc_html_e( 'Restore the built-in default template.', 'simple-lms' ); ?></span>
    </p>

    <?php if ( ! empty( $statuses ) && ! is_wp_error( $statuses ) ) : ?>
    <h2><?php esc_html_e( 'Status-Specific Templates', 'simple-lms' ); ?></h2>
    <p class="description"><?php esc_html_e( 'Override the default template for specific course statuses. Leave empty to use the default template.', 'simple-lms' ); ?></p>

    <div class="status-templates-accordion">
        <?php foreach ( $statuses as $status ) : ?>
        <div class="status-template-item">
            <h3 class="status-template-header">
                <button type="button" class="handlediv" aria-expanded="false">
                    <span class="toggle-indicator" aria-hidden="true"></span>
                </button>
                <span><?php echo esc_html( $status->name ); ?></span>
                <?php if ( ! empty( $status_templates[ $status->term_id ] ) ) : ?>
                <span class="template-active-badge"><?php esc_html_e( 'Custom', 'simple-lms' ); ?></span>
                <?php endif; ?>
            </h3>
            <div class="status-template-content" style="display: none;">
                <textarea name="simple_lms_status_templates[<?php echo esc_attr( $status->term_id ); ?>]" rows="15" class="large-text code"><?php echo esc_textarea( isset( $status_templates[ $status->term_id ] ) ? $status_templates[ $status->term_id ] : '' ); ?></textarea>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php submit_button(); ?>
</form>

<script>
jQuery(document).ready(function($) {
    var editorInstance = null;

    // Toggle status template sections.
    $('.status-template-header').on('click', function() {
        var $content = $(this).next('.status-template-content');
        var $toggle = $(this).find('.handlediv');

        $content.slideToggle(200);
        $toggle.attr('aria-expanded', $content.is(':visible'));
    });

    // Initialize code editor for default template.
    if (typeof wp !== 'undefined' && wp.codeEditor) {
        editorInstance = wp.codeEditor.initialize($('#simple_lms_default_template'), {
            codemirror: {
                mode: 'htmlmixed',
                lineNumbers: true,
                lineWrapping: true
            }
        });
    }

    // Reset default template button.
    $('#simple-lms-reset-template-btn').on('click', function() {
        if (!confirm(simpleLMS.i18n.confirmResetTemplate)) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: simpleLMS.ajaxUrl,
            type: 'POST',
            data: {
                action: 'simple_lms_reset_default_template',
                nonce: simpleLMS.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update textarea value.
                    if (editorInstance && editorInstance.codemirror) {
                        editorInstance.codemirror.setValue(response.data.template);
                    } else {
                        $('#simple_lms_default_template').val(response.data.template);
                    }
                    alert(simpleLMS.i18n.templateReset);
                } else {
                    alert(response.data.message || simpleLMS.i18n.error);
                }
            },
            error: function() {
                alert(simpleLMS.i18n.error);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
