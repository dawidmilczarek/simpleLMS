<?php
/**
 * Course data helper class.
 *
 * Provides unified methods for fetching course data used by templates and shortcodes.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LMS_Course_Data class.
 */
class LMS_Course_Data {

	/**
	 * Get course data.
	 *
	 * @param int  $post_id         Course post ID.
	 * @param bool $include_content Whether to include post content (default: false).
	 * @return array
	 */
	public static function get( $post_id, $include_content = false ) {
		$post = get_post( $post_id );

		// Get meta fields.
		$date       = get_post_meta( $post_id, '_simple_lms_date', true );
		$time_start = get_post_meta( $post_id, '_simple_lms_time_start', true );
		$time_end   = get_post_meta( $post_id, '_simple_lms_time_end', true );
		$duration   = get_post_meta( $post_id, '_simple_lms_duration', true );
		$videos     = get_post_meta( $post_id, '_simple_lms_videos', true );
		$materials  = get_post_meta( $post_id, '_simple_lms_materials', true );

		// Get taxonomies.
		$categories     = wp_get_post_terms( $post_id, 'simple_lms_category' );
		$tags           = wp_get_post_terms( $post_id, 'simple_lms_tag' );
		$statuses       = wp_get_post_terms( $post_id, 'simple_lms_status' );
		$lecturer_terms = wp_get_post_terms( $post_id, 'simple_lms_lecturer', array( 'fields' => 'names' ) );
		$lecturer       = ! empty( $lecturer_terms ) && ! is_wp_error( $lecturer_terms ) ? implode( ', ', $lecturer_terms ) : '';

		// Format date.
		$date_format    = Simple_LMS::get_setting( 'date_format', 'd.m.Y' );
		$formatted_date = '';
		if ( ! empty( $date ) ) {
			$timestamp = strtotime( $date );
			if ( $timestamp ) {
				$formatted_date = date_i18n( $date_format, $timestamp );
			}
		}

		// Format time range.
		$time_range = '';
		if ( ! empty( $time_start ) && ! empty( $time_end ) ) {
			$time_range = $time_start . ' - ' . $time_end;
		} elseif ( ! empty( $time_start ) ) {
			$time_range = $time_start;
		}

		$data = array(
			'title'     => get_the_title( $post_id ),
			'date'      => $formatted_date,
			'date_raw'  => $date,
			'time'      => $time_range,
			'duration'  => $duration,
			'lecturer'  => $lecturer,
			'videos'    => is_array( $videos ) ? $videos : array(),
			'materials' => is_array( $materials ) ? $materials : array(),
			'category'  => ! empty( $categories ) && ! is_wp_error( $categories ) ? $categories[0]->name : '',
			'tags'      => ! empty( $tags ) && ! is_wp_error( $tags ) ? implode( ', ', wp_list_pluck( $tags, 'name' ) ) : '',
			'status'    => ! empty( $statuses ) && ! is_wp_error( $statuses ) ? $statuses[0]->name : '',
		);

		// Include content if requested (for templates).
		if ( $include_content && $post ) {
			$data['content']     = apply_filters( 'the_content', $post->post_content );
			$data['raw_content'] = $post->post_content;
		}

		return $data;
	}

	/**
	 * Get membership plans.
	 *
	 * @return array
	 */
	public static function get_membership_plans() {
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
	 * @param string $search       Optional search term.
	 * @param array  $selected_ids Optional array of selected product IDs to include.
	 * @return array
	 */
	public static function get_subscription_products( $search = '', $selected_ids = array() ) {
		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			return array();
		}

		$status_filter = Simple_LMS::get_setting( 'product_status_filter', 'publish' );
		$status        = 'any' === $status_filter ? array( 'publish', 'draft', 'trash' ) : 'publish';

		$args = array(
			'type'   => array( 'subscription', 'variable-subscription' ),
			'limit'  => 50,
			'status' => $status,
		);

		// If searching, add search parameter.
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$products = wc_get_products( $args );

		// Also fetch selected products that might not be in the search results.
		if ( ! empty( $selected_ids ) ) {
			$selected_products = wc_get_products(
				array(
					'type'    => array( 'subscription', 'variable-subscription' ),
					'include' => $selected_ids,
					'status'  => $status,
					'limit'   => -1,
				)
			);

			// Merge and dedupe.
			$product_ids = array_map(
				function ( $p ) {
					return $p->get_id();
				},
				$products
			);
			foreach ( $selected_products as $selected ) {
				if ( ! in_array( $selected->get_id(), $product_ids, true ) ) {
					$products[] = $selected;
				}
			}
		}

		return $products;
	}
}
