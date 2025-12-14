<?php
/**
 * Admin certificate generation page.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$today_date = date( 'Y-m-d' );
?>
<div class="wrap simple-lms-certificates">
    <h1><?php esc_html_e( 'Generate Certificate', 'simple-lms' ); ?></h1>

    <p class="description">
        <?php esc_html_e( 'Fill in the form to generate a course completion certificate.', 'simple-lms' ); ?>
    </p>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" target="_blank">
        <?php wp_nonce_field( 'simple_lms_generate_certificate' ); ?>
        <input type="hidden" name="action" value="simple_lms_generate_certificate">

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="user_name"><?php esc_html_e( 'Full name', 'simple-lms' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="user_name" name="user_name" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="course_title"><?php esc_html_e( 'Course title', 'simple-lms' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="course_title" name="course_title" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="trainer_name"><?php esc_html_e( 'Lecturer', 'simple-lms' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="trainer_name" name="trainer_name" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="course_duration"><?php esc_html_e( 'Duration', 'simple-lms' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="course_duration" name="course_duration" class="regular-text" required placeholder="e.g. 6h">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="completion_date"><?php esc_html_e( 'Completion date', 'simple-lms' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="date" id="completion_date" name="completion_date" value="<?php echo esc_attr( $today_date ); ?>" max="<?php echo esc_attr( $today_date ); ?>" required>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php esc_html_e( 'Generate Certificate', 'simple-lms' ); ?>
            </button>
        </p>
    </form>

    <hr>

    <h2><?php esc_html_e( 'Certificate Settings', 'simple-lms' ); ?></h2>
    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=certificates' ) ); ?>" class="button">
            <?php esc_html_e( 'Go to certificate settings', 'simple-lms' ); ?>
        </a>
    </p>
</div>

<style>
.simple-lms-certificates .required {
    color: #dc3232;
}
</style>
