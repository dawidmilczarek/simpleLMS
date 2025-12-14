<?php
/**
 * Course form page.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get course data if editing.
$course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
$is_edit   = $course_id > 0;
$course    = $is_edit ? get_post( $course_id ) : null;

if ( $is_edit && ( ! $course || 'simple_lms_course' !== $course->post_type ) ) {
    wp_die( esc_html__( 'Course not found.', 'simple-lms' ) );
}

// Get settings for defaults.
$settings = get_option( 'simple_lms_settings', array() );

// Get course meta.
$course_title   = $is_edit ? $course->post_title : '';
$course_content = $is_edit ? $course->post_content : '';
$post_status    = $is_edit ? $course->post_status : 'publish';
$date           = $is_edit ? get_post_meta( $course_id, '_simple_lms_date', true ) : '';
$time_start     = $is_edit ? get_post_meta( $course_id, '_simple_lms_time_start', true ) : ( $settings['default_time_start'] ?? '' );
$time_end       = $is_edit ? get_post_meta( $course_id, '_simple_lms_time_end', true ) : ( $settings['default_time_end'] ?? '' );
$duration       = $is_edit ? get_post_meta( $course_id, '_simple_lms_duration', true ) : ( $settings['default_duration'] ?? '' );
$lecturer       = $is_edit ? get_post_meta( $course_id, '_simple_lms_lecturer', true ) : ( $settings['default_lecturer'] ?? '' );
$videos         = $is_edit ? get_post_meta( $course_id, '_simple_lms_videos', true ) : array();
$materials      = $is_edit ? get_post_meta( $course_id, '_simple_lms_materials', true ) : array();
$memberships    = $is_edit ? get_post_meta( $course_id, '_simple_lms_access_memberships', true ) : array();
$products       = $is_edit ? get_post_meta( $course_id, '_simple_lms_access_products', true ) : array();
$redirect_url   = $is_edit ? get_post_meta( $course_id, '_simple_lms_redirect_url', true ) : '';

$videos    = is_array( $videos ) ? $videos : array();
$materials = is_array( $materials ) ? $materials : array();
$memberships = is_array( $memberships ) ? $memberships : array();
$products  = is_array( $products ) ? $products : array();

// Get taxonomies.
$categories      = get_terms( array( 'taxonomy' => 'simple_lms_category', 'hide_empty' => false ) );
$statuses        = get_terms( array( 'taxonomy' => 'simple_lms_status', 'hide_empty' => false ) );
$course_cats     = $is_edit ? wp_get_post_terms( $course_id, 'simple_lms_category', array( 'fields' => 'ids' ) ) : array();
$course_tags     = $is_edit ? wp_get_post_terms( $course_id, 'simple_lms_tag', array( 'fields' => 'names' ) ) : array();
$course_statuses = $is_edit ? wp_get_post_terms( $course_id, 'simple_lms_status', array( 'fields' => 'ids' ) ) : array();

// Default status for new courses.
if ( ! $is_edit && ! empty( $settings['default_status'] ) ) {
    $default_status_term = get_term_by( 'slug', $settings['default_status'], 'simple_lms_status' );
    if ( $default_status_term ) {
        $course_statuses = array( $default_status_term->term_id );
    }
}

$has_memberships   = function_exists( 'wc_memberships' );
$has_subscriptions = class_exists( 'WC_Subscriptions' );
$membership_plans  = LMS_Admin::get_membership_plans();
$subscription_products = LMS_Admin::get_subscription_products();

$default_video_title   = $settings['default_video_title'] ?? '';
$default_material_label = $settings['default_material_label'] ?? '';

$page_title = $is_edit ? __( 'Edit Course', 'simple-lms' ) : __( 'Add New Course', 'simple-lms' );
?>
<div class="wrap simple-lms-course-form">
    <h1><?php echo esc_html( $page_title ); ?></h1>

    <?php if ( isset( $_GET['message'] ) && 'saved' === $_GET['message'] ) : ?>
    <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e( 'Course saved successfully.', 'simple-lms' ); ?></p>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="course-form">
        <?php wp_nonce_field( 'simple_lms_save_course', 'simple_lms_course_nonce' ); ?>
        <input type="hidden" name="course_id" value="<?php echo esc_attr( $course_id ); ?>">

        <div class="course-form-layout">
            <div class="course-form-main">
                <!-- Title -->
                <div class="course-form-section">
                    <input type="text" name="course_title" id="course_title" class="large-text" placeholder="<?php esc_attr_e( 'Course title', 'simple-lms' ); ?>" value="<?php echo esc_attr( $course_title ); ?>" required>
                </div>

                <!-- Course Details -->
                <div class="course-form-section course-form-box">
                    <h2><?php esc_html_e( 'Course Details', 'simple-lms' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="simple_lms_date"><?php esc_html_e( 'Date', 'simple-lms' ); ?></label></th>
                            <td>
                                <input type="date" id="simple_lms_date" name="simple_lms_date" value="<?php echo esc_attr( $date ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e( 'Time Range', 'simple-lms' ); ?></label></th>
                            <td>
                                <input type="time" id="simple_lms_time_start" name="simple_lms_time_start" value="<?php echo esc_attr( $time_start ); ?>">
                                <span> - </span>
                                <input type="time" id="simple_lms_time_end" name="simple_lms_time_end" value="<?php echo esc_attr( $time_end ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="simple_lms_duration"><?php esc_html_e( 'Duration', 'simple-lms' ); ?></label></th>
                            <td>
                                <input type="text" id="simple_lms_duration" name="simple_lms_duration" value="<?php echo esc_attr( $duration ); ?>" placeholder="<?php esc_attr_e( 'e.g., 6h', 'simple-lms' ); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="simple_lms_lecturer"><?php esc_html_e( 'Lecturer', 'simple-lms' ); ?></label></th>
                            <td>
                                <input type="text" id="simple_lms_lecturer" name="simple_lms_lecturer" value="<?php echo esc_attr( $lecturer ); ?>" class="regular-text">
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Videos -->
                <div class="course-form-section course-form-box">
                    <h2><?php esc_html_e( 'Videos', 'simple-lms' ); ?></h2>
                    <div class="simple-lms-repeater" id="simple-lms-videos-repeater" data-default-title="<?php echo esc_attr( $default_video_title ); ?>">
                        <div class="repeater-items">
                            <?php if ( ! empty( $videos ) ) : ?>
                                <?php foreach ( $videos as $index => $video ) : ?>
                                <div class="repeater-item video-item">
                                    <div class="repeater-item-header">
                                        <span class="dashicons dashicons-menu handle"></span>
                                        <span class="item-title"><?php echo esc_html( $video['title'] ?: __( 'Video', 'simple-lms' ) ); ?></span>
                                        <button type="button" class="button-link remove-repeater-item"><?php esc_html_e( 'Remove', 'simple-lms' ); ?></button>
                                    </div>
                                    <div class="repeater-item-content">
                                        <p>
                                            <label><?php esc_html_e( 'Title', 'simple-lms' ); ?></label>
                                            <input type="text" name="simple_lms_videos[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $video['title'] ); ?>" class="widefat video-title-input">
                                        </p>
                                        <p>
                                            <label><?php esc_html_e( 'Vimeo URL', 'simple-lms' ); ?></label>
                                            <input type="url" name="simple_lms_videos[<?php echo esc_attr( $index ); ?>][vimeo_url]" value="<?php echo esc_attr( $video['vimeo_url'] ); ?>" class="widefat" placeholder="https://vimeo.com/123456789">
                                        </p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button add-repeater-item" data-type="video">
                            <?php esc_html_e( '+ Add Video', 'simple-lms' ); ?>
                        </button>
                    </div>
                    <script type="text/html" id="tmpl-simple-lms-video-row">
                        <div class="repeater-item video-item">
                            <div class="repeater-item-header">
                                <span class="dashicons dashicons-menu handle"></span>
                                <span class="item-title"><?php esc_html_e( 'Video', 'simple-lms' ); ?></span>
                                <button type="button" class="button-link remove-repeater-item"><?php esc_html_e( 'Remove', 'simple-lms' ); ?></button>
                            </div>
                            <div class="repeater-item-content">
                                <p>
                                    <label><?php esc_html_e( 'Title', 'simple-lms' ); ?></label>
                                    <input type="text" name="simple_lms_videos[{{INDEX}}][title]" value="" class="widefat video-title-input">
                                </p>
                                <p>
                                    <label><?php esc_html_e( 'Vimeo URL', 'simple-lms' ); ?></label>
                                    <input type="url" name="simple_lms_videos[{{INDEX}}][vimeo_url]" value="" class="widefat" placeholder="https://vimeo.com/123456789">
                                </p>
                            </div>
                        </div>
                    </script>
                </div>

                <!-- Materials -->
                <div class="course-form-section course-form-box">
                    <h2><?php esc_html_e( 'Materials', 'simple-lms' ); ?></h2>
                    <div class="simple-lms-repeater" id="simple-lms-materials-repeater" data-default-label="<?php echo esc_attr( $default_material_label ); ?>">
                        <div class="repeater-items">
                            <?php if ( ! empty( $materials ) ) : ?>
                                <?php foreach ( $materials as $index => $material ) : ?>
                                <div class="repeater-item material-item">
                                    <div class="repeater-item-header">
                                        <span class="dashicons dashicons-menu handle"></span>
                                        <span class="item-title"><?php echo esc_html( $material['label'] ?: __( 'Material', 'simple-lms' ) ); ?></span>
                                        <button type="button" class="button-link remove-repeater-item"><?php esc_html_e( 'Remove', 'simple-lms' ); ?></button>
                                    </div>
                                    <div class="repeater-item-content">
                                        <p>
                                            <label><?php esc_html_e( 'Label', 'simple-lms' ); ?></label>
                                            <input type="text" name="simple_lms_materials[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $material['label'] ); ?>" class="widefat material-label-input">
                                        </p>
                                        <p>
                                            <label><?php esc_html_e( 'URL', 'simple-lms' ); ?></label>
                                            <input type="url" name="simple_lms_materials[<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $material['url'] ); ?>" class="widefat" placeholder="https://example.com/file.pdf">
                                        </p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button add-repeater-item" data-type="material">
                            <?php esc_html_e( '+ Add Material', 'simple-lms' ); ?>
                        </button>
                    </div>
                    <script type="text/html" id="tmpl-simple-lms-material-row">
                        <div class="repeater-item material-item">
                            <div class="repeater-item-header">
                                <span class="dashicons dashicons-menu handle"></span>
                                <span class="item-title"><?php esc_html_e( 'Material', 'simple-lms' ); ?></span>
                                <button type="button" class="button-link remove-repeater-item"><?php esc_html_e( 'Remove', 'simple-lms' ); ?></button>
                            </div>
                            <div class="repeater-item-content">
                                <p>
                                    <label><?php esc_html_e( 'Label', 'simple-lms' ); ?></label>
                                    <input type="text" name="simple_lms_materials[{{INDEX}}][label]" value="" class="widefat material-label-input">
                                </p>
                                <p>
                                    <label><?php esc_html_e( 'URL', 'simple-lms' ); ?></label>
                                    <input type="url" name="simple_lms_materials[{{INDEX}}][url]" value="" class="widefat" placeholder="https://example.com/file.pdf">
                                </p>
                            </div>
                        </div>
                    </script>
                </div>

                <!-- Content -->
                <div class="course-form-section course-form-box">
                    <h2><?php esc_html_e( 'Additional Content', 'simple-lms' ); ?></h2>
                    <?php
                    wp_editor(
                        $course_content,
                        'course_content',
                        array(
                            'textarea_name' => 'course_content',
                            'textarea_rows' => 10,
                            'media_buttons' => true,
                            'teeny'         => false,
                            'quicktags'     => true,
                        )
                    );
                    ?>
                </div>

                <!-- Access Control -->
                <div class="course-form-section course-form-box">
                    <h2><?php esc_html_e( 'Access Control', 'simple-lms' ); ?></h2>
                    <table class="form-table">
                        <?php if ( $has_memberships ) : ?>
                        <tr>
                            <th><label><?php esc_html_e( 'Required Memberships', 'simple-lms' ); ?></label></th>
                            <td>
                                <?php if ( ! empty( $membership_plans ) ) : ?>
                                    <?php foreach ( $membership_plans as $plan ) : ?>
                                    <label class="simple-lms-checkbox">
                                        <input type="checkbox" name="simple_lms_access_memberships[]" value="<?php echo esc_attr( $plan->ID ); ?>" <?php checked( in_array( $plan->ID, $memberships, true ) ); ?>>
                                        <?php echo esc_html( $plan->post_title ); ?>
                                    </label><br>
                                    <?php endforeach; ?>
                                    <p class="description"><?php esc_html_e( 'User needs ANY of the selected memberships (OR logic).', 'simple-lms' ); ?></p>
                                <?php else : ?>
                                    <p class="description"><?php esc_html_e( 'No membership plans found.', 'simple-lms' ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else : ?>
                        <tr>
                            <th><?php esc_html_e( 'Memberships', 'simple-lms' ); ?></th>
                            <td><p class="description"><?php esc_html_e( 'WooCommerce Memberships is not active.', 'simple-lms' ); ?></p></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ( $has_subscriptions ) : ?>
                        <tr>
                            <th><label><?php esc_html_e( 'Required Subscriptions', 'simple-lms' ); ?></label></th>
                            <td>
                                <?php if ( ! empty( $subscription_products ) ) : ?>
                                    <?php foreach ( $subscription_products as $product ) : ?>
                                    <label class="simple-lms-checkbox">
                                        <input type="checkbox" name="simple_lms_access_products[]" value="<?php echo esc_attr( $product->get_id() ); ?>" <?php checked( in_array( $product->get_id(), $products, true ) ); ?>>
                                        <?php echo esc_html( $product->get_name() ); ?>
                                    </label><br>
                                    <?php endforeach; ?>
                                    <p class="description"><?php esc_html_e( 'User needs active subscription to ANY of the selected products (OR logic).', 'simple-lms' ); ?></p>
                                <?php else : ?>
                                    <p class="description"><?php esc_html_e( 'No subscription products found.', 'simple-lms' ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else : ?>
                        <tr>
                            <th><?php esc_html_e( 'Subscriptions', 'simple-lms' ); ?></th>
                            <td><p class="description"><?php esc_html_e( 'WooCommerce Subscriptions is not active.', 'simple-lms' ); ?></p></td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <th><label for="simple_lms_redirect_url"><?php esc_html_e( 'Redirect URL', 'simple-lms' ); ?></label></th>
                            <td>
                                <input type="url" id="simple_lms_redirect_url" name="simple_lms_redirect_url" value="<?php echo esc_attr( $redirect_url ); ?>" class="regular-text" placeholder="<?php echo esc_attr( Simple_LMS::get_setting( 'redirect_url', '/sklep/' ) ); ?>">
                                <p class="description"><?php esc_html_e( 'Where to redirect users without access. Leave empty to use default.', 'simple-lms' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="course-form-sidebar">
                <!-- Publish Box -->
                <div class="course-form-box">
                    <h2><?php esc_html_e( 'Publish', 'simple-lms' ); ?></h2>
                    <div class="publish-box-content">
                        <p>
                            <label>
                                <input type="radio" name="course_status" value="publish" <?php checked( $post_status, 'publish' ); ?>>
                                <?php esc_html_e( 'Published', 'simple-lms' ); ?>
                            </label>
                        </p>
                        <p>
                            <label>
                                <input type="radio" name="course_status" value="draft" <?php checked( $post_status, 'draft' ); ?>>
                                <?php esc_html_e( 'Draft', 'simple-lms' ); ?>
                            </label>
                        </p>
                        <hr>
                        <p>
                            <button type="submit" class="button button-primary button-large" id="publish-course">
                                <?php echo $is_edit ? esc_html__( 'Update Course', 'simple-lms' ) : esc_html__( 'Create Course', 'simple-lms' ); ?>
                            </button>
                        </p>
                        <?php if ( $is_edit ) : ?>
                        <p>
                            <a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" class="button" target="_blank">
                                <?php esc_html_e( 'View Course', 'simple-lms' ); ?>
                            </a>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status Taxonomy -->
                <div class="course-form-box">
                    <h2><?php esc_html_e( 'Course Status', 'simple-lms' ); ?></h2>
                    <?php if ( ! empty( $statuses ) && ! is_wp_error( $statuses ) ) : ?>
                        <?php foreach ( $statuses as $status ) : ?>
                        <p>
                            <label>
                                <input type="checkbox" name="simple_lms_course_status_tax[]" value="<?php echo esc_attr( $status->term_id ); ?>" <?php checked( in_array( $status->term_id, $course_statuses, true ) ); ?>>
                                <?php echo esc_html( $status->name ); ?>
                            </label>
                        </p>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="description"><?php esc_html_e( 'No statuses found.', 'simple-lms' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=statuses' ) ); ?>"><?php esc_html_e( 'Add statuses', 'simple-lms' ); ?></a></p>
                    <?php endif; ?>
                </div>

                <!-- Categories -->
                <div class="course-form-box">
                    <h2><?php esc_html_e( 'Categories', 'simple-lms' ); ?></h2>
                    <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                        <div class="categorychecklist">
                            <?php foreach ( $categories as $category ) : ?>
                            <p>
                                <label>
                                    <input type="checkbox" name="simple_lms_categories[]" value="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( in_array( $category->term_id, $course_cats, true ) ); ?>>
                                    <?php echo esc_html( $category->name ); ?>
                                </label>
                            </p>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="description"><?php esc_html_e( 'No categories found.', 'simple-lms' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=categories' ) ); ?>"><?php esc_html_e( 'Add categories', 'simple-lms' ); ?></a></p>
                    <?php endif; ?>
                </div>

                <!-- Tags -->
                <div class="course-form-box">
                    <h2><?php esc_html_e( 'Tags', 'simple-lms' ); ?></h2>
                    <input type="text" name="simple_lms_tags" value="<?php echo esc_attr( implode( ', ', $course_tags ) ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Separate with commas', 'simple-lms' ); ?>">
                </div>
            </div>
        </div>
    </form>
</div>
