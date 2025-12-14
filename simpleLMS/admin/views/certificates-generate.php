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
    <h1><?php esc_html_e( 'Generuj certyfikat', 'simple-lms' ); ?></h1>

    <p class="description">
        <?php esc_html_e( 'Wypelnij formularz, aby wygenerowac certyfikat ukonczenia szkolenia.', 'simple-lms' ); ?>
    </p>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" target="_blank">
        <?php wp_nonce_field( 'simple_lms_generate_certificate' ); ?>
        <input type="hidden" name="action" value="simple_lms_generate_certificate">

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="user_name"><?php esc_html_e( 'Imie i nazwisko', 'simple-lms' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="user_name" name="user_name" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="course_title"><?php esc_html_e( 'Tytul kursu', 'simple-lms' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="course_title" name="course_title" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="trainer_name"><?php esc_html_e( 'Wykladowca', 'simple-lms' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="trainer_name" name="trainer_name" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="course_duration"><?php esc_html_e( 'Czas trwania', 'simple-lms' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="course_duration" name="course_duration" class="regular-text" required placeholder="np. 6h">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="completion_date"><?php esc_html_e( 'Data ukonczenia', 'simple-lms' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="date" id="completion_date" name="completion_date" value="<?php echo esc_attr( $today_date ); ?>" max="<?php echo esc_attr( $today_date ); ?>" required>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php esc_html_e( 'Generuj certyfikat', 'simple-lms' ); ?>
            </button>
        </p>
    </form>

    <hr>

    <h2><?php esc_html_e( 'Ustawienia certyfikatu', 'simple-lms' ); ?></h2>
    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=certificates' ) ); ?>" class="button">
            <?php esc_html_e( 'Przejdz do ustawien certyfikatu', 'simple-lms' ); ?>
        </a>
    </p>
</div>

<style>
.simple-lms-certificates .required {
    color: #dc3232;
}
</style>
