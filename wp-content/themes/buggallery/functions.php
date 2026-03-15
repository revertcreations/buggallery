<?php
/**
 * BugGallery Theme Functions
 *
 * Mobile-first theme for the BugGallery unmanned art gallery.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BUGGALLERY_THEME_VERSION', '1.0.0' );

/**
 * Theme setup
 */
function buggallery_theme_setup(): void {
    // Add theme support
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo', [
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ] );
    add_theme_support( 'html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ] );

    // WooCommerce support
    add_theme_support( 'woocommerce' );

    // Image sizes for bug photos
    add_image_size( 'bug-hero', 1200, 900, true );      // Hero image on bug page
    add_image_size( 'bug-card', 600, 450, true );        // Card in grid
    add_image_size( 'bug-thumbnail', 300, 225, true );   // Small thumbnail

    // Nav menus
    register_nav_menus( [
        'primary' => __( 'Primary Menu', 'buggallery' ),
    ] );
}
add_action( 'after_setup_theme', 'buggallery_theme_setup' );

/**
 * Enqueue theme styles and scripts
 */
function buggallery_enqueue_assets(): void {
    // Main theme stylesheet
    wp_enqueue_style(
        'buggallery-style',
        get_stylesheet_uri(),
        [],
        BUGGALLERY_THEME_VERSION
    );

    // Google Fonts — Inter
    wp_enqueue_style(
        'buggallery-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        [],
        null
    );

    // Bug page specific styles
    if ( is_singular( 'bug_photo' ) ) {
        wp_enqueue_style(
            'buggallery-bug-page',
            get_template_directory_uri() . '/assets/css/bug-page.css',
            [ 'buggallery-style' ],
            BUGGALLERY_THEME_VERSION
        );
    }

    // Archive styles
    if ( is_post_type_archive( 'bug_photo' ) || is_tax( 'bug_species' ) ) {
        wp_enqueue_style(
            'buggallery-archive',
            get_template_directory_uri() . '/assets/css/archive.css',
            [ 'buggallery-style' ],
            BUGGALLERY_THEME_VERSION
        );
    }

    // Front page styles
    if ( is_front_page() ) {
        wp_enqueue_style(
            'buggallery-home',
            get_template_directory_uri() . '/assets/css/home.css',
            [ 'buggallery-style' ],
            BUGGALLERY_THEME_VERSION
        );
    }


}
add_action( 'wp_enqueue_scripts', 'buggallery_enqueue_assets' );

/**
 * Add viewport meta tag
 */
function buggallery_viewport_meta(): void {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">' . "\n";
    echo '<meta name="theme-color" content="#0a0a0a">' . "\n";
}
add_action( 'wp_head', 'buggallery_viewport_meta', 1 );



/**
 * Custom template for bug_photo single posts
 */
function buggallery_template_include( string $template ): string {
    if ( is_singular( 'bug_photo' ) ) {
        $custom = locate_template( 'templates/single-bug_photo.php' );
        if ( $custom ) {
            return $custom;
        }
    }

    if ( is_post_type_archive( 'bug_photo' ) ) {
        $custom = locate_template( 'templates/archive-bug_photo.php' );
        if ( $custom ) {
            return $custom;
        }
    }

    return $template;
}
add_filter( 'template_include', 'buggallery_template_include' );

/**
 * Helper: Get photographer website URL
 */
function buggallery_get_photographer_url( int $user_id ): string {
    return get_user_meta( $user_id, 'buggallery_website_url', true ) ?: '';
}

/**
 * WooCommerce cart fragments — update cart count via AJAX
 */
function buggallery_cart_fragment( array $fragments ): array {
    $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

    $fragments['.site-nav-cart-count'] = sprintf(
        '<span class="site-nav-cart-count%s">%s</span>',
        $cart_count ? '' : ' is-empty',
        esc_html( $cart_count )
    );

    return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'buggallery_cart_fragment' );

/**
 * Helper: Format price for display
 */
function buggallery_format_price( string $price ): string {
    if ( function_exists( 'wc_price' ) ) {
        return wc_price( $price );
    }
    return '$' . number_format( (float) $price, 2 );
}
