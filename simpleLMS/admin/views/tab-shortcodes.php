<?php
/**
 * Shortcodes settings tab.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get admin notices from LMS_Admin.
$lms      = Simple_LMS::instance();
$notices  = $lms->admin->get_admin_notices();

// Get data for form.
$presets    = get_option( 'simple_lms_shortcode_presets', array() );
$statuses   = get_terms( array( 'taxonomy' => 'simple_lms_status', 'hide_empty' => false ) );
$categories = get_terms( array( 'taxonomy' => 'simple_lms_category', 'hide_empty' => false ) );
$tags       = get_terms( array( 'taxonomy' => 'simple_lms_tag', 'hide_empty' => false ) );

$available_elements = array(
    'title'    => __( 'Title', 'simple-lms' ),
    'status'   => __( 'Status', 'simple-lms' ),
    'date'     => __( 'Date', 'simple-lms' ),
    'time'     => __( 'Time', 'simple-lms' ),
    'duration' => __( 'Duration', 'simple-lms' ),
    'lecturer' => __( 'Lecturer', 'simple-lms' ),
    'category' => __( 'Category', 'simple-lms' ),
    'tags'     => __( 'Tags', 'simple-lms' ),
);

$default_elements = array( 'title', 'status', 'date', 'time', 'duration', 'lecturer' );

// Check if editing.
$editing_preset = null;
$editing_slug   = null;
if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] && isset( $_GET['preset'] ) ) {
    $editing_slug = sanitize_key( $_GET['preset'] );
    if ( isset( $presets[ $editing_slug ] ) ) {
        $editing_preset = $presets[ $editing_slug ];
    }
}
?>

<?php foreach ( $notices as $notice ) : ?>
<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
    <p><?php echo esc_html( $notice['message'] ); ?></p>
</div>
<?php endforeach; ?>

<div class="simple-lms-shortcodes">
    <p class="description">
        <?php esc_html_e( 'Create presets to use with the [lms_courses] shortcode.', 'simple-lms' ); ?>
        <?php esc_html_e( 'Usage: [lms_courses preset="preset-name"]', 'simple-lms' ); ?>
    </p>

    <div class="taxonomy-form-column">
        <?php if ( $editing_preset ) : ?>
        <h2><?php esc_html_e( 'Edit Preset', 'simple-lms' ); ?>: <code><?php echo esc_html( $editing_slug ); ?></code></h2>
        <form method="post" action="">
            <?php wp_nonce_field( 'simple_lms_edit_preset' ); ?>
            <input type="hidden" name="preset_name" value="<?php echo esc_attr( $editing_slug ); ?>">

            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Filter by Status', 'simple-lms' ); ?></label></th>
                    <td>
                        <?php if ( ! empty( $statuses ) && ! is_wp_error( $statuses ) ) : ?>
                            <?php foreach ( $statuses as $status ) : ?>
                            <label class="simple-lms-checkbox">
                                <input type="checkbox" name="statuses[]" value="<?php echo esc_attr( $status->term_id ); ?>" <?php checked( in_array( $status->term_id, $editing_preset['statuses'] ?? array() ) ); ?>>
                                <?php echo esc_html( $status->name ); ?>
                            </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'Leave empty to show all.', 'simple-lms' ); ?></p>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e( 'No statuses found.', 'simple-lms' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Filter by Category', 'simple-lms' ); ?></label></th>
                    <td>
                        <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                            <?php foreach ( $categories as $category ) : ?>
                            <label class="simple-lms-checkbox">
                                <input type="checkbox" name="categories[]" value="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( in_array( $category->term_id, $editing_preset['categories'] ?? array() ) ); ?>>
                                <?php echo esc_html( $category->name ); ?>
                            </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'Leave empty to show all.', 'simple-lms' ); ?></p>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e( 'No categories found.', 'simple-lms' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Filter by Tags', 'simple-lms' ); ?></label></th>
                    <td>
                        <?php if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) : ?>
                            <?php foreach ( $tags as $tag ) : ?>
                            <label class="simple-lms-checkbox">
                                <input type="checkbox" name="tags[]" value="<?php echo esc_attr( $tag->term_id ); ?>" <?php checked( in_array( $tag->term_id, $editing_preset['tags'] ?? array() ) ); ?>>
                                <?php echo esc_html( $tag->name ); ?>
                            </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'Leave empty to show all.', 'simple-lms' ); ?></p>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e( 'No tags found.', 'simple-lms' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="orderby"><?php esc_html_e( 'Order By', 'simple-lms' ); ?></label></th>
                    <td>
                        <select id="orderby" name="orderby">
                            <option value="date" <?php selected( $editing_preset['orderby'] ?? 'date', 'date' ); ?>><?php esc_html_e( 'Course Date', 'simple-lms' ); ?></option>
                            <option value="title" <?php selected( $editing_preset['orderby'] ?? '', 'title' ); ?>><?php esc_html_e( 'Title (alphabetical)', 'simple-lms' ); ?></option>
                            <option value="menu_order" <?php selected( $editing_preset['orderby'] ?? '', 'menu_order' ); ?>><?php esc_html_e( 'Menu Order (manual)', 'simple-lms' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="order"><?php esc_html_e( 'Order', 'simple-lms' ); ?></label></th>
                    <td>
                        <select id="order" name="order">
                            <option value="DESC" <?php selected( $editing_preset['order'] ?? 'DESC', 'DESC' ); ?>><?php esc_html_e( 'Descending (newest first)', 'simple-lms' ); ?></option>
                            <option value="ASC" <?php selected( $editing_preset['order'] ?? '', 'ASC' ); ?>><?php esc_html_e( 'Ascending (oldest first)', 'simple-lms' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="limit"><?php esc_html_e( 'Limit', 'simple-lms' ); ?></label></th>
                    <td>
                        <input type="number" id="limit" name="limit" value="<?php echo esc_attr( $editing_preset['limit'] ?? -1 ); ?>" min="-1" class="small-text">
                        <p class="description"><?php esc_html_e( 'Number of courses to show. Use -1 for unlimited.', 'simple-lms' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Display Elements', 'simple-lms' ); ?></label></th>
                    <td>
                        <p class="description"><?php esc_html_e( 'Drag to reorder. Check to display.', 'simple-lms' ); ?></p>
                        <?php
                        $preset_elements = $editing_preset['elements'] ?? $default_elements;
                        // Merge: first show saved elements, then remaining ones.
                        $ordered_elements = array();
                        foreach ( $preset_elements as $el ) {
                            if ( isset( $available_elements[ $el ] ) ) {
                                $ordered_elements[ $el ] = $available_elements[ $el ];
                            }
                        }
                        foreach ( $available_elements as $key => $label ) {
                            if ( ! isset( $ordered_elements[ $key ] ) ) {
                                $ordered_elements[ $key ] = $label;
                            }
                        }
                        ?>
                        <ul id="elements-sortable" class="elements-sortable">
                            <?php foreach ( $ordered_elements as $key => $label ) : ?>
                            <li data-element="<?php echo esc_attr( $key ); ?>">
                                <span class="dashicons dashicons-menu handle"></span>
                                <label>
                                    <input type="checkbox" name="elements[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $preset_elements, true ) ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </label>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                </tr>
            </table>

            <p>
                <button type="submit" name="simple_lms_edit_preset" class="button button-primary"><?php esc_html_e( 'Update Preset', 'simple-lms' ); ?></button>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=shortcodes' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'simple-lms' ); ?></a>
            </p>
        </form>

        <?php else : ?>
        <h2><?php esc_html_e( 'Add New Preset', 'simple-lms' ); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field( 'simple_lms_add_preset' ); ?>

            <table class="form-table">
                <tr>
                    <th><label for="preset_name"><?php esc_html_e( 'Name', 'simple-lms' ); ?></label></th>
                    <td>
                        <input type="text" id="preset_name" name="preset_name" value="" class="regular-text" required pattern="[a-z0-9\-]+">
                        <p class="description"><?php esc_html_e( 'Lowercase letters, numbers and hyphens only. Used in shortcode.', 'simple-lms' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Filter by Status', 'simple-lms' ); ?></label></th>
                    <td>
                        <?php if ( ! empty( $statuses ) && ! is_wp_error( $statuses ) ) : ?>
                            <?php foreach ( $statuses as $status ) : ?>
                            <label class="simple-lms-checkbox">
                                <input type="checkbox" name="statuses[]" value="<?php echo esc_attr( $status->term_id ); ?>">
                                <?php echo esc_html( $status->name ); ?>
                            </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'Leave empty to show all.', 'simple-lms' ); ?></p>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e( 'No statuses found.', 'simple-lms' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Filter by Category', 'simple-lms' ); ?></label></th>
                    <td>
                        <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                            <?php foreach ( $categories as $category ) : ?>
                            <label class="simple-lms-checkbox">
                                <input type="checkbox" name="categories[]" value="<?php echo esc_attr( $category->term_id ); ?>">
                                <?php echo esc_html( $category->name ); ?>
                            </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'Leave empty to show all.', 'simple-lms' ); ?></p>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e( 'No categories found.', 'simple-lms' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Filter by Tags', 'simple-lms' ); ?></label></th>
                    <td>
                        <?php if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) : ?>
                            <?php foreach ( $tags as $tag ) : ?>
                            <label class="simple-lms-checkbox">
                                <input type="checkbox" name="tags[]" value="<?php echo esc_attr( $tag->term_id ); ?>">
                                <?php echo esc_html( $tag->name ); ?>
                            </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'Leave empty to show all.', 'simple-lms' ); ?></p>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e( 'No tags found.', 'simple-lms' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="orderby"><?php esc_html_e( 'Order By', 'simple-lms' ); ?></label></th>
                    <td>
                        <select id="orderby" name="orderby">
                            <option value="date"><?php esc_html_e( 'Course Date', 'simple-lms' ); ?></option>
                            <option value="title"><?php esc_html_e( 'Title (alphabetical)', 'simple-lms' ); ?></option>
                            <option value="menu_order"><?php esc_html_e( 'Menu Order (manual)', 'simple-lms' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="order"><?php esc_html_e( 'Order', 'simple-lms' ); ?></label></th>
                    <td>
                        <select id="order" name="order">
                            <option value="DESC"><?php esc_html_e( 'Descending (newest first)', 'simple-lms' ); ?></option>
                            <option value="ASC"><?php esc_html_e( 'Ascending (oldest first)', 'simple-lms' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="limit"><?php esc_html_e( 'Limit', 'simple-lms' ); ?></label></th>
                    <td>
                        <input type="number" id="limit" name="limit" value="-1" min="-1" class="small-text">
                        <p class="description"><?php esc_html_e( 'Number of courses to show. Use -1 for unlimited.', 'simple-lms' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Display Elements', 'simple-lms' ); ?></label></th>
                    <td>
                        <p class="description"><?php esc_html_e( 'Drag to reorder. Check to display.', 'simple-lms' ); ?></p>
                        <ul id="elements-sortable" class="elements-sortable">
                            <?php foreach ( $available_elements as $key => $label ) : ?>
                            <li data-element="<?php echo esc_attr( $key ); ?>">
                                <span class="dashicons dashicons-menu handle"></span>
                                <label>
                                    <input type="checkbox" name="elements[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $default_elements, true ) ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </label>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                </tr>
            </table>

            <p>
                <button type="submit" name="simple_lms_add_preset" class="button button-primary"><?php esc_html_e( 'Add New Preset', 'simple-lms' ); ?></button>
            </p>
        </form>
        <?php endif; ?>
    </div>

    <div class="taxonomy-list-column">
        <h2><?php esc_html_e( 'Presets', 'simple-lms' ); ?></h2>

        <?php if ( ! empty( $presets ) ) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Name', 'simple-lms' ); ?></th>
                    <th><?php esc_html_e( 'Shortcode', 'simple-lms' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'simple-lms' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $presets as $preset_slug => $preset ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $preset_slug ); ?></strong></td>
                    <td><code>[lms_courses preset="<?php echo esc_attr( $preset_slug ); ?>"]</code></td>
                    <td>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-lms-settings&tab=shortcodes&action=edit&preset=' . $preset_slug ) ); ?>">
                            <?php esc_html_e( 'Edit', 'simple-lms' ); ?>
                        </a>
                        |
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=simple-lms-settings&tab=shortcodes&action=delete&preset=' . $preset_slug ), 'delete_preset_' . $preset_slug ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this preset?', 'simple-lms' ); ?>');">
                            <?php esc_html_e( 'Delete', 'simple-lms' ); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p><?php esc_html_e( 'No presets created yet.', 'simple-lms' ); ?></p>
        <?php endif; ?>
    </div>
</div>
