<?php
/**
 * Taxonomy management tab.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Determine which taxonomy we're managing.
$taxonomy_map = array(
    'categories' => array(
        'taxonomy'        => 'simple_lms_category',
        'singular'        => __( 'Category', 'simple-lms' ),
        'plural'          => __( 'Categories', 'simple-lms' ),
        'default_setting' => 'default_category',
    ),
    'tags' => array(
        'taxonomy'        => 'simple_lms_tag',
        'singular'        => __( 'Tag', 'simple-lms' ),
        'plural'          => __( 'Tags', 'simple-lms' ),
        'default_setting' => null, // Tags don't have a default.
    ),
    'statuses' => array(
        'taxonomy'        => 'simple_lms_status',
        'singular'        => __( 'Status', 'simple-lms' ),
        'plural'          => __( 'Statuses', 'simple-lms' ),
        'default_setting' => 'default_status',
    ),
    'lecturers' => array(
        'taxonomy'        => 'simple_lms_lecturer',
        'singular'        => __( 'Lecturer', 'simple-lms' ),
        'plural'          => __( 'Lecturers', 'simple-lms' ),
        'default_setting' => 'default_lecturer',
    ),
);

$current_tab     = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'categories';
$tax_config      = $taxonomy_map[ $current_tab ] ?? $taxonomy_map['categories'];
$taxonomy        = $tax_config['taxonomy'];
$singular        = $tax_config['singular'];
$plural          = $tax_config['plural'];
$default_setting = $tax_config['default_setting'];

// Get settings for default value.
$settings = get_option( 'simple_lms_settings', array() );

// Get admin notices from LMS_Admin.
$lms     = Simple_LMS::instance();
$notices = $lms->admin->get_admin_notices();

// Get terms.
$terms = get_terms(
    array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
    )
);

// Check if editing.
$editing_term = null;
if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] && isset( $_GET['term_id'] ) ) {
    $editing_term = get_term( absint( $_GET['term_id'] ), $taxonomy );
}

// Refresh settings after save.
$settings = get_option( 'simple_lms_settings', array() );
?>

<?php foreach ( $notices as $notice ) : ?>
<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
    <p><?php echo esc_html( $notice['message'] ); ?></p>
</div>
<?php endforeach; ?>

<?php if ( $default_setting ) : ?>
<div class="taxonomy-default-setting">
    <h2><?php printf( esc_html__( 'Default %s for New Courses', 'simple-lms' ), esc_html( $singular ) ); ?></h2>
    <form method="post" action="">
        <?php wp_nonce_field( 'simple_lms_save_default' ); ?>
        <table class="form-table">
            <tr>
                <th>
                    <label for="default_value"><?php printf( esc_html__( 'Default %s', 'simple-lms' ), esc_html( $singular ) ); ?></label>
                </th>
                <td>
                    <select id="default_value" name="default_value">
                        <option value=""><?php esc_html_e( '— None —', 'simple-lms' ); ?></option>
                        <?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
                            <?php foreach ( $terms as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( isset( $settings[ $default_setting ] ) ? $settings[ $default_setting ] : '', $term->slug ); ?>>
                                <?php echo esc_html( $term->name ); ?>
                            </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="description">
                        <?php printf( esc_html__( 'This %s will be pre-selected when creating new courses.', 'simple-lms' ), esc_html( strtolower( $singular ) ) ); ?>
                    </p>
                </td>
            </tr>
        </table>
        <p>
            <button type="submit" name="simple_lms_save_default" class="button button-primary"><?php esc_html_e( 'Save Default', 'simple-lms' ); ?></button>
        </p>
    </form>
</div>
<hr>
<?php endif; ?>

<div class="taxonomy-form-column">
    <?php if ( $editing_term ) : ?>
    <h2><?php printf( esc_html__( 'Edit %s', 'simple-lms' ), esc_html( $singular ) ); ?></h2>
    <form method="post" action="">
        <?php wp_nonce_field( 'simple_lms_edit_term' ); ?>
        <input type="hidden" name="term_id" value="<?php echo esc_attr( $editing_term->term_id ); ?>">

        <table class="form-table">
            <tr>
                <th><label for="term_name"><?php esc_html_e( 'Name', 'simple-lms' ); ?></label></th>
                <td>
                    <input type="text" id="term_name" name="term_name" value="<?php echo esc_attr( $editing_term->name ); ?>" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th><label for="term_slug"><?php esc_html_e( 'Slug', 'simple-lms' ); ?></label></th>
                <td>
                    <input type="text" id="term_slug" name="term_slug" value="<?php echo esc_attr( $editing_term->slug ); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e( 'The "slug" is the URL-friendly version of the name.', 'simple-lms' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="term_description"><?php esc_html_e( 'Description', 'simple-lms' ); ?></label></th>
                <td>
                    <textarea id="term_description" name="term_description" rows="3" class="large-text"><?php echo esc_textarea( $editing_term->description ); ?></textarea>
                </td>
            </tr>
        </table>

        <p>
            <button type="submit" name="simple_lms_edit_term" class="button button-primary"><?php esc_html_e( 'Update', 'simple-lms' ); ?></button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=' . $current_tab ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'simple-lms' ); ?></a>
        </p>
    </form>
    <?php else : ?>
    <h2><?php printf( esc_html__( 'Add New %s', 'simple-lms' ), esc_html( $singular ) ); ?></h2>
    <form method="post" action="">
        <?php wp_nonce_field( 'simple_lms_add_term' ); ?>

        <table class="form-table">
            <tr>
                <th><label for="term_name"><?php esc_html_e( 'Name', 'simple-lms' ); ?></label></th>
                <td>
                    <input type="text" id="term_name" name="term_name" value="" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th><label for="term_slug"><?php esc_html_e( 'Slug', 'simple-lms' ); ?></label></th>
                <td>
                    <input type="text" id="term_slug" name="term_slug" value="" class="regular-text">
                    <p class="description"><?php esc_html_e( 'The "slug" is the URL-friendly version of the name. Leave empty to auto-generate.', 'simple-lms' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="term_description"><?php esc_html_e( 'Description', 'simple-lms' ); ?></label></th>
                <td>
                    <textarea id="term_description" name="term_description" rows="3" class="large-text"></textarea>
                </td>
            </tr>
        </table>

        <p>
            <button type="submit" name="simple_lms_add_term" class="button button-primary">
                <?php printf( esc_html__( 'Add New %s', 'simple-lms' ), esc_html( $singular ) ); ?>
            </button>
        </p>
    </form>
    <?php endif; ?>
</div>

<div class="taxonomy-list-column">
    <h2><?php echo esc_html( $plural ); ?></h2>

    <?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Name', 'simple-lms' ); ?></th>
                <th><?php esc_html_e( 'Slug', 'simple-lms' ); ?></th>
                <th><?php esc_html_e( 'Count', 'simple-lms' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'simple-lms' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $terms as $term ) : ?>
            <tr>
                <td>
                    <strong><?php echo esc_html( $term->name ); ?></strong>
                    <?php if ( $term->description ) : ?>
                    <p class="description"><?php echo esc_html( wp_trim_words( $term->description, 10 ) ); ?></p>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html( $term->slug ); ?></td>
                <td><?php echo esc_html( $term->count ); ?></td>
                <td>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=' . $current_tab . '&action=edit&term_id=' . $term->term_id ) ); ?>">
                        <?php esc_html_e( 'Edit', 'simple-lms' ); ?>
                    </a>
                    |
                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=simple-lms-settings&tab=' . $current_tab . '&action=delete&term_id=' . $term->term_id ), 'delete_term_' . $term->term_id ) ); ?>" class="delete-term" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this?', 'simple-lms' ); ?>');">
                        <?php esc_html_e( 'Delete', 'simple-lms' ); ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else : ?>
    <p><?php printf( esc_html__( 'No %s found.', 'simple-lms' ), esc_html( strtolower( $plural ) ) ); ?></p>
    <?php endif; ?>
</div>
