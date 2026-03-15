<?php
/**
 * Plugin Name: BugGallery Core
 * Description: Core functionality for the BugGallery unmanned art gallery — custom post types, photographer roles, QR codes, WooCommerce integration, and Printful fulfillment.
 * Version: 1.0.0
 * Author: Revert Creations
 * Author URI: https://revertcreations.com
 * Text Domain: buggallery
 * Requires at least: 6.4
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'BUGGALLERY_VERSION', '1.0.0' );
define( 'BUGGALLERY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BUGGALLERY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BUGGALLERY_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class
 */
final class BugGallery {

    private static ?BugGallery $instance = null;

    public static function instance(): BugGallery {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes(): void {
        require_once BUGGALLERY_PLUGIN_DIR . 'includes/cpt-bug-photo.php';
        require_once BUGGALLERY_PLUGIN_DIR . 'includes/roles.php';
        require_once BUGGALLERY_PLUGIN_DIR . 'includes/meta-boxes.php';
        require_once BUGGALLERY_PLUGIN_DIR . 'includes/qr-generator.php';
        require_once BUGGALLERY_PLUGIN_DIR . 'includes/woo-integration.php';
        require_once BUGGALLERY_PLUGIN_DIR . 'includes/printful-api.php';
        require_once BUGGALLERY_PLUGIN_DIR . 'includes/notifications.php';
        require_once BUGGALLERY_PLUGIN_DIR . 'includes/photographer-dashboard.php';
        require_once BUGGALLERY_PLUGIN_DIR . 'includes/photographer-upload-form.php';
        require_once BUGGALLERY_PLUGIN_DIR . 'includes/user-registration.php';
        require_once BUGGALLERY_PLUGIN_DIR . 'includes/user-login.php';
    }

    private function init_hooks(): void {
        register_activation_hook( BUGGALLERY_PLUGIN_FILE, [ $this, 'activate' ] );
        register_deactivation_hook( BUGGALLERY_PLUGIN_FILE, [ $this, 'deactivate' ] );

        add_action( 'init', [ $this, 'load_textdomain' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function activate(): void {
        BugGallery_Roles::create_roles();
        BugGallery_CPT_Bug_Photo::register();
        self::ensure_portal_pages();
        self::ensure_woocommerce_pages();
        flush_rewrite_rules();
    }

    /**
     * Ensure required front-end portal pages exist.
     */
    public static function ensure_portal_pages(): void {
        self::ensure_page( 'photographer-dashboard', 'Photographer Dashboard', '[buggallery_dashboard]' );
        self::ensure_page( 'photographer-register', 'Photographer Register', '[buggallery_register]' );
        self::ensure_page( 'photographer-login', 'Photographer Login', '[buggallery_login]' );
    }

    /**
     * Ensure WooCommerce core pages needed for the purchase flow are configured.
     */
    public static function ensure_woocommerce_pages(): void {
        // Use WooCommerce block-based cart/checkout for built-in mobile-first styling.
        $cart_block = '<!-- wp:woocommerce/cart --><div class="wp-block-woocommerce-cart alignwide is-loading"><!-- wp:woocommerce/filled-cart-block --><div class="wp-block-woocommerce-filled-cart-block"><!-- wp:woocommerce/cart-items-block --><div class="wp-block-woocommerce-cart-items-block"><!-- wp:woocommerce/cart-line-items-block --><div class="wp-block-woocommerce-cart-line-items-block"></div><!-- /wp:woocommerce/cart-line-items-block --></div><!-- /wp:woocommerce/cart-items-block --><!-- wp:woocommerce/cart-totals-block --><div class="wp-block-woocommerce-cart-totals-block"><!-- wp:woocommerce/cart-order-summary-block --><div class="wp-block-woocommerce-cart-order-summary-block"><!-- wp:woocommerce/cart-order-summary-subtotal-block --><div class="wp-block-woocommerce-cart-order-summary-subtotal-block"></div><!-- /wp:woocommerce/cart-order-summary-subtotal-block --><!-- wp:woocommerce/cart-order-summary-totals-block --><div class="wp-block-woocommerce-cart-order-summary-totals-block"></div><!-- /wp:woocommerce/cart-order-summary-totals-block --></div><!-- /wp:woocommerce/cart-order-summary-block --><!-- wp:woocommerce/proceed-to-checkout-block --><div class="wp-block-woocommerce-proceed-to-checkout-block"></div><!-- /wp:woocommerce/proceed-to-checkout-block --></div><!-- /wp:woocommerce/cart-totals-block --></div><!-- /wp:woocommerce/filled-cart-block --><!-- wp:woocommerce/empty-cart-block --><div class="wp-block-woocommerce-empty-cart-block"><!-- wp:paragraph --><p>Your cart is currently empty.</p><!-- /wp:paragraph --></div><!-- /wp:woocommerce/empty-cart-block --></div><!-- /wp:woocommerce/cart -->';

        $checkout_block = '<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading"><!-- wp:woocommerce/checkout-fields-block --><div class="wp-block-woocommerce-checkout-fields-block"><!-- wp:woocommerce/checkout-express-payment-block --><div class="wp-block-woocommerce-checkout-express-payment-block"></div><!-- /wp:woocommerce/checkout-express-payment-block --><!-- wp:woocommerce/checkout-contact-information-block --><div class="wp-block-woocommerce-checkout-contact-information-block"></div><!-- /wp:woocommerce/checkout-contact-information-block --><!-- wp:woocommerce/checkout-shipping-address-block --><div class="wp-block-woocommerce-checkout-shipping-address-block"></div><!-- /wp:woocommerce/checkout-shipping-address-block --><!-- wp:woocommerce/checkout-billing-address-block --><div class="wp-block-woocommerce-checkout-billing-address-block"></div><!-- /wp:woocommerce/checkout-billing-address-block --><!-- wp:woocommerce/checkout-shipping-methods-block --><div class="wp-block-woocommerce-checkout-shipping-methods-block"></div><!-- /wp:woocommerce/checkout-shipping-methods-block --><!-- wp:woocommerce/checkout-payment-block --><div class="wp-block-woocommerce-checkout-payment-block"></div><!-- /wp:woocommerce/checkout-payment-block --><!-- wp:woocommerce/checkout-actions-block --><div class="wp-block-woocommerce-checkout-actions-block"></div><!-- /wp:woocommerce/checkout-actions-block --></div><!-- /wp:woocommerce/checkout-fields-block --><!-- wp:woocommerce/checkout-totals-block --><div class="wp-block-woocommerce-checkout-totals-block"><!-- wp:woocommerce/checkout-order-summary-block --><div class="wp-block-woocommerce-checkout-order-summary-block"><!-- wp:woocommerce/checkout-order-summary-cart-items-block --><div class="wp-block-woocommerce-checkout-order-summary-cart-items-block"></div><!-- /wp:woocommerce/checkout-order-summary-cart-items-block --><!-- wp:woocommerce/checkout-order-summary-subtotal-block --><div class="wp-block-woocommerce-checkout-order-summary-subtotal-block"></div><!-- /wp:woocommerce/checkout-order-summary-subtotal-block --><!-- wp:woocommerce/checkout-order-summary-totals-block --><div class="wp-block-woocommerce-checkout-order-summary-totals-block"></div><!-- /wp:woocommerce/checkout-order-summary-totals-block --></div><!-- /wp:woocommerce/checkout-order-summary-block --></div><!-- /wp:woocommerce/checkout-totals-block --></div><!-- /wp:woocommerce/checkout -->';

        self::ensure_woo_page( 'cart', 'Cart', $cart_block, 'woocommerce_cart_page_id' );
        self::ensure_woo_page( 'checkout', 'Checkout', $checkout_block, 'woocommerce_checkout_page_id' );
    }

    /**
     * Create a page only if it does not exist yet.
     */
    private static function ensure_page( string $slug, string $title, string $content ): void {
        $existing = get_page_by_path( $slug );
        if ( $existing instanceof \WP_Post ) {
            return;
        }

        wp_insert_post( [
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $content,
        ] );
    }

    /**
     * Create or normalize a WooCommerce page and store its option reference.
     *
     * If the page already exists with block-based or matching content, it is
     * left untouched. Content is only written when creating a new page or
     * when the existing page has neither the expected block nor shortcode.
     */
    private static function ensure_woo_page( string $slug, string $title, string $content, string $option_key ): void {
        $existing = get_page_by_path( $slug );
        if ( $existing instanceof \WP_Post ) {
            $page_content = (string) $existing->post_content;
            $has_block     = false !== strpos( $page_content, 'wp:woocommerce/' );
            $has_shortcode = false !== strpos( $page_content, '[woocommerce_' );

            $needs_update = false;
            $update_args  = [
                'ID'        => $existing->ID,
                'post_name' => $slug,
            ];

            // Only replace content if the page has neither a WooCommerce block
            // nor a WooCommerce shortcode (i.e. it was emptied or corrupted).
            if ( ! $has_block && ! $has_shortcode ) {
                $update_args['post_content'] = $content;
                $needs_update = true;
            }

            if ( $existing->post_name !== $slug ) {
                $needs_update = true;
            }

            if ( $needs_update ) {
                wp_update_post( $update_args );
            }

            update_option( $option_key, $existing->ID );
            return;
        }

        $page_id = wp_insert_post( [
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $content,
        ] );

        if ( $page_id && ! is_wp_error( $page_id ) ) {
            update_option( $option_key, $page_id );
        }
    }

    public function deactivate(): void {
        flush_rewrite_rules();
    }

    public function load_textdomain(): void {
        load_plugin_textdomain( 'buggallery', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function enqueue_frontend_assets(): void {
        if ( is_singular( 'bug_photo' ) ) {
            wp_enqueue_style(
                'buggallery-public',
                BUGGALLERY_PLUGIN_URL . 'assets/css/public.css',
                [],
                BUGGALLERY_VERSION
            );
        }

        // Photographer-facing portal assets
        if ( is_page( 'photographer-dashboard' ) || is_page( 'photographer-register' ) || is_page( 'photographer-login' ) || is_page( 'register' ) || is_page( 'login' ) ) {
            wp_enqueue_style(
                'buggallery-dashboard',
                BUGGALLERY_PLUGIN_URL . 'assets/css/dashboard.css',
                [],
                BUGGALLERY_VERSION
            );
            wp_enqueue_script(
                'buggallery-dashboard',
                BUGGALLERY_PLUGIN_URL . 'assets/js/dashboard.js',
                [],
                BUGGALLERY_VERSION,
                true
            );
        }
    }

    public function enqueue_admin_assets( string $hook ): void {
        global $post_type;

        if ( 'bug_photo' !== $post_type ) {
            return;
        }

        wp_enqueue_style(
            'buggallery-admin',
            BUGGALLERY_PLUGIN_URL . 'assets/css/admin.css',
            [],
            BUGGALLERY_VERSION
        );
        wp_enqueue_script(
            'buggallery-admin',
            BUGGALLERY_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery', 'jquery-ui-sortable' ],
            BUGGALLERY_VERSION,
            true
        );
    }
}

// Activation/deactivation hooks must be registered at file load time
register_activation_hook( __FILE__, function() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/roles.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/cpt-bug-photo.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/photographer-dashboard.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/photographer-upload-form.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/user-registration.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/user-login.php';
    BugGallery_Roles::create_roles();
    BugGallery_CPT_Bug_Photo::register();
    BugGallery::ensure_portal_pages();
    BugGallery::ensure_woocommerce_pages();
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
} );

// Initialize
function buggallery(): BugGallery {
    return BugGallery::instance();
}

add_action( 'plugins_loaded', 'buggallery' );
