<?php
/**
 * Import page view.
 *
 * @package SimpleLMS_Import
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$courses  = $this->get_old_courses();
$statuses = $this->get_available_statuses();
$count    = count( $courses );

// Messages.
$imported = isset( $_GET['imported'] ) ? intval( $_GET['imported'] ) : 0;
$errors   = isset( $_GET['errors'] ) ? intval( $_GET['errors'] ) : 0;
$error    = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : '';
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Import Courses from Course Manager', 'simple-lms' ); ?></h1>

    <?php if ( $imported > 0 ) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                printf(
                    /* translators: %d: number of imported courses */
                    esc_html__( 'Successfully imported %d courses.', 'simple-lms' ),
                    $imported
                );
                if ( $errors > 0 ) {
                    printf(
                        /* translators: %d: number of errors */
                        ' ' . esc_html__( '%d courses failed to import.', 'simple-lms' ),
                        $errors
                    );
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( $error === 'no_status' ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( 'Please select a status for imported courses.', 'simple-lms' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( $error === 'no_courses' ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( 'Please select at least one course to import.', 'simple-lms' ); ?></p>
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 100%; padding: 20px; margin-top: 20px;">
        <h2><?php esc_html_e( 'Import Summary', 'simple-lms' ); ?></h2>
        <p>
            <?php
            printf(
                /* translators: %d: number of courses */
                esc_html__( 'Found %d courses in Course Manager database.', 'simple-lms' ),
                $count
            );
            ?>
        </p>

        <?php if ( $count > 0 ) : ?>

            <?php if ( empty( $statuses ) ) : ?>
                <div class="notice notice-warning inline">
                    <p>
                        <?php esc_html_e( 'No statuses found in SimpleLMS. Please create at least one status in SimpleLMS settings before importing.', 'simple-lms' ); ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=statuses' ) ); ?>">
                            <?php esc_html_e( 'Go to Settings', 'simple-lms' ); ?>
                        </a>
                    </p>
                </div>
            <?php else : ?>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="import-form">
                    <?php wp_nonce_field( 'simple_lms_import_action' ); ?>
                    <input type="hidden" name="action" value="simple_lms_import">

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="import_status"><?php esc_html_e( 'Status for imported courses', 'simple-lms' ); ?></label>
                            </th>
                            <td>
                                <select name="import_status" id="import_status" required>
                                    <option value=""><?php esc_html_e( '-- Select Status --', 'simple-lms' ); ?></option>
                                    <?php foreach ( $statuses as $status ) : ?>
                                        <option value="<?php echo esc_attr( $status->term_id ); ?>">
                                            <?php echo esc_html( $status->name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <h3><?php esc_html_e( 'Select Courses to Import', 'simple-lms' ); ?></h3>

                    <p>
                        <button type="button" class="button" id="select-all"><?php esc_html_e( 'Select All', 'simple-lms' ); ?></button>
                        <button type="button" class="button" id="select-none"><?php esc_html_e( 'Select None', 'simple-lms' ); ?></button>
                        <span id="selected-count" style="margin-left: 15px; font-weight: bold;">0 <?php esc_html_e( 'selected', 'simple-lms' ); ?></span>
                    </p>

                    <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">
                                    <input type="checkbox" id="select-all-checkbox">
                                </th>
                                <th style="width: 28%;"><?php esc_html_e( 'Title', 'simple-lms' ); ?></th>
                                <th style="width: 14%;"><?php esc_html_e( 'Lecturer', 'simple-lms' ); ?></th>
                                <th style="width: 11%;"><?php esc_html_e( 'Date', 'simple-lms' ); ?></th>
                                <th style="width: 10%;"><?php esc_html_e( 'Time', 'simple-lms' ); ?></th>
                                <th style="width: 8%;"><?php esc_html_e( 'Duration', 'simple-lms' ); ?></th>
                                <th style="width: 10%;"><?php esc_html_e( 'Videos', 'simple-lms' ); ?></th>
                                <th style="width: 10%;"><?php esc_html_e( 'Materials', 'simple-lms' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $courses as $course ) : ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <input type="checkbox" name="course_ids[]" value="<?php echo esc_attr( $course['id'] ); ?>" class="course-checkbox">
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html( $course['title'] ); ?></strong>
                                    </td>
                                    <td><?php echo esc_html( $course['trainer_name'] ); ?></td>
                                    <td><?php echo esc_html( $course['original_date'] ); ?></td>
                                    <td><?php echo esc_html( $course['original_time'] ); ?></td>
                                    <td><?php echo esc_html( $course['duration'] ); ?></td>
                                    <td>
                                        <?php
                                        $video_count = is_array( $course['video_links'] ) ? count( $course['video_links'] ) : 0;
                                        echo esc_html( $video_count );
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $material_count = is_array( $course['training_material_links'] ) ? count( $course['training_material_links'] ) : 0;
                                        echo esc_html( $material_count );
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p class="submit" style="margin-top: 20px;">
                        <button type="submit" class="button button-primary button-large" id="import-button">
                            <?php esc_html_e( 'Import Selected Courses', 'simple-lms' ); ?>
                        </button>
                    </p>
                </form>

                <script>
                (function() {
                    var checkboxes = document.querySelectorAll('.course-checkbox');
                    var selectAllCheckbox = document.getElementById('select-all-checkbox');
                    var selectAllBtn = document.getElementById('select-all');
                    var selectNoneBtn = document.getElementById('select-none');
                    var countDisplay = document.getElementById('selected-count');

                    function updateCount() {
                        var checked = document.querySelectorAll('.course-checkbox:checked').length;
                        countDisplay.textContent = checked + ' <?php esc_html_e( 'selected', 'simple-lms' ); ?>';
                    }

                    function setAll(checked) {
                        checkboxes.forEach(function(cb) { cb.checked = checked; });
                        selectAllCheckbox.checked = checked;
                        updateCount();
                    }

                    selectAllBtn.addEventListener('click', function() { setAll(true); });
                    selectNoneBtn.addEventListener('click', function() { setAll(false); });

                    selectAllCheckbox.addEventListener('change', function() {
                        setAll(this.checked);
                    });

                    checkboxes.forEach(function(cb) {
                        cb.addEventListener('change', function() {
                            updateCount();
                            var allChecked = document.querySelectorAll('.course-checkbox:checked').length === checkboxes.length;
                            selectAllCheckbox.checked = allChecked;
                        });
                    });

                    document.getElementById('import-form').addEventListener('submit', function(e) {
                        var checked = document.querySelectorAll('.course-checkbox:checked').length;
                        if (checked === 0) {
                            alert('<?php esc_html_e( 'Please select at least one course to import.', 'simple-lms' ); ?>');
                            e.preventDefault();
                            return false;
                        }
                        return confirm('<?php esc_html_e( 'Are you sure you want to import the selected courses? This action cannot be undone.', 'simple-lms' ); ?>');
                    });
                })();
                </script>

            <?php endif; ?>

        <?php else : ?>
            <p><?php esc_html_e( 'No courses found to import.', 'simple-lms' ); ?></p>
        <?php endif; ?>
    </div>
</div>
