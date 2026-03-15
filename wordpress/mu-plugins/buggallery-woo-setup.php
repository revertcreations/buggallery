<?php
/**
 * Plugin Name: BugGallery WooCommerce Auto-Setup
 * Description: Must-use plugin that auto-configures WooCommerce settings for the BugGallery POC.
 * Version: 1.0.0
 * Author: Revert Creations
 *
 * This runs on every page load as a must-use plugin.
 * It checks a flag option and only runs setup once.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', function() {
    // Only run once — skip if already configured
    if ( get_option( 'buggallery_woo_configured' ) ) {
        return;
    }

    // Wait for WooCommerce to be available
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    // --- Core WooCommerce settings ---
    update_option( 'woocommerce_currency', 'USD' );
    update_option( 'woocommerce_default_country', 'US' );
    update_option( 'woocommerce_coming_soon', 'no' );

    // --- Checkout settings ---
    update_option( 'woocommerce_enable_guest_checkout', 'yes' );
    update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'no' );
    update_option( 'woocommerce_enable_checkout_login_prompt', 'no' );

    // --- Cart behavior (skip cart, go direct to checkout) ---
    update_option( 'woocommerce_cart_redirect_after_add', 'no' );

    // --- Shipping (flat rate for "Mail Me a Print") ---
    // Shipping is handled by Printful, so we use free shipping as default
    // and let Printful calculate actual costs later
    update_option( 'woocommerce_ship_to_countries', 'all' );

    // --- Tax (keep simple for POC) ---
    update_option( 'woocommerce_calc_taxes', 'no' );

    // --- Permalink / product base ---
    update_option( 'woocommerce_permalinks', [
        'product_base'           => '/product',
        'category_base'          => 'product-category',
        'tag_base'               => 'product-tag',
        'attribute_base'         => '',
        'use_verbose_page_rules' => false,
    ] );

    // --- Activate required plugins if not already active ---
    $active_plugins = get_option( 'active_plugins', [] );
    $plugins_to_activate = [
        'woocommerce/woocommerce.php',
        'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php',
        'buggallery-core/buggallery-core.php',
    ];

    $changed = false;
    foreach ( $plugins_to_activate as $plugin ) {
        if ( ! in_array( $plugin, $active_plugins, true ) ) {
            $active_plugins[] = $plugin;
            $changed = true;
        }
    }

    if ( $changed ) {
        sort( $active_plugins );
        update_option( 'active_plugins', $active_plugins );
    }

    // --- Set permalinks to post name ---
    update_option( 'permalink_structure', '/%postname%/' );

    // Mark as configured so this doesn't run again
    update_option( 'buggallery_woo_configured', true );

    // Flush rewrite rules on next load
    update_option( 'buggallery_flush_rewrites', true );
}, 1 );

// Flush rewrite rules once after configuration
add_action( 'init', function() {
    if ( get_option( 'buggallery_flush_rewrites' ) ) {
        flush_rewrite_rules();
        delete_option( 'buggallery_flush_rewrites' );
    }
}, 99 );
