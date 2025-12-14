<?php
/**
 * Meta Boxes for Course post type.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LMS_Meta_Boxes class.
 */
class LMS_Meta_Boxes {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_simple_lms_course', array( $this, 'save_meta_boxes' ), 10, 2 );
    }

    /**
     * Add meta boxes.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'simple_lms_course_details',
            __( 'Course Details', 'simple-lms' ),
            array( $this, 'render_course_details_meta_box' ),
            'simple_lms_course',
            'normal',
            'high'
        );

        add_meta_box(
            'simple_lms_course_videos',
            __( 'Videos', 'simple-lms' ),
            array( $this, 'render_videos_meta_box' ),
            'simple_lms_course',
            'normal',
            'high'
        );

        add_meta_box(
            'simple_lms_course_materials',
            __( 'Materials', 'simple-lms' ),
            array( $this, 'render_materials_meta_box' ),
            'simple_lms_course',
            'normal',
            'high'
        );

        add_meta_box(
            'simple_lms_access_control',
            __( 'Access Control', 'simple-lms' ),
            array( $this, 'render_access_control_meta_box' ),
            'simple_lms_course',
            'normal',
            'high'
        );
    }

    /**
     * Render course details meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_course_details_meta_box( $post ) {
        wp_nonce_field( 'simple_lms_save_course', 'simple_lms_course_nonce' );

        $date       = get_post_meta( $post->ID, '_simple_lms_date', true );
        $time_start = get_post_meta( $post->ID, '_simple_lms_time_start', true );
        $time_end   = get_post_meta( $post->ID, '_simple_lms_time_end', true );
        $duration   = get_post_meta( $post->ID, '_simple_lms_duration', true );
        $lecturer   = get_post_meta( $post->ID, '_simple_lms_lecturer', true );

        // Get defaults for new courses.
        if ( 'auto-draft' === $post->post_status ) {
            $settings   = get_option( 'simple_lms_settings', array() );
            $time_start = ! empty( $settings['default_time_start'] ) ? $settings['default_time_start'] : $time_start;
            $time_end   = ! empty( $settings['default_time_end'] ) ? $settings['default_time_end'] : $time_end;
            $duration   = ! empty( $settings['default_duration'] ) ? $settings['default_duration'] : $duration;
            $lecturer   = ! empty( $settings['default_lecturer'] ) ? $settings['default_lecturer'] : $lecturer;
        }
        ?>
        <table class="form-table simple-lms-meta-table">
            <tr>
                <th><label for="simple_lms_date"><?php esc_html_e( 'Date', 'simple-lms' ); ?></label></th>
                <td>
                    <input type="date" id="simple_lms_date" name="simple_lms_date" value="<?php echo esc_attr( $date ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="simple_lms_time_start"><?php esc_html_e( 'Time Range', 'simple-lms' ); ?></label></th>
                <td>
                    <input type="time" id="simple_lms_time_start" name="simple_lms_time_start" value="<?php echo esc_attr( $time_start ); ?>" class="small-text">
                    <span> - </span>
                    <input type="time" id="simple_lms_time_end" name="simple_lms_time_end" value="<?php echo esc_attr( $time_end ); ?>" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label for="simple_lms_duration"><?php esc_html_e( 'Duration', 'simple-lms' ); ?></label></th>
                <td>
                    <input type="text" id="simple_lms_duration" name="simple_lms_duration" value="<?php echo esc_attr( $duration ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., 6h', 'simple-lms' ); ?>">
                    <p class="description"><?php esc_html_e( 'Auto-calculated from time range, but you can edit it.', 'simple-lms' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="simple_lms_lecturer"><?php esc_html_e( 'Lecturer', 'simple-lms' ); ?></label></th>
                <td>
                    <input type="text" id="simple_lms_lecturer" name="simple_lms_lecturer" value="<?php echo esc_attr( $lecturer ); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render videos meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_videos_meta_box( $post ) {
        $videos   = get_post_meta( $post->ID, '_simple_lms_videos', true );
        $videos   = is_array( $videos ) ? $videos : array();
        $settings = get_option( 'simple_lms_settings', array() );
        $default_title = isset( $settings['default_video_title'] ) ? $settings['default_video_title'] : '';
        ?>
        <div class="simple-lms-repeater" id="simple-lms-videos-repeater" data-default-title="<?php echo esc_attr( $default_title ); ?>">
            <div class="repeater-items">
                <?php
                if ( ! empty( $videos ) ) {
                    foreach ( $videos as $index => $video ) {
                        $this->render_video_row( $index, $video );
                    }
                }
                ?>
            </div>
            <button type="button" class="button add-repeater-item" data-type="video">
                <?php esc_html_e( '+ Add Video', 'simple-lms' ); ?>
            </button>
        </div>

        <script type="text/html" id="tmpl-simple-lms-video-row">
            <?php $this->render_video_row( '{{INDEX}}', array( 'title' => '', 'vimeo_url' => '' ), true ); ?>
        </script>
        <?php
    }

    /**
     * Render a single video row.
     *
     * @param int|string $index Row index.
     * @param array      $video Video data.
     * @param bool       $is_template Whether this is a template.
     */
    private function render_video_row( $index, $video, $is_template = false ) {
        $title     = isset( $video['title'] ) ? $video['title'] : '';
        $vimeo_url = isset( $video['vimeo_url'] ) ? $video['vimeo_url'] : '';
        ?>
        <div class="repeater-item video-item">
            <div class="repeater-item-header">
                <span class="dashicons dashicons-menu handle"></span>
                <span class="item-title"><?php echo esc_html( $title ? $title : __( 'Video', 'simple-lms' ) ); ?></span>
                <button type="button" class="button-link remove-repeater-item"><?php esc_html_e( 'Remove', 'simple-lms' ); ?></button>
            </div>
            <div class="repeater-item-content">
                <p>
                    <label><?php esc_html_e( 'Title', 'simple-lms' ); ?></label>
                    <input type="text" name="simple_lms_videos[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $title ); ?>" class="widefat video-title-input">
                </p>
                <p>
                    <label><?php esc_html_e( 'Vimeo URL', 'simple-lms' ); ?></label>
                    <input type="url" name="simple_lms_videos[<?php echo esc_attr( $index ); ?>][vimeo_url]" value="<?php echo esc_attr( $vimeo_url ); ?>" class="widefat" placeholder="https://vimeo.com/123456789">
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render materials meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_materials_meta_box( $post ) {
        $materials = get_post_meta( $post->ID, '_simple_lms_materials', true );
        $materials = is_array( $materials ) ? $materials : array();
        $settings  = get_option( 'simple_lms_settings', array() );
        $default_label = isset( $settings['default_material_label'] ) ? $settings['default_material_label'] : '';
        ?>
        <div class="simple-lms-repeater" id="simple-lms-materials-repeater" data-default-label="<?php echo esc_attr( $default_label ); ?>">
            <div class="repeater-items">
                <?php
                if ( ! empty( $materials ) ) {
                    foreach ( $materials as $index => $material ) {
                        $this->render_material_row( $index, $material );
                    }
                }
                ?>
            </div>
            <button type="button" class="button add-repeater-item" data-type="material">
                <?php esc_html_e( '+ Add Material', 'simple-lms' ); ?>
            </button>
        </div>

        <script type="text/html" id="tmpl-simple-lms-material-row">
            <?php $this->render_material_row( '{{INDEX}}', array( 'label' => '', 'url' => '' ), true ); ?>
        </script>
        <?php
    }

    /**
     * Render a single material row.
     *
     * @param int|string $index Row index.
     * @param array      $material Material data.
     * @param bool       $is_template Whether this is a template.
     */
    private function render_material_row( $index, $material, $is_template = false ) {
        $label = isset( $material['label'] ) ? $material['label'] : '';
        $url   = isset( $material['url'] ) ? $material['url'] : '';
        ?>
        <div class="repeater-item material-item">
            <div class="repeater-item-header">
                <span class="dashicons dashicons-menu handle"></span>
                <span class="item-title"><?php echo esc_html( $label ? $label : __( 'Material', 'simple-lms' ) ); ?></span>
                <button type="button" class="button-link remove-repeater-item"><?php esc_html_e( 'Remove', 'simple-lms' ); ?></button>
            </div>
            <div class="repeater-item-content">
                <p>
                    <label><?php esc_html_e( 'Label', 'simple-lms' ); ?></label>
                    <input type="text" name="simple_lms_materials[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $label ); ?>" class="widefat material-label-input">
                </p>
                <p>
                    <label><?php esc_html_e( 'URL', 'simple-lms' ); ?></label>
                    <input type="url" name="simple_lms_materials[<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $url ); ?>" class="widefat" placeholder="https://example.com/file.pdf">
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render access control meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_access_control_meta_box( $post ) {
        $memberships  = get_post_meta( $post->ID, '_simple_lms_access_memberships', true );
        $memberships  = is_array( $memberships ) ? $memberships : array();
        $products     = get_post_meta( $post->ID, '_simple_lms_access_products', true );
        $products     = is_array( $products ) ? $products : array();
        $redirect_url = get_post_meta( $post->ID, '_simple_lms_redirect_url', true );

        $has_memberships   = function_exists( 'wc_memberships' );
        $has_subscriptions = class_exists( 'WC_Subscriptions' );
        ?>
        <table class="form-table simple-lms-meta-table">
            <?php if ( $has_memberships ) : ?>
            <tr>
                <th><label><?php esc_html_e( 'Required Memberships', 'simple-lms' ); ?></label></th>
                <td>
                    <?php
                    $plans = $this->get_membership_plans();
                    if ( ! empty( $plans ) ) {
                        foreach ( $plans as $plan ) {
                            ?>
                            <label class="simple-lms-checkbox">
                                <input type="checkbox" name="simple_lms_access_memberships[]" value="<?php echo esc_attr( $plan->ID ); ?>" <?php checked( in_array( $plan->ID, $memberships, true ) ); ?>>
                                <?php echo esc_html( $plan->post_title ); ?>
                            </label><br>
                            <?php
                        }
                    } else {
                        esc_html_e( 'No membership plans found.', 'simple-lms' );
                    }
                    ?>
                    <p class="description"><?php esc_html_e( 'User needs ANY of the selected memberships (OR logic).', 'simple-lms' ); ?></p>
                </td>
            </tr>
            <?php else : ?>
            <tr>
                <th><?php esc_html_e( 'Memberships', 'simple-lms' ); ?></th>
                <td>
                    <p class="description"><?php esc_html_e( 'WooCommerce Memberships is not active.', 'simple-lms' ); ?></p>
                </td>
            </tr>
            <?php endif; ?>

            <?php if ( $has_subscriptions ) : ?>
            <tr>
                <th><label><?php esc_html_e( 'Required Subscription Products', 'simple-lms' ); ?></label></th>
                <td>
                    <?php
                    $subscription_products = $this->get_subscription_products();
                    if ( ! empty( $subscription_products ) ) {
                        foreach ( $subscription_products as $product ) {
                            ?>
                            <label class="simple-lms-checkbox">
                                <input type="checkbox" name="simple_lms_access_products[]" value="<?php echo esc_attr( $product->get_id() ); ?>" <?php checked( in_array( $product->get_id(), $products, true ) ); ?>>
                                <?php echo esc_html( $product->get_name() ); ?>
                            </label><br>
                            <?php
                        }
                    } else {
                        esc_html_e( 'No subscription products found.', 'simple-lms' );
                    }
                    ?>
                    <p class="description"><?php esc_html_e( 'User needs active subscription to ANY of the selected products (OR logic).', 'simple-lms' ); ?></p>
                </td>
            </tr>
            <?php else : ?>
            <tr>
                <th><?php esc_html_e( 'Subscriptions', 'simple-lms' ); ?></th>
                <td>
                    <p class="description"><?php esc_html_e( 'WooCommerce Subscriptions is not active.', 'simple-lms' ); ?></p>
                </td>
            </tr>
            <?php endif; ?>

            <tr>
                <th><label for="simple_lms_redirect_url"><?php esc_html_e( 'Redirect URL', 'simple-lms' ); ?></label></th>
                <td>
                    <input type="url" id="simple_lms_redirect_url" name="simple_lms_redirect_url" value="<?php echo esc_attr( $redirect_url ); ?>" class="regular-text" placeholder="<?php echo esc_attr( Simple_LMS::get_setting( 'redirect_url', '/sklep/' ) ); ?>">
                    <p class="description"><?php esc_html_e( 'Where to redirect users without access. Leave empty to use the default from settings.', 'simple-lms' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Get membership plans.
     *
     * @return array
     */
    private function get_membership_plans() {
        if ( ! function_exists( 'wc_memberships' ) ) {
            return array();
        }

        return get_posts(
            array(
                'post_type'      => 'wc_membership_plan',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            )
        );
    }

    /**
     * Get subscription products.
     *
     * @return array
     */
    private function get_subscription_products() {
        if ( ! class_exists( 'WC_Subscriptions' ) ) {
            return array();
        }

        $products = wc_get_products(
            array(
                'type'   => array( 'subscription', 'variable-subscription' ),
                'limit'  => -1,
                'status' => 'publish',
            )
        );

        return $products;
    }

    /**
     * Save meta boxes.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post Post object.
     */
    public function save_meta_boxes( $post_id, $post ) {
        // Verify nonce.
        if ( ! isset( $_POST['simple_lms_course_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['simple_lms_course_nonce'] ) ), 'simple_lms_save_course' ) ) {
            return;
        }

        // Check autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save course details.
        if ( isset( $_POST['simple_lms_date'] ) ) {
            update_post_meta( $post_id, '_simple_lms_date', sanitize_text_field( wp_unslash( $_POST['simple_lms_date'] ) ) );
        }

        if ( isset( $_POST['simple_lms_time_start'] ) ) {
            update_post_meta( $post_id, '_simple_lms_time_start', sanitize_text_field( wp_unslash( $_POST['simple_lms_time_start'] ) ) );
        }

        if ( isset( $_POST['simple_lms_time_end'] ) ) {
            update_post_meta( $post_id, '_simple_lms_time_end', sanitize_text_field( wp_unslash( $_POST['simple_lms_time_end'] ) ) );
        }

        if ( isset( $_POST['simple_lms_duration'] ) ) {
            update_post_meta( $post_id, '_simple_lms_duration', sanitize_text_field( wp_unslash( $_POST['simple_lms_duration'] ) ) );
        }

        if ( isset( $_POST['simple_lms_lecturer'] ) ) {
            update_post_meta( $post_id, '_simple_lms_lecturer', sanitize_text_field( wp_unslash( $_POST['simple_lms_lecturer'] ) ) );
        }

        // Save videos.
        if ( isset( $_POST['simple_lms_videos'] ) && is_array( $_POST['simple_lms_videos'] ) ) {
            $videos = array();
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            foreach ( wp_unslash( $_POST['simple_lms_videos'] ) as $video ) {
                if ( ! empty( $video['title'] ) || ! empty( $video['vimeo_url'] ) ) {
                    $videos[] = array(
                        'title'     => sanitize_text_field( $video['title'] ),
                        'vimeo_url' => esc_url_raw( $video['vimeo_url'] ),
                    );
                }
            }
            update_post_meta( $post_id, '_simple_lms_videos', $videos );
        } else {
            delete_post_meta( $post_id, '_simple_lms_videos' );
        }

        // Save materials.
        if ( isset( $_POST['simple_lms_materials'] ) && is_array( $_POST['simple_lms_materials'] ) ) {
            $materials = array();
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            foreach ( wp_unslash( $_POST['simple_lms_materials'] ) as $material ) {
                if ( ! empty( $material['label'] ) || ! empty( $material['url'] ) ) {
                    $materials[] = array(
                        'label' => sanitize_text_field( $material['label'] ),
                        'url'   => esc_url_raw( $material['url'] ),
                    );
                }
            }
            update_post_meta( $post_id, '_simple_lms_materials', $materials );
        } else {
            delete_post_meta( $post_id, '_simple_lms_materials' );
        }

        // Save access control.
        if ( isset( $_POST['simple_lms_access_memberships'] ) && is_array( $_POST['simple_lms_access_memberships'] ) ) {
            $memberships = array_map( 'absint', wp_unslash( $_POST['simple_lms_access_memberships'] ) );
            update_post_meta( $post_id, '_simple_lms_access_memberships', $memberships );
        } else {
            delete_post_meta( $post_id, '_simple_lms_access_memberships' );
        }

        if ( isset( $_POST['simple_lms_access_products'] ) && is_array( $_POST['simple_lms_access_products'] ) ) {
            $products = array_map( 'absint', wp_unslash( $_POST['simple_lms_access_products'] ) );
            update_post_meta( $post_id, '_simple_lms_access_products', $products );
        } else {
            delete_post_meta( $post_id, '_simple_lms_access_products' );
        }

        if ( isset( $_POST['simple_lms_redirect_url'] ) ) {
            update_post_meta( $post_id, '_simple_lms_redirect_url', esc_url_raw( wp_unslash( $_POST['simple_lms_redirect_url'] ) ) );
        }
    }
}
