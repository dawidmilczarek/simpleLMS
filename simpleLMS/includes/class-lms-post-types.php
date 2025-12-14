<?php
/**
 * Custom Post Types and Taxonomies.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LMS_Post_Types class.
 */
class LMS_Post_Types {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
        add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 5 );
    }

    /**
     * Register custom post types.
     */
    public static function register_post_types() {
        $labels = array(
            'name'                  => _x( 'Courses', 'Post type general name', 'simple-lms' ),
            'singular_name'         => _x( 'Course', 'Post type singular name', 'simple-lms' ),
            'menu_name'             => _x( 'Courses', 'Admin Menu text', 'simple-lms' ),
            'name_admin_bar'        => _x( 'Course', 'Add New on Toolbar', 'simple-lms' ),
            'add_new'               => __( 'Add New', 'simple-lms' ),
            'add_new_item'          => __( 'Add New Course', 'simple-lms' ),
            'new_item'              => __( 'New Course', 'simple-lms' ),
            'edit_item'             => __( 'Edit Course', 'simple-lms' ),
            'view_item'             => __( 'View Course', 'simple-lms' ),
            'all_items'             => __( 'Courses', 'simple-lms' ),
            'search_items'          => __( 'Search Courses', 'simple-lms' ),
            'parent_item_colon'     => __( 'Parent Courses:', 'simple-lms' ),
            'not_found'             => __( 'No courses found.', 'simple-lms' ),
            'not_found_in_trash'    => __( 'No courses found in Trash.', 'simple-lms' ),
            'featured_image'        => _x( 'Course Cover Image', 'Overrides the "Featured Image" phrase', 'simple-lms' ),
            'set_featured_image'    => _x( 'Set cover image', 'Overrides the "Set featured image" phrase', 'simple-lms' ),
            'remove_featured_image' => _x( 'Remove cover image', 'Overrides the "Remove featured image" phrase', 'simple-lms' ),
            'use_featured_image'    => _x( 'Use as cover image', 'Overrides the "Use as featured image" phrase', 'simple-lms' ),
            'archives'              => _x( 'Course archives', 'The post type archive label', 'simple-lms' ),
            'insert_into_item'      => _x( 'Insert into course', 'Overrides the "Insert into post" phrase', 'simple-lms' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this course', 'Overrides the "Uploaded to this post" phrase', 'simple-lms' ),
            'filter_items_list'     => _x( 'Filter courses list', 'Screen reader text', 'simple-lms' ),
            'items_list_navigation' => _x( 'Courses list navigation', 'Screen reader text', 'simple-lms' ),
            'items_list'            => _x( 'Courses list', 'Screen reader text', 'simple-lms' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, // We add it to our custom menu.
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'course' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-welcome-learn-more',
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
            'show_in_rest'       => true,
        );

        register_post_type( 'simple_lms_course', $args );
    }

    /**
     * Register taxonomies.
     */
    public static function register_taxonomies() {
        // Course Categories.
        $cat_labels = array(
            'name'                       => _x( 'Course Categories', 'taxonomy general name', 'simple-lms' ),
            'singular_name'              => _x( 'Course Category', 'taxonomy singular name', 'simple-lms' ),
            'search_items'               => __( 'Search Categories', 'simple-lms' ),
            'popular_items'              => __( 'Popular Categories', 'simple-lms' ),
            'all_items'                  => __( 'All Categories', 'simple-lms' ),
            'parent_item'                => __( 'Parent Category', 'simple-lms' ),
            'parent_item_colon'          => __( 'Parent Category:', 'simple-lms' ),
            'edit_item'                  => __( 'Edit Category', 'simple-lms' ),
            'update_item'                => __( 'Update Category', 'simple-lms' ),
            'add_new_item'               => __( 'Add New Category', 'simple-lms' ),
            'new_item_name'              => __( 'New Category Name', 'simple-lms' ),
            'separate_items_with_commas' => __( 'Separate categories with commas', 'simple-lms' ),
            'add_or_remove_items'        => __( 'Add or remove categories', 'simple-lms' ),
            'choose_from_most_used'      => __( 'Choose from the most used categories', 'simple-lms' ),
            'not_found'                  => __( 'No categories found.', 'simple-lms' ),
            'menu_name'                  => __( 'Categories', 'simple-lms' ),
        );

        $cat_args = array(
            'hierarchical'      => true,
            'labels'            => $cat_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'course-category' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( 'simple_lms_category', array( 'simple_lms_course' ), $cat_args );

        // Course Tags.
        $tag_labels = array(
            'name'                       => _x( 'Course Tags', 'taxonomy general name', 'simple-lms' ),
            'singular_name'              => _x( 'Course Tag', 'taxonomy singular name', 'simple-lms' ),
            'search_items'               => __( 'Search Tags', 'simple-lms' ),
            'popular_items'              => __( 'Popular Tags', 'simple-lms' ),
            'all_items'                  => __( 'All Tags', 'simple-lms' ),
            'edit_item'                  => __( 'Edit Tag', 'simple-lms' ),
            'update_item'                => __( 'Update Tag', 'simple-lms' ),
            'add_new_item'               => __( 'Add New Tag', 'simple-lms' ),
            'new_item_name'              => __( 'New Tag Name', 'simple-lms' ),
            'separate_items_with_commas' => __( 'Separate tags with commas', 'simple-lms' ),
            'add_or_remove_items'        => __( 'Add or remove tags', 'simple-lms' ),
            'choose_from_most_used'      => __( 'Choose from the most used tags', 'simple-lms' ),
            'not_found'                  => __( 'No tags found.', 'simple-lms' ),
            'menu_name'                  => __( 'Tags', 'simple-lms' ),
        );

        $tag_args = array(
            'hierarchical'      => false,
            'labels'            => $tag_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'course-tag' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( 'simple_lms_tag', array( 'simple_lms_course' ), $tag_args );

        // Course Status.
        $status_labels = array(
            'name'                       => _x( 'Course Statuses', 'taxonomy general name', 'simple-lms' ),
            'singular_name'              => _x( 'Course Status', 'taxonomy singular name', 'simple-lms' ),
            'search_items'               => __( 'Search Statuses', 'simple-lms' ),
            'popular_items'              => __( 'Popular Statuses', 'simple-lms' ),
            'all_items'                  => __( 'All Statuses', 'simple-lms' ),
            'edit_item'                  => __( 'Edit Status', 'simple-lms' ),
            'update_item'                => __( 'Update Status', 'simple-lms' ),
            'add_new_item'               => __( 'Add New Status', 'simple-lms' ),
            'new_item_name'              => __( 'New Status Name', 'simple-lms' ),
            'separate_items_with_commas' => __( 'Separate statuses with commas', 'simple-lms' ),
            'add_or_remove_items'        => __( 'Add or remove statuses', 'simple-lms' ),
            'choose_from_most_used'      => __( 'Choose from the most used statuses', 'simple-lms' ),
            'not_found'                  => __( 'No statuses found.', 'simple-lms' ),
            'menu_name'                  => __( 'Statuses', 'simple-lms' ),
        );

        $status_args = array(
            'hierarchical'      => false,
            'labels'            => $status_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'course-status' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( 'simple_lms_status', array( 'simple_lms_course' ), $status_args );

        // Course Lecturers.
        $lecturer_labels = array(
            'name'                       => _x( 'Lecturers', 'taxonomy general name', 'simple-lms' ),
            'singular_name'              => _x( 'Lecturer', 'taxonomy singular name', 'simple-lms' ),
            'search_items'               => __( 'Search Lecturers', 'simple-lms' ),
            'popular_items'              => __( 'Popular Lecturers', 'simple-lms' ),
            'all_items'                  => __( 'All Lecturers', 'simple-lms' ),
            'edit_item'                  => __( 'Edit Lecturer', 'simple-lms' ),
            'update_item'                => __( 'Update Lecturer', 'simple-lms' ),
            'add_new_item'               => __( 'Add New Lecturer', 'simple-lms' ),
            'new_item_name'              => __( 'New Lecturer Name', 'simple-lms' ),
            'separate_items_with_commas' => __( 'Separate lecturers with commas', 'simple-lms' ),
            'add_or_remove_items'        => __( 'Add or remove lecturers', 'simple-lms' ),
            'choose_from_most_used'      => __( 'Choose from the most used lecturers', 'simple-lms' ),
            'not_found'                  => __( 'No lecturers found.', 'simple-lms' ),
            'menu_name'                  => __( 'Lecturers', 'simple-lms' ),
        );

        $lecturer_args = array(
            'hierarchical'      => false,
            'labels'            => $lecturer_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'course-lecturer' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( 'simple_lms_lecturer', array( 'simple_lms_course' ), $lecturer_args );
    }
}
