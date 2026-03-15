<?php
/**
 * WooCommerce Integration
 *
 * Automatically creates/syncs WooCommerce products from bug_photo posts.
 * Each bug photo becomes a variable product with two variations:
 * - "Take from Wall" (immediate pickup)
 * - "Mail Me a Print" (Printful fulfillment)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BugGallery_Woo_Integration {

    public static function init(): void {
        // Only load if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        add_action( 'save_post_bug_photo', [ __CLASS__, 'sync_product' ], 30, 2 );
        add_action( 'before_delete_post', [ __CLASS__, 'delete_linked_product' ] );
        add_filter( 'woocommerce_add_to_cart_redirect', [ __CLASS__, 'redirect_to_checkout' ] );
        add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'add_order_line_attribution' ], 10, 4 );
    }

    /**
     * Create or update a WooCommerce product when a bug photo is saved
     */
    public static function sync_product( int $post_id, \WP_Post $post ): void {
        if ( 'publish' !== $post->post_status ) {
            return;
        }

        // Prevent infinite loops
        if ( defined( 'BUGGALLERY_SYNCING' ) ) {
            return;
        }
        define( 'BUGGALLERY_SYNCING', true );

        $wall_price = get_post_meta( $post_id, '_buggallery_wall_price', true );
        $mail_price = get_post_meta( $post_id, '_buggallery_mail_price', true );

        // Default prices if not set
        $wall_price = $wall_price ?: '150.00';
        $mail_price = $mail_price ?: '75.00';

        // Check if a linked product already exists
        $product_id = get_post_meta( $post_id, '_buggallery_product_id', true );

        if ( $product_id && get_post( $product_id ) ) {
            // Update existing product
            self::update_product( $product_id, $post, $wall_price, $mail_price );
        } else {
            // Create new product
            $product_id = self::create_product( $post, $wall_price, $mail_price );
            update_post_meta( $post_id, '_buggallery_product_id', $product_id );
        }
    }

    /**
     * Create a new WooCommerce product for a bug photo
     */
    private static function create_product( \WP_Post $bug_post, string $wall_price, string $mail_price ): int {
        $product = new \WC_Product_Variable();

        $product->set_name( $bug_post->post_title );
        $product->set_description( $bug_post->post_content );
        $product->set_short_description(
            sprintf( __( 'Large-format print of "%s"', 'buggallery' ), $bug_post->post_title )
        );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'hidden' ); // Only accessible via bug pages
        $product->set_virtual( false );

        // Set the bug photo as product image
        $thumbnail_id = get_post_thumbnail_id( $bug_post->ID );
        if ( $thumbnail_id ) {
            $product->set_image_id( $thumbnail_id );
        }

        // Store reference back to bug photo
        $product->update_meta_data( '_buggallery_bug_photo_id', $bug_post->ID );

        $product_id = $product->save();

        // Create the "Purchase Type" attribute
        self::create_purchase_variations( $product_id, $wall_price, $mail_price );

        return $product_id;
    }

    /**
     * Update an existing WooCommerce product
     */
    private static function update_product( int $product_id, \WP_Post $bug_post, string $wall_price, string $mail_price ): void {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return;
        }

        $product->set_name( $bug_post->post_title );
        $product->set_description( $bug_post->post_content );

        $thumbnail_id = get_post_thumbnail_id( $bug_post->ID );
        if ( $thumbnail_id ) {
            $product->set_image_id( $thumbnail_id );
        }

        $product->save();

        // Update variation prices
        self::update_variation_prices( $product_id, $wall_price, $mail_price );
    }

    /**
     * Create purchase type variations (Take from Wall / Mail Me a Print)
     */
    private static function create_purchase_variations( int $product_id, string $wall_price, string $mail_price ): void {
        // Register the attribute
        $attribute = new \WC_Product_Attribute();
        $attribute->set_name( 'Purchase Type' );
        $attribute->set_options( [ 'Take from Wall', 'Mail Me a Print' ] );
        $attribute->set_position( 0 );
        $attribute->set_visible( true );
        $attribute->set_variation( true );

        $product = wc_get_product( $product_id );
        $product->set_attributes( [ $attribute ] );
        $product->save();

        // Create "Take from Wall" variation
        $wall_variation = new \WC_Product_Variation();
        $wall_variation->set_parent_id( $product_id );
        $wall_variation->set_attributes( [ 'purchase-type' => 'Take from Wall' ] );
        $wall_variation->set_regular_price( $wall_price );
        $wall_variation->set_virtual( true ); // No shipping needed
        $wall_variation->set_status( 'publish' );
        $wall_variation->update_meta_data( '_buggallery_purchase_type', 'wall' );
        $wall_variation->save();

        // Create "Mail Me a Print" variation
        $mail_variation = new \WC_Product_Variation();
        $mail_variation->set_parent_id( $product_id );
        $mail_variation->set_attributes( [ 'purchase-type' => 'Mail Me a Print' ] );
        $mail_variation->set_regular_price( $mail_price );
        $mail_variation->set_virtual( false ); // Requires shipping
        $mail_variation->set_status( 'publish' );
        $mail_variation->update_meta_data( '_buggallery_purchase_type', 'mail' );
        $mail_variation->save();
    }

    /**
     * Update variation prices
     */
    private static function update_variation_prices( int $product_id, string $wall_price, string $mail_price ): void {
        $product = wc_get_product( $product_id );
        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            return;
        }

        foreach ( $product->get_children() as $variation_id ) {
            $variation = wc_get_product( $variation_id );
            if ( ! $variation ) {
                continue;
            }

            $type = $variation->get_meta( '_buggallery_purchase_type' );
            if ( 'wall' === $type ) {
                $variation->set_regular_price( $wall_price );
            } elseif ( 'mail' === $type ) {
                $variation->set_regular_price( $mail_price );
            }
            $variation->save();
        }
    }

    /**
     * Delete linked WooCommerce product when bug photo is deleted
     */
    public static function delete_linked_product( int $post_id ): void {
        if ( 'bug_photo' !== get_post_type( $post_id ) ) {
            return;
        }

        $product_id = get_post_meta( $post_id, '_buggallery_product_id', true );
        if ( $product_id ) {
            wp_delete_post( $product_id, true );
        }
    }

    /**
     * Skip cart and go directly to checkout for a faster mobile experience.
     * Only applies to BugGallery products — other WooCommerce products use the default cart flow.
     */
    public static function redirect_to_checkout( string $url ): string {
        // Check if the product being added is a BugGallery product
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $product_id = isset( $_REQUEST['add-to-cart'] ) ? absint( $_REQUEST['add-to-cart'] ) : 0;
        if ( ! $product_id ) {
            return $url;
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return $url;
        }

        // Check for BugGallery meta on the product (or its parent for variations)
        $check_id  = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product_id;
        $bug_photo = get_post_meta( $check_id, '_buggallery_bug_photo_id', true );

        if ( ! $bug_photo ) {
            return $url;
        }

        return wc_get_checkout_url();
    }

    /**
     * Get the WooCommerce product ID for a bug photo
     */
    public static function get_product_id( int $bug_photo_id ): ?int {
        $product_id = get_post_meta( $bug_photo_id, '_buggallery_product_id', true );
        return $product_id ? (int) $product_id : null;
    }

    /**
     * Get add-to-cart URLs for a bug photo's purchase options
     */
    public static function get_purchase_urls( int $bug_photo_id ): array {
        $product_id = self::ensure_linked_product( $bug_photo_id );
        if ( ! $product_id ) {
            return [ 'wall' => '#', 'mail' => '#' ];
        }

        $product = wc_get_product( $product_id );
        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            return [ 'wall' => '#', 'mail' => '#' ];
        }

        $urls = [ 'wall' => '#', 'mail' => '#' ];

        foreach ( $product->get_children() as $variation_id ) {
            $variation = wc_get_product( $variation_id );
            if ( ! $variation ) {
                continue;
            }

            $type = $variation->get_meta( '_buggallery_purchase_type' );
            if ( $type && isset( $urls[ $type ] ) ) {
                $urls[ $type ] = add_query_arg( [
                    'add-to-cart' => $product_id,
                    'variation_id' => $variation_id,
                    'attribute_purchase-type' => $type === 'wall' ? 'Take from Wall' : 'Mail Me a Print',
                ], wc_get_checkout_url() );
            }
        }

        return $urls;
    }

    /**
     * Ensure a published bug photo has a linked WooCommerce product.
     */
    private static function ensure_linked_product( int $bug_photo_id ): ?int {
        $product_id = self::get_product_id( $bug_photo_id );
        if ( $product_id && get_post( $product_id ) ) {
            return $product_id;
        }

        $bug_post = get_post( $bug_photo_id );
        if ( ! ( $bug_post instanceof \WP_Post ) || 'bug_photo' !== $bug_post->post_type ) {
            return null;
        }

        if ( 'publish' !== $bug_post->post_status ) {
            return null;
        }

        self::sync_product( $bug_photo_id, $bug_post );

        $product_id = self::get_product_id( $bug_photo_id );
        if ( $product_id && get_post( $product_id ) ) {
            return $product_id;
        }

        return null;
    }

    /**
     * Persist attribution metadata so each sale is linked to the photographer.
     */
    public static function add_order_line_attribution( \WC_Order_Item_Product $item, string $cart_item_key, array $values, \WC_Order $order ): void {
        $product = $item->get_product();
        if ( ! $product ) {
            return;
        }

        $parent_product = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;
        if ( ! $parent_product ) {
            return;
        }

        $bug_photo_id = (int) $parent_product->get_meta( '_buggallery_bug_photo_id' );
        if ( ! $bug_photo_id ) {
            return;
        }

        $item->add_meta_data( '_buggallery_bug_photo_id', $bug_photo_id, true );

        $author_id = (int) get_post_field( 'post_author', $bug_photo_id );
        if ( $author_id ) {
            $item->add_meta_data( '_buggallery_photographer_id', $author_id, true );
            $item->add_meta_data( '_buggallery_photographer_name', get_the_author_meta( 'display_name', $author_id ), true );
        }

        $purchase_type = $product->get_meta( '_buggallery_purchase_type' );
        if ( $purchase_type ) {
            $item->add_meta_data( '_buggallery_purchase_type', $purchase_type, true );
        }
    }
}

// Initialize after WooCommerce loads
add_action( 'woocommerce_loaded', [ 'BugGallery_Woo_Integration', 'init' ] );
