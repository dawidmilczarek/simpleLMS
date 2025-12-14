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

$presets    = get_option( 'simple_lms_shortcode_presets', array() );
$statuses   = get_terms(
    array(
        'taxonomy'   => 'simple_lms_status',
        'hide_empty' => false,
    )
);
$categories = get_terms(
    array(
        'taxonomy'   => 'simple_lms_category',
        'hide_empty' => false,
    )
);
$tags       = get_terms(
    array(
        'taxonomy'   => 'simple_lms_tag',
        'hide_empty' => false,
    )
);

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
?>
<div class="simple-lms-shortcodes">
    <h2><?php esc_html_e( 'Shortcode Presets', 'simple-lms' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'Create presets to use with the [lms_courses] shortcode.', 'simple-lms' ); ?>
        <br>
        <?php esc_html_e( 'Usage: [lms_courses preset="preset-name"]', 'simple-lms' ); ?>
    </p>

    <div class="presets-list">
        <h3><?php esc_html_e( 'Existing Presets', 'simple-lms' ); ?></h3>
        <?php if ( ! empty( $presets ) ) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Name', 'simple-lms' ); ?></th>
                    <th><?php esc_html_e( 'Label', 'simple-lms' ); ?></th>
                    <th><?php esc_html_e( 'Shortcode', 'simple-lms' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'simple-lms' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $presets as $preset_name => $preset ) : ?>
                <tr data-preset="<?php echo esc_attr( $preset_name ); ?>">
                    <td><strong><?php echo esc_html( $preset_name ); ?></strong></td>
                    <td><?php echo esc_html( isset( $preset['label'] ) ? $preset['label'] : '' ); ?></td>
                    <td><code>[lms_courses preset="<?php echo esc_attr( $preset_name ); ?>"]</code></td>
                    <td>
                        <button type="button" class="button edit-preset" data-preset="<?php echo esc_attr( $preset_name ); ?>"><?php esc_html_e( 'Edit', 'simple-lms' ); ?></button>
                        <button type="button" class="button delete-preset" data-preset="<?php echo esc_attr( $preset_name ); ?>"><?php esc_html_e( 'Delete', 'simple-lms' ); ?></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p><?php esc_html_e( 'No presets created yet.', 'simple-lms' ); ?></p>
        <?php endif; ?>
    </div>

    <hr>

    <div class="preset-form-container">
        <h3 id="preset-form-title"><?php esc_html_e( 'Add New Preset', 'simple-lms' ); ?></h3>

        <form id="preset-form" class="preset-form">
            <input type="hidden" id="editing_preset" value="">

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="preset_name"><?php esc_html_e( 'Preset Name (slug)', 'simple-lms' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="preset_name" name="preset_name" class="regular-text" pattern="[a-z0-9\-]+" required>
                        <p class="description"><?php esc_html_e( 'Lowercase letters, numbers, and hyphens only. Used in shortcode.', 'simple-lms' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="preset_label"><?php esc_html_e( 'Label', 'simple-lms' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="preset_label" name="preset_label" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Human-readable name for admin reference.', 'simple-lms' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Filter by Status', 'simple-lms' ); ?></label>
                    </th>
                    <td>
                        <?php if ( ! empty( $statuses ) && ! is_wp_error( $statuses ) ) : ?>
                            <?php foreach ( $statuses as $status ) : ?>
                            <label class="simple-lms-checkbox">
                                <input type="checkbox" name="statuses[]" value="<?php echo esc_attr( $status->term_id ); ?>">
                                <?php echo esc_html( $status->name ); ?>
                            </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'Leave empty to show all statuses.', 'simple-lms' ); ?></p>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e( 'No statuses found.', 'simple-lms' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Filter by Category', 'simple-lms' ); ?></label>
                    </th>
                    <td>
                        <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                            <?php foreach ( $categories as $category ) : ?>
                            <label class="simple-lms-checkbox">
                                <input type="checkbox" name="categories[]" value="<?php echo esc_attr( $category->term_id ); ?>">
                                <?php echo esc_html( $category->name ); ?>
                            </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'Leave empty to show all categories.', 'simple-lms' ); ?></p>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e( 'No categories found.', 'simple-lms' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Filter by Tags', 'simple-lms' ); ?></label>
                    </th>
                    <td>
                        <?php if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) : ?>
                            <?php foreach ( $tags as $tag ) : ?>
                            <label class="simple-lms-checkbox">
                                <input type="checkbox" name="tags[]" value="<?php echo esc_attr( $tag->term_id ); ?>">
                                <?php echo esc_html( $tag->name ); ?>
                            </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'Leave empty to show all tags.', 'simple-lms' ); ?></p>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e( 'No tags found.', 'simple-lms' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="orderby"><?php esc_html_e( 'Order By', 'simple-lms' ); ?></label>
                    </th>
                    <td>
                        <select id="orderby" name="orderby">
                            <option value="date"><?php esc_html_e( 'Course Date', 'simple-lms' ); ?></option>
                            <option value="title"><?php esc_html_e( 'Title (alphabetical)', 'simple-lms' ); ?></option>
                            <option value="menu_order"><?php esc_html_e( 'Menu Order (manual)', 'simple-lms' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="order"><?php esc_html_e( 'Order', 'simple-lms' ); ?></label>
                    </th>
                    <td>
                        <select id="order" name="order">
                            <option value="DESC"><?php esc_html_e( 'Descending (newest/Z first)', 'simple-lms' ); ?></option>
                            <option value="ASC"><?php esc_html_e( 'Ascending (oldest/A first)', 'simple-lms' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="limit"><?php esc_html_e( 'Limit', 'simple-lms' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="limit" name="limit" value="-1" min="-1" class="small-text">
                        <p class="description"><?php esc_html_e( 'Number of courses to show. Use -1 for unlimited.', 'simple-lms' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="columns"><?php esc_html_e( 'Columns', 'simple-lms' ); ?></label>
                    </th>
                    <td>
                        <select id="columns" name="columns">
                            <option value="1"><?php esc_html_e( '1 column (full width)', 'simple-lms' ); ?></option>
                            <option value="2"><?php esc_html_e( '2 columns', 'simple-lms' ); ?></option>
                            <option value="3" selected><?php esc_html_e( '3 columns', 'simple-lms' ); ?></option>
                            <option value="4"><?php esc_html_e( '4 columns', 'simple-lms' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Display Elements', 'simple-lms' ); ?></label>
                    </th>
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

            <p class="submit">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Preset', 'simple-lms' ); ?></button>
                <button type="button" id="cancel-edit" class="button" style="display: none;"><?php esc_html_e( 'Cancel', 'simple-lms' ); ?></button>
            </p>
        </form>
    </div>
</div>

<script type="text/javascript">
var simpleLMSPresets = <?php echo wp_json_encode( $presets ); ?>;
</script>
