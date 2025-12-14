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

// Get filter/sort/pagination parameters.
$paged           = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$search          = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$status_filter   = isset( $_GET['status'] ) ? absint( $_GET['status'] ) : 0;
$category_filter = isset( $_GET['category'] ) ? absint( $_GET['category'] ) : 0;
$tag_filter      = isset( $_GET['tag'] ) ? absint( $_GET['tag'] ) : 0;
$lecturer_filter = isset( $_GET['lecturer'] ) ? absint( $_GET['lecturer'] ) : 0;
$orderby         = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'date';
$order           = isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : 'DESC';

// Get per_page from screen options.
$user_id  = get_current_user_id();
$per_page = get_user_meta( $user_id, 'simple_lms_courses_per_page', true );
$per_page = $per_page ? absint( $per_page ) : 20;

// Validate order.
$order = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

// Map orderby to WP_Query parameters.
$orderby_map = array(
    'title'       => 'title',
    'date'        => 'date',
    'course_date' => 'meta_value',
    'post_status' => 'post_status',
);

$wp_orderby = isset( $orderby_map[ $orderby ] ) ? $orderby_map[ $orderby ] : 'date';

$args = array(
    'post_type'      => 'simple_lms_course',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'post_status'    => array( 'publish', 'draft' ),
    'orderby'        => $wp_orderby,
    'order'          => $order,
);

// Handle meta ordering.
if ( 'course_date' === $orderby ) {
    $args['meta_key'] = '_simple_lms_date';
    $args['orderby']  = 'meta_value';
}

// Search.
if ( $search ) {
    $args['s'] = $search;
}

// Build tax_query.
$tax_query = array();

if ( $status_filter ) {
    $tax_query[] = array(
        'taxonomy' => 'simple_lms_status',
        'field'    => 'term_id',
        'terms'    => $status_filter,
    );
}

if ( $category_filter ) {
    $tax_query[] = array(
        'taxonomy' => 'simple_lms_category',
        'field'    => 'term_id',
        'terms'    => $category_filter,
    );
}

if ( $tag_filter ) {
    $tax_query[] = array(
        'taxonomy' => 'simple_lms_tag',
        'field'    => 'term_id',
        'terms'    => $tag_filter,
    );
}

// Lecturer filter (now using taxonomy).
if ( $lecturer_filter ) {
    $tax_query[] = array(
        'taxonomy' => 'simple_lms_lecturer',
        'field'    => 'term_id',
        'terms'    => $lecturer_filter,
    );
}

if ( ! empty( $tax_query ) ) {
    $tax_query['relation'] = 'AND';
    $args['tax_query']     = $tax_query;
}

$courses_query = new WP_Query( $args );

// Get terms for filters.
$statuses   = get_terms( array( 'taxonomy' => 'simple_lms_status', 'hide_empty' => false ) );
$categories = get_terms( array( 'taxonomy' => 'simple_lms_category', 'hide_empty' => false ) );
$tags       = get_terms( array( 'taxonomy' => 'simple_lms_tag', 'hide_empty' => false ) );
$lecturers  = get_terms( array( 'taxonomy' => 'simple_lms_lecturer', 'hide_empty' => false ) );

$date_format = Simple_LMS::get_setting( 'date_format', 'd.m.Y' );

/**
 * Helper function to generate sortable column URL.
 */
function simple_lms_sort_url( $column, $current_orderby, $current_order ) {
    $new_order = ( $current_orderby === $column && $current_order === 'ASC' ) ? 'DESC' : 'ASC';
    $params    = $_GET;
    $params['orderby'] = $column;
    $params['order']   = $new_order;
    unset( $params['paged'] ); // Reset to page 1 when sorting.
    return add_query_arg( $params, admin_url( 'admin.php' ) );
}

/**
 * Helper function to get sort class.
 */
