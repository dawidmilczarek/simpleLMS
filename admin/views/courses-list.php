<?php
/**
 * Courses list page.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get courses.
$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$status_filter = isset( $_GET['status'] ) ? absint( $_GET['status'] ) : 0;

$args = array(
    'post_type'      => 'simple_lms_course',
    'posts_per_page' => 20,
    'paged'          => $paged,
    'post_status'    => array( 'publish', 'draft' ),
    'orderby'        => 'date',
    'order'          => 'DESC',
);

if ( $search ) {
    $args['s'] = $search;
}

if ( $status_filter ) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'simple_lms_status',
            'field'    => 'term_id',
            'terms'    => $status_filter,
        ),
    );
}

$courses_query = new WP_Query( $args );
$statuses = get_terms( array( 'taxonomy' => 'simple_lms_status', 'hide_empty' => false ) );
$date_format = Simple_LMS::get_setting( 'date_format', 'd.m.Y' );
?>
<div class="wrap simple-lms-courses">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Courses', 'simple-lms' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'simple-lms' ); ?></a>
    <hr class="wp-header-end">

    <?php if ( isset( $_GET['deleted'] ) ) : ?>
    <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e( 'Course deleted successfully.', 'simple-lms' ); ?></p>
    </div>
    <?php endif; ?>

    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="simple-lms">
                <select name="status">
                    <option value=""><?php esc_html_e( 'All Statuses', 'simple-lms' ); ?></option>
                    <?php if ( ! empty( $statuses ) && ! is_wp_error( $statuses ) ) : ?>
                        <?php foreach ( $statuses as $status ) : ?>
                        <option value="<?php echo esc_attr( $status->term_id ); ?>" <?php selected( $status_filter, $status->term_id ); ?>>
                            <?php echo esc_html( $status->name ); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'simple-lms' ); ?>">
            </form>
        </div>
        <div class="alignright">
            <form method="get" action="">
                <input type="hidden" name="page" value="simple-lms">
                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search courses...', 'simple-lms' ); ?>">
                <input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'simple-lms' ); ?>">
            </form>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="column-title column-primary"><?php esc_html_e( 'Title', 'simple-lms' ); ?></th>
                <th scope="col" class="column-status"><?php esc_html_e( 'Status', 'simple-lms' ); ?></th>
                <th scope="col" class="column-date"><?php esc_html_e( 'Course Date', 'simple-lms' ); ?></th>
                <th scope="col" class="column-lecturer"><?php esc_html_e( 'Lecturer', 'simple-lms' ); ?></th>
                <th scope="col" class="column-post-status"><?php esc_html_e( 'Published', 'simple-lms' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $courses_query->have_posts() ) : ?>
                <?php while ( $courses_query->have_posts() ) : $courses_query->the_post(); ?>
                <?php
                $course_id      = get_the_ID();
                $course_date    = get_post_meta( $course_id, '_simple_lms_date', true );
                $lecturer       = get_post_meta( $course_id, '_simple_lms_lecturer', true );
                $course_status  = wp_get_post_terms( $course_id, 'simple_lms_status', array( 'fields' => 'names' ) );
                $post_status    = get_post_status( $course_id );

                $formatted_date = '';
                if ( $course_date ) {
                    $timestamp = strtotime( $course_date );
                    if ( $timestamp ) {
                        $formatted_date = date_i18n( $date_format, $timestamp );
                    }
                }
                ?>
                <tr>
                    <td class="column-title column-primary" data-colname="<?php esc_attr_e( 'Title', 'simple-lms' ); ?>">
                        <strong>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-add&course_id=' . $course_id ) ); ?>" class="row-title">
                                <?php the_title(); ?>
                            </a>
                        </strong>
                        <div class="row-actions">
                            <span class="edit">
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-add&course_id=' . $course_id ) ); ?>">
                                    <?php esc_html_e( 'Edit', 'simple-lms' ); ?>
                                </a> |
                            </span>
                            <span class="view">
                                <a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" target="_blank">
                                    <?php esc_html_e( 'View', 'simple-lms' ); ?>
                                </a> |
                            </span>
                            <span class="trash">
                                <a href="#" class="delete-course" data-course-id="<?php echo esc_attr( $course_id ); ?>">
                                    <?php esc_html_e( 'Delete', 'simple-lms' ); ?>
                                </a>
                            </span>
                        </div>
                    </td>
                    <td class="column-status" data-colname="<?php esc_attr_e( 'Status', 'simple-lms' ); ?>">
                        <?php echo ! empty( $course_status ) && ! is_wp_error( $course_status ) ? esc_html( implode( ', ', $course_status ) ) : '—'; ?>
                    </td>
                    <td class="column-date" data-colname="<?php esc_attr_e( 'Course Date', 'simple-lms' ); ?>">
                        <?php echo $formatted_date ? esc_html( $formatted_date ) : '—'; ?>
                    </td>
                    <td class="column-lecturer" data-colname="<?php esc_attr_e( 'Lecturer', 'simple-lms' ); ?>">
                        <?php echo $lecturer ? esc_html( $lecturer ) : '—'; ?>
                    </td>
                    <td class="column-post-status" data-colname="<?php esc_attr_e( 'Published', 'simple-lms' ); ?>">
                        <?php if ( 'publish' === $post_status ) : ?>
                            <span class="status-publish"><?php esc_html_e( 'Published', 'simple-lms' ); ?></span>
                        <?php else : ?>
                            <span class="status-draft"><?php esc_html_e( 'Draft', 'simple-lms' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <tr>
                    <td colspan="5"><?php esc_html_e( 'No courses found.', 'simple-lms' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $courses_query->max_num_pages > 1 ) : ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            echo paginate_links( array(
                'base'      => add_query_arg( 'paged', '%#%' ),
                'format'    => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total'     => $courses_query->max_num_pages,
                'current'   => $paged,
            ) );
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>
