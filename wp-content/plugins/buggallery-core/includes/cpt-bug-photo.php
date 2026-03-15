<?php
/**
 * Custom Post Type: Bug Photo
 *
 * Registers the bug_photo CPT which serves as the central content type.
 * Each bug photo has: image, story, photographer, related links, and purchase options.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BugGallery_CPT_Bug_Photo {

    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'register' ] );
        add_action( 'init', [ __CLASS__, 'register_taxonomy' ] );
    }

    /**
     * Register the bug_photo custom post type
     */
    public static function register(): void {
        $labels = [
            'name'                  => __( 'Bug Photos', 'buggallery' ),
            'singular_name'         => __( 'Bug Photo', 'buggallery' ),
            'menu_name'             => __( 'Bug Gallery', 'buggallery' ),
            'add_new'               => __( 'Add New Bug', 'buggallery' ),
            'add_new_item'          => __( 'Add New Bug Photo', 'buggallery' ),
            'edit_item'             => __( 'Edit Bug Photo', 'buggallery' ),
            'new_item'              => __( 'New Bug Photo', 'buggallery' ),
            'view_item'             => __( 'View Bug Photo', 'buggallery' ),
            'search_items'          => __( 'Search Bug Photos', 'buggallery' ),
            'not_found'             => __( 'No bug photos found', 'buggallery' ),
            'not_found_in_trash'    => __( 'No bug photos found in trash', 'buggallery' ),
            'all_items'             => __( 'All Bug Photos', 'buggallery' ),
            'featured_image'        => __( 'Bug Photo Image', 'buggallery' ),
            'set_featured_image'    => __( 'Set bug photo image', 'buggallery' ),
            'remove_featured_image' => __( 'Remove bug photo image', 'buggallery' ),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => [ 'slug' => 'bug', 'with_front' => false ],
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-camera',
            'supports'            => [
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'author',
                'custom-fields',
            ],
            'template'            => [
                [ 'core/paragraph', [ 'placeholder' => 'Tell the story of this bug...' ] ],
            ],
        ];

        register_post_type( 'bug_photo', $args );
    }

    /**
     * Register bug species taxonomy
     */
    public static function register_taxonomy(): void {
        $labels = [
            'name'              => __( 'Bug Species', 'buggallery' ),
            'singular_name'     => __( 'Species', 'buggallery' ),
            'search_items'      => __( 'Search Species', 'buggallery' ),
            'all_items'         => __( 'All Species', 'buggallery' ),
            'parent_item'       => __( 'Parent Species', 'buggallery' ),
            'parent_item_colon' => __( 'Parent Species:', 'buggallery' ),
            'edit_item'         => __( 'Edit Species', 'buggallery' ),
            'update_item'       => __( 'Update Species', 'buggallery' ),
            'add_new_item'      => __( 'Add New Species', 'buggallery' ),
            'new_item_name'     => __( 'New Species Name', 'buggallery' ),
            'menu_name'         => __( 'Species', 'buggallery' ),
        ];

        register_taxonomy( 'bug_species', 'bug_photo', [
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'species' ],
        ] );
    }
}

BugGallery_CPT_Bug_Photo::init();