function simple_lms_sort_class( $column, $current_orderby, $current_order ) {
    if ( $current_orderby !== $column ) {
        return 'sortable desc';
    }
    return 'sorted ' . strtolower( $current_order );
}
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
        <form method="get" action="" class="simple-lms-filters">
            <input type="hidden" name="page" value="simple-lms">

            <div class="alignleft actions">
                <!-- Category filter -->
                <select name="category">
                    <option value=""><?php esc_html_e( 'All Categories', 'simple-lms' ); ?></option>
                    <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                        <?php foreach ( $categories as $category ) : ?>
                        <option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $category_filter, $category->term_id ); ?>>
                            <?php echo esc_html( $category->name ); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <!-- Tag filter -->
                <select name="tag">
                    <option value=""><?php esc_html_e( 'All Tags', 'simple-lms' ); ?></option>
                    <?php if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) : ?>
                        <?php foreach ( $tags as $tag ) : ?>
                        <option value="<?php echo esc_attr( $tag->term_id ); ?>" <?php selected( $tag_filter, $tag->term_id ); ?>>
                            <?php echo esc_html( $tag->name ); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <!-- Status filter -->
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

                <!-- Lecturer filter -->
                <select name="lecturer">
                    <option value=""><?php esc_html_e( 'All Lecturers', 'simple-lms' ); ?></option>
                    <?php if ( ! empty( $lecturers ) && ! is_wp_error( $lecturers ) ) : ?>
                        <?php foreach ( $lecturers as $lecturer ) : ?>
                        <option value="<?php echo esc_attr( $lecturer->term_id ); ?>" <?php selected( $lecturer_filter, $lecturer->term_id ); ?>>
                            <?php echo esc_html( $lecturer->name ); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'simple-lms' ); ?>">

                <?php if ( $category_filter || $tag_filter || $status_filter || $lecturer_filter || $search ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms' ) ); ?>" class="button"><?php esc_html_e( 'Reset', 'simple-lms' ); ?></a>
                <?php endif; ?>
            </div>

            <div class="alignright">
                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search courses...', 'simple-lms' ); ?>">
                <input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'simple-lms' ); ?>">
            </div>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="column-title column-primary <?php echo esc_attr( simple_lms_sort_class( 'title', $orderby, $order ) ); ?>">
                    <a href="<?php echo esc_url( simple_lms_sort_url( 'title', $orderby, $order ) ); ?>">
                        <span><?php esc_html_e( 'Title', 'simple-lms' ); ?></span>
                        <span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span>
                    </a>
                </th>
                <th scope="col" class="column-category"><?php esc_html_e( 'Category', 'simple-lms' ); ?></th>
                <th scope="col" class="column-tags"><?php esc_html_e( 'Tags', 'simple-lms' ); ?></th>
                <th scope="col" class="column-status"><?php esc_html_e( 'Status', 'simple-lms' ); ?></th>
                <th scope="col" class="column-course-date <?php echo esc_attr( simple_lms_sort_class( 'course_date', $orderby, $order ) ); ?>">
                    <a href="<?php echo esc_url( simple_lms_sort_url( 'course_date', $orderby, $order ) ); ?>">
                        <span><?php esc_html_e( 'Course Date', 'simple-lms' ); ?></span>
                        <span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span>
                    </a>
                </th>
                <th scope="col" class="column-lecturer"><?php esc_html_e( 'Lecturer', 'simple-lms' ); ?></th>
                <th scope="col" class="column-post-status <?php echo esc_attr( simple_lms_sort_class( 'post_status', $orderby, $order ) ); ?>">
                    <a href="<?php echo esc_url( simple_lms_sort_url( 'post_status', $orderby, $order ) ); ?>">
                        <span><?php esc_html_e( 'Published', 'simple-lms' ); ?></span>
                        <span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span>
                    </a>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $courses_query->have_posts() ) : ?>
                <?php while ( $courses_query->have_posts() ) : $courses_query->the_post(); ?>
                <?php
                $course_id        = get_the_ID();
                $course_date      = get_post_meta( $course_id, '_simple_lms_date', true );
                $course_status    = wp_get_post_terms( $course_id, 'simple_lms_status', array( 'fields' => 'names' ) );
                $course_category  = wp_get_post_terms( $course_id, 'simple_lms_category', array( 'fields' => 'names' ) );
                $course_tags      = wp_get_post_terms( $course_id, 'simple_lms_tag', array( 'fields' => 'names' ) );
                $course_lecturer  = wp_get_post_terms( $course_id, 'simple_lms_lecturer', array( 'fields' => 'names' ) );
                $post_status      = get_post_status( $course_id );

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
                    <td class="column-category" data-colname="<?php esc_attr_e( 'Category', 'simple-lms' ); ?>">
                        <?php echo ! empty( $course_category ) && ! is_wp_error( $course_category ) ? esc_html( implode( ', ', $course_category ) ) : '—'; ?>
                    </td>
                    <td class="column-tags" data-colname="<?php esc_attr_e( 'Tags', 'simple-lms' ); ?>">
                        <?php echo ! empty( $course_tags ) && ! is_wp_error( $course_tags ) ? esc_html( implode( ', ', $course_tags ) ) : '—'; ?>
                    </td>
                    <td class="column-status" data-colname="<?php esc_attr_e( 'Status', 'simple-lms' ); ?>">
                        <?php echo ! empty( $course_status ) && ! is_wp_error( $course_status ) ? esc_html( implode( ', ', $course_status ) ) : '—'; ?>
                    </td>
                    <td class="column-course-date" data-colname="<?php esc_attr_e( 'Course Date', 'simple-lms' ); ?>">
                        <?php echo $formatted_date ? esc_html( $formatted_date ) : '—'; ?>
                    </td>
                    <td class="column-lecturer" data-colname="<?php esc_attr_e( 'Lecturer', 'simple-lms' ); ?>">
                        <?php echo ! empty( $course_lecturer ) && ! is_wp_error( $course_lecturer ) ? esc_html( implode( ', ', $course_lecturer ) ) : '—'; ?>
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
                    <td colspan="7"><?php esc_html_e( 'No courses found.', 'simple-lms' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $courses_query->max_num_pages > 1 ) : ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php
                printf(
                    /* translators: %s: number of items */
                    _n( '%s item', '%s items', $courses_query->found_posts, 'simple-lms' ),
                    number_format_i18n( $courses_query->found_posts )
                );
                ?>
            </span>
            <?php
            $pagination_args = array(
                'base'      => add_query_arg( 'paged', '%#%' ),
                'format'    => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total'     => $courses_query->max_num_pages,
                'current'   => $paged,
            );
            echo paginate_links( $pagination_args );
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>
