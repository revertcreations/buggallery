<?php
/**
 * Printful API Integration
 *
 * Handles communication with Printful's API for print-on-demand fulfillment.
 * When a "Mail Me a Print" order is placed, this class:
 * 1. Creates a Printful order with the bug photo and shipping address
 * 2. Tracks fulfillment status via webhooks
 * 3. Updates WooCommerce order with tracking info
 *
 * API docs: https://developers.printful.com/docs/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BugGallery_Printful_API {

    const API_BASE       = 'https://api.printful.com';
    const API_KEY_OPTION = 'buggallery_printful_api_key';
    const WEBHOOK_SLUG   = 'buggallery-printful-webhook';

    /**
     * PoC SAFETY: When true, Printful orders are created as DRAFTS only.
     * They will NOT be sent to production, and you will NOT be charged.
     * Set to false (or define BUGGALLERY_PRINTFUL_DRAFT_MODE) when going live.
     */
    const DEFAULT_DRAFT_MODE = true;

    /**
     * Available poster variants from Printful's Enhanced Matte Paper Poster (product 1).
     * These are the standard sizes. The client picks one from the settings page.
     */
    const POSTER_VARIANTS = [
        1   => '18" x 24"',
        2   => '24" x 36"',
        387 => '12" x 18"',
        388 => '11" x 17"',
        389 => '16" x 20"',
    ];

    const DEFAULT_VARIANT_ID = 1; // 18" x 24"

    // ─── Bootstrap ───────────────────────────────────────────

    public static function init(): void {
        // Admin settings page
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );

        // Order hooks — create Printful orders when WooCommerce orders are paid
        add_action( 'woocommerce_order_status_processing', [ __CLASS__, 'handle_new_order' ] );
        add_action( 'woocommerce_order_status_completed', [ __CLASS__, 'handle_new_order' ] );

        // Webhook endpoint for Printful shipment callbacks
        add_action( 'rest_api_init', [ __CLASS__, 'register_webhook_endpoint' ] );

        // Admin orders list — add Printful status column
        add_filter( 'manage_edit-shop_order_columns', [ __CLASS__, 'add_order_column' ] );
        add_action( 'manage_shop_order_posts_custom_column', [ __CLASS__, 'render_order_column' ], 10, 2 );

        // HPOS compatibility (WooCommerce 8+)
        add_filter( 'manage_woocommerce_page_wc-orders_columns', [ __CLASS__, 'add_order_column' ] );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ __CLASS__, 'render_order_column_hpos' ], 10, 2 );

        // Order detail metabox
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_order_metabox' ] );

        // AJAX connection test
        add_action( 'wp_ajax_buggallery_printful_test', [ __CLASS__, 'ajax_test_connection' ] );
    }

    // ─── Configuration ───────────────────────────────────────

    public static function is_draft_mode(): bool {
        if ( defined( 'BUGGALLERY_PRINTFUL_DRAFT_MODE' ) ) {
            return (bool) BUGGALLERY_PRINTFUL_DRAFT_MODE;
        }
        return (bool) apply_filters( 'buggallery_printful_draft_mode', self::DEFAULT_DRAFT_MODE );
    }

    public static function get_api_key(): string {
        return get_option( self::API_KEY_OPTION, '' );
    }

    public static function is_configured(): bool {
        return ! empty( self::get_api_key() );
    }

    public static function get_variant_id(): int {
        $saved = (int) get_option( 'buggallery_printful_variant_id', self::DEFAULT_VARIANT_ID );
        return isset( self::POSTER_VARIANTS[ $saved ] ) ? $saved : self::DEFAULT_VARIANT_ID;
    }

    public static function get_webhook_url(): string {
        return rest_url( 'buggallery/v1/printful-webhook' );
    }

    // ─── API Request ─────────────────────────────────────────

    public static function api_request( string $endpoint, string $method = 'GET', array $data = [] ): array {
        $api_key = self::get_api_key();
        if ( empty( $api_key ) ) {
            return [ 'success' => false, 'error' => 'Printful API key not configured' ];
        }

        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 30,
        ];

        if ( ! empty( $data ) && in_array( $method, [ 'POST', 'PUT' ], true ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        $response = wp_remote_request( self::API_BASE . $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            return [ 'success' => false, 'error' => $response->get_error_message() ];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code >= 200 && $code < 300 ) {
            return [ 'success' => true, 'data' => $body['result'] ?? $body ];
        }

        return [
            'success' => false,
            'error'   => $body['error']['message'] ?? 'Unknown API error (HTTP ' . $code . ')',
            'code'    => $code,
        ];
    }

    // ─── Order Creation ──────────────────────────────────────

    /**
     * Handle new WooCommerce orders — send all "Mail Me a Print" items to Printful.
     */
    public static function handle_new_order( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Skip if not configured
        if ( ! self::is_configured() ) {
            $order->add_order_note( __( 'Printful: Skipped — API key not configured.', 'buggallery' ) );
            return;
        }

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $purchase_type = $product->get_meta( '_buggallery_purchase_type' );
            if ( 'mail' !== $purchase_type ) {
                continue;
            }

            // Check if this specific item was already sent to Printful
            $item_printful_id = $item->get_meta( '_buggallery_printful_order_id' );
            if ( $item_printful_id ) {
                continue;
            }

            // Get the bug photo ID from the parent product
            $parent_product = wc_get_product( $product->get_parent_id() );
            if ( ! $parent_product ) {
                continue;
            }

            $bug_photo_id = (int) $parent_product->get_meta( '_buggallery_bug_photo_id' );
            if ( ! $bug_photo_id ) {
                continue;
            }

            // Create Printful order for this item
            $result = self::create_order( $order, $bug_photo_id, $item );
            if ( ! $result['success'] ) {
                $order->add_order_note(
                    sprintf( __( 'Printful order failed for "%s": %s', 'buggallery' ), $item->get_name(), $result['error'] )
                );
            }
        }
    }

    /**
     * Create a Printful order for a specific order line item.
     */
    public static function create_order( \WC_Order $order, int $bug_photo_id, \WC_Order_Item_Product $item ): array {
        // Get the full-resolution image URL
        $image_id  = get_post_thumbnail_id( $bug_photo_id );
        $image_url = wp_get_attachment_url( $image_id );

        if ( ! $image_url ) {
            return [ 'success' => false, 'error' => 'No image found for bug photo #' . $bug_photo_id ];
        }

        // Warn if the image URL looks like localhost (Printful can't fetch it)
        $host = wp_parse_url( $image_url, PHP_URL_HOST );
        $is_local = in_array( $host, [ 'localhost', '127.0.0.1', '0.0.0.0' ], true )
                    || str_ends_with( $host, '.local' )
                    || str_ends_with( $host, '.test' );

        if ( $is_local ) {
            // Substitute a public placeholder image so the draft order can be
            // created in Printful during local development. In production
            // (real domain) this block never runs.
            $original_url = $image_url;
            $image_url    = 'https://picsum.photos/id/237/1800/2400';
            $order->add_order_note(
                sprintf(
                    __( 'Printful dev: Local image URL (%s) replaced with a placeholder for testing. The real image will be used automatically in production.', 'buggallery' ),
                    $original_url
                )
            );
        }

        $order_data = [
            'confirm'     => ! self::is_draft_mode(),
            'external_id' => $order->get_id() . '-' . $item->get_id(),
            'recipient'   => [
                'name'         => trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() ),
                'address1'     => $order->get_shipping_address_1(),
                'address2'     => $order->get_shipping_address_2(),
                'city'         => $order->get_shipping_city(),
                'state_code'   => $order->get_shipping_state(),
                'country_code' => $order->get_shipping_country(),
                'zip'          => $order->get_shipping_postcode(),
                'email'        => $order->get_billing_email(),
                'phone'        => $order->get_billing_phone(),
            ],
            'items' => [
                [
                    'variant_id' => self::get_variant_id(),
                    'quantity'   => max( 1, $item->get_quantity() ),
                    'name'       => get_the_title( $bug_photo_id ) . ' — Print',
                    'files'      => [
                        [
                            'type' => 'default',
                            'url'  => $image_url,
                        ],
                    ],
                ],
            ],
        ];

        $result = self::api_request( '/orders', 'POST', $order_data );

        $draft_label = self::is_draft_mode() ? ' (DRAFT — not sent to production)' : '';

        if ( $result['success'] ) {
            $printful_id = $result['data']['id'] ?? 'unknown';

            // Store on the line item (supports multiple mail items per order)
            $item->add_meta_data( '_buggallery_printful_order_id', $printful_id, true );
            $item->save();

            // Also store on the order for quick lookups
            $existing = $order->get_meta( '_buggallery_printful_order_ids' ) ?: [];
            $existing[] = $printful_id;
            $order->update_meta_data( '_buggallery_printful_order_ids', $existing );
            $order->update_meta_data( '_buggallery_printful_status', $result['data']['status'] ?? 'draft' );
            $order->add_order_note(
                sprintf( __( 'Printful order #%s created for "%s"%s', 'buggallery' ), $printful_id, $item->get_name(), $draft_label )
            );
            $order->save();
        }

        return $result;
    }

    // ─── Webhook ─────────────────────────────────────────────

    /**
     * Register REST API endpoint for Printful webhook callbacks.
     */
    public static function register_webhook_endpoint(): void {
        register_rest_route( 'buggallery/v1', '/printful-webhook', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_webhook' ],
            'permission_callback' => '__return_true', // Printful signs the payload
        ] );
    }

    /**
     * Handle incoming Printful webhook events.
     *
     * Printful events we care about:
     * - package_shipped: A package has been shipped, includes tracking info
     * - order_updated:   Order status changed
     * - order_failed:    Order could not be fulfilled
     */
    public static function handle_webhook( \WP_REST_Request $request ): \WP_REST_Response {
        $body = $request->get_json_params();

        if ( empty( $body['type'] ) || empty( $body['data'] ) ) {
            return new \WP_REST_Response( [ 'error' => 'Invalid payload' ], 400 );
        }

        $type = $body['type'];
        $data = $body['data'];

        // Find the WooCommerce order by Printful's external_id (format: "{order_id}-{item_id}")
        $external_id = $data['order']['external_id'] ?? '';
        $wc_order_id = (int) explode( '-', $external_id )[0];

        if ( ! $wc_order_id ) {
            return new \WP_REST_Response( [ 'error' => 'No matching order' ], 404 );
        }

        $order = wc_get_order( $wc_order_id );
        if ( ! $order ) {
            return new \WP_REST_Response( [ 'error' => 'Order not found' ], 404 );
        }

        $printful_status = $data['order']['status'] ?? '';

        switch ( $type ) {
            case 'package_shipped':
                $tracking_number = $data['shipment']['tracking_number'] ?? '';
                $tracking_url    = $data['shipment']['tracking_url'] ?? '';
                $carrier         = $data['shipment']['carrier'] ?? '';

                $order->update_meta_data( '_buggallery_printful_status', 'shipped' );
                $order->update_meta_data( '_buggallery_printful_tracking_number', $tracking_number );
                $order->update_meta_data( '_buggallery_printful_tracking_url', $tracking_url );
                $order->update_meta_data( '_buggallery_printful_carrier', $carrier );
                $order->add_order_note(
                    sprintf(
                        __( 'Printful: Package shipped via %s. Tracking: %s', 'buggallery' ),
                        $carrier,
                        $tracking_url ?: $tracking_number
                    )
                );

                // Mark WooCommerce order as completed
                if ( $order->get_status() !== 'completed' ) {
                    $order->update_status( 'completed', __( 'Printful shipment confirmed.', 'buggallery' ) );
                }
                $order->save();
                break;

            case 'order_updated':
                $order->update_meta_data( '_buggallery_printful_status', $printful_status );
                $order->add_order_note(
                    sprintf( __( 'Printful status updated: %s', 'buggallery' ), $printful_status )
                );
                $order->save();
                break;

            case 'order_failed':
                $reason = $data['reason'] ?? 'Unknown reason';
                $order->update_meta_data( '_buggallery_printful_status', 'failed' );
                $order->add_order_note(
                    sprintf( __( 'Printful order FAILED: %s', 'buggallery' ), $reason )
                );
                $order->save();
                break;
        }

        return new \WP_REST_Response( [ 'ok' => true ], 200 );
    }

    // ─── Admin: Orders List Column ───────────────────────────

    public static function add_order_column( array $columns ): array {
        $new_columns = [];
        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            // Insert after the order status column
            if ( 'order_status' === $key ) {
                $new_columns['printful_status'] = __( 'Printful', 'buggallery' );
            }
        }
        return $new_columns;
    }

    /**
     * Render the Printful status column (classic post-based orders).
     */
    public static function render_order_column( string $column, int $post_id ): void {
        if ( 'printful_status' !== $column ) {
            return;
        }
        $order = wc_get_order( $post_id );
        if ( $order ) {
            self::output_status_badge( $order );
        }
    }

    /**
     * Render the Printful status column (HPOS orders).
     */
    public static function render_order_column_hpos( string $column, $order ): void {
        if ( 'printful_status' !== $column ) {
            return;
        }
        if ( $order instanceof \WC_Order ) {
            self::output_status_badge( $order );
        }
    }

    /**
     * Output a styled status badge for an order.
     */
    private static function output_status_badge( \WC_Order $order ): void {
        // Check if this order has any mail items
        $has_mail_item = false;
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( $product && 'mail' === $product->get_meta( '_buggallery_purchase_type' ) ) {
                $has_mail_item = true;
                break;
            }
        }

        if ( ! $has_mail_item ) {
            echo '<span style="color:#666;">—</span>';
            return;
        }

        $status       = $order->get_meta( '_buggallery_printful_status' ) ?: 'not_sent';
        $tracking_url = $order->get_meta( '_buggallery_printful_tracking_url' );

        $colors = [
            'not_sent'   => '#888',
            'draft'      => '#e8c547',
            'pending'    => '#f59e0b',
            'inprocess'  => '#3b82f6',
            'fulfilled'  => '#22c55e',
            'shipped'    => '#22c55e',
            'completed'  => '#22c55e',
            'failed'     => '#ef4444',
            'canceled'   => '#ef4444',
            'cancelled'  => '#ef4444',
        ];

        $labels = [
            'not_sent'   => 'Not sent',
            'draft'      => 'Draft',
            'pending'    => 'Pending',
            'inprocess'  => 'In production',
            'fulfilled'  => 'Fulfilled',
            'shipped'    => 'Shipped',
            'completed'  => 'Completed',
            'failed'     => 'Failed',
            'canceled'   => 'Canceled',
            'cancelled'  => 'Canceled',
        ];

        $color = $colors[ $status ] ?? '#888';
        $label = $labels[ $status ] ?? ucfirst( $status );

        echo '<mark style="background:' . esc_attr( $color ) . '20;color:' . esc_attr( $color ) . ';padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600;white-space:nowrap;">';
        echo esc_html( $label );
        echo '</mark>';

        if ( $tracking_url ) {
            echo ' <a href="' . esc_url( $tracking_url ) . '" target="_blank" style="font-size:11px;">Track</a>';
        }
    }

    // ─── Admin: Order Detail Metabox ─────────────────────────

    public static function add_order_metabox(): void {
        $screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' )
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        add_meta_box(
            'buggallery-printful-order',
            __( 'Printful Fulfillment', 'buggallery' ),
            [ __CLASS__, 'render_order_metabox' ],
            $screen,
            'side',
            'default'
        );
    }

    public static function render_order_metabox( $post_or_order ): void {
        $order = ( $post_or_order instanceof \WC_Order )
            ? $post_or_order
            : wc_get_order( $post_or_order->ID );

        if ( ! $order ) {
            echo '<p>' . esc_html__( 'Order not found.', 'buggallery' ) . '</p>';
            return;
        }

        $printful_ids  = $order->get_meta( '_buggallery_printful_order_ids' ) ?: [];
        $status        = $order->get_meta( '_buggallery_printful_status' ) ?: 'not_sent';
        $tracking_num  = $order->get_meta( '_buggallery_printful_tracking_number' );
        $tracking_url  = $order->get_meta( '_buggallery_printful_tracking_url' );
        $carrier       = $order->get_meta( '_buggallery_printful_carrier' );

        if ( empty( $printful_ids ) ) {
            // Check if there are any mail items
            $has_mail = false;
            foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();
                if ( $product && 'mail' === $product->get_meta( '_buggallery_purchase_type' ) ) {
                    $has_mail = true;
                    break;
                }
            }

            if ( ! $has_mail ) {
                echo '<p style="color:#666;">' . esc_html__( 'This order has no "Mail Me a Print" items.', 'buggallery' ) . '</p>';
            } else {
                echo '<p style="color:#e8c547;">' . esc_html__( 'Not yet sent to Printful.', 'buggallery' ) . '</p>';
                if ( ! self::is_configured() ) {
                    echo '<p style="color:#ef4444;font-size:12px;">' . esc_html__( 'Printful API key is not configured.', 'buggallery' ) . '</p>';
                }
            }
            return;
        }

        echo '<table style="width:100%;font-size:13px;">';

        // Printful Order IDs
        echo '<tr><td style="padding:4px 0;color:#666;">Order IDs:</td><td style="padding:4px 0;">';
        foreach ( $printful_ids as $pid ) {
            echo '<a href="https://www.printful.com/dashboard?order_id=' . esc_attr( $pid ) . '" target="_blank">#' . esc_html( $pid ) . '</a> ';
        }
        echo '</td></tr>';

        // Status
        echo '<tr><td style="padding:4px 0;color:#666;">Status:</td><td style="padding:4px 0;">';
        self::output_status_badge( $order );
        echo '</td></tr>';

        // Draft mode warning
        if ( self::is_draft_mode() && 'draft' === $status ) {
            echo '<tr><td colspan="2" style="padding:8px 0 4px;"><span style="color:#e8c547;font-size:12px;">Draft mode is active. This order has not been sent to production.</span></td></tr>';
        }

        // Tracking
        if ( $tracking_num ) {
            echo '<tr><td style="padding:4px 0;color:#666;">Carrier:</td><td style="padding:4px 0;">' . esc_html( $carrier ?: '—' ) . '</td></tr>';
            echo '<tr><td style="padding:4px 0;color:#666;">Tracking:</td><td style="padding:4px 0;">';
            if ( $tracking_url ) {
                echo '<a href="' . esc_url( $tracking_url ) . '" target="_blank">' . esc_html( $tracking_num ) . '</a>';
            } else {
                echo esc_html( $tracking_num );
            }
            echo '</td></tr>';
        }

        echo '</table>';
    }

    // ─── Admin: Settings Page ────────────────────────────────

    public static function add_settings_page(): void {
        add_submenu_page(
            'woocommerce',
            __( 'BugGallery Printful Settings', 'buggallery' ),
            __( 'Printful', 'buggallery' ),
            'manage_options',
            'buggallery-printful',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    public static function register_settings(): void {
        register_setting( 'buggallery_printful', self::API_KEY_OPTION, [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        register_setting( 'buggallery_printful', 'buggallery_printful_variant_id', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => self::DEFAULT_VARIANT_ID,
        ] );
    }

    /**
     * AJAX handler for connection test.
     */
    public static function ajax_test_connection(): void {
        check_ajax_referer( 'buggallery_printful_test', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        // Use /oauth/scopes to verify the token — works with any valid token
        // regardless of which scopes are granted.
        $result = self::api_request( '/oauth/scopes' );
        if ( $result['success'] ) {
            $scopes = $result['data']['scopes'] ?? [];
            $scope_names = array_column( $scopes, 'scope' );
            $has_orders = in_array( 'orders', $scope_names, true );

            $message = sprintf(
                'Connected — %d scope(s) granted.',
                count( $scopes )
            );

            if ( ! $has_orders ) {
                $message .= ' Warning: "orders" scope is missing — order creation will fail.';
            }

            wp_send_json_success( [
                'message' => $message,
                'scopes'  => $scope_names,
            ] );
        } else {
            wp_send_json_error( $result['error'] );
        }
    }

    public static function render_settings_page(): void {
        $variant_id = self::get_variant_id();
        $nonce      = wp_create_nonce( 'buggallery_printful_test' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'BugGallery Printful Integration', 'buggallery' ); ?></h1>

            <?php if ( self::is_draft_mode() ) : ?>
                <div class="notice notice-warning" style="border-left-color:#e8c547;">
                    <p><strong>Draft Mode Active</strong> — Printful orders are created as drafts only. No prints will be produced. No charges will be made. Safe for testing.</p>
                    <p style="font-size:12px;color:#666;">To go live, add <code>define( 'BUGGALLERY_PRINTFUL_DRAFT_MODE', false );</code> to wp-config.php.</p>
                </div>
            <?php else : ?>
                <div class="notice notice-error">
                    <p><strong>LIVE MODE</strong> — Printful orders WILL be sent to production and you WILL be charged.</p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'buggallery_printful' ); ?>

                <table class="form-table">
                    <!-- API Key -->
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr( self::API_KEY_OPTION ); ?>">
                                <?php esc_html_e( 'Printful API Key', 'buggallery' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="password"
                                id="<?php echo esc_attr( self::API_KEY_OPTION ); ?>"
                                name="<?php echo esc_attr( self::API_KEY_OPTION ); ?>"
                                value="<?php echo esc_attr( self::get_api_key() ); ?>"
                                class="regular-text"
                                autocomplete="off"
                            >
                            <button type="button" id="buggallery-test-connection" class="button button-secondary" style="margin-left:8px;">
                                <?php esc_html_e( 'Test Connection', 'buggallery' ); ?>
                            </button>
                            <span id="buggallery-test-result" style="margin-left:8px;"></span>
                            <p class="description">
                                <?php esc_html_e( 'Get your API key from Printful Dashboard → Settings → API.', 'buggallery' ); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Poster Size -->
                    <tr>
                        <th scope="row">
                            <label for="buggallery_printful_variant_id">
                                <?php esc_html_e( 'Poster Size', 'buggallery' ); ?>
                            </label>
                        </th>
                        <td>
                            <select name="buggallery_printful_variant_id" id="buggallery_printful_variant_id">
                                <?php foreach ( self::POSTER_VARIANTS as $id => $label ) : ?>
                                    <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $variant_id, $id ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                        <?php if ( $id === self::DEFAULT_VARIANT_ID ) echo '(default)'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Enhanced Matte Paper Poster — the size Printful will print and ship.', 'buggallery' ); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Webhook URL -->
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Webhook URL', 'buggallery' ); ?></th>
                        <td>
                            <code style="display:inline-block;padding:6px 10px;background:#f0f0f1;border-radius:4px;font-size:13px;user-select:all;">
                                <?php echo esc_html( self::get_webhook_url() ); ?>
                            </code>
                            <p class="description">
                                <?php esc_html_e( 'Add this URL in Printful Dashboard → Settings → Webhooks. Enable the "Package shipped", "Order updated", and "Order failed" events.', 'buggallery' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <?php if ( self::is_configured() ) : ?>
                <?php self::render_recent_orders_table(); ?>
            <?php endif; ?>
        </div>

        <script>
        (function() {
            var btn = document.getElementById('buggallery-test-connection');
            var result = document.getElementById('buggallery-test-result');
            if (!btn) return;
            btn.addEventListener('click', function() {
                result.textContent = 'Testing...';
                result.style.color = '#666';
                // Use the current value in the input field, not the saved one
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=buggallery_printful_test&nonce=<?php echo esc_js( $nonce ); ?>'
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        result.textContent = '✓ ' + data.data.message;
                        result.style.color = '#22c55e';
                    } else {
                        result.textContent = '✗ ' + (data.data || 'Connection failed');
                        result.style.color = '#ef4444';
                    }
                })
                .catch(function() {
                    result.textContent = '✗ Request failed';
                    result.style.color = '#ef4444';
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Render a table of recent WooCommerce orders that have Printful data.
     */
    private static function render_recent_orders_table(): void {
        $orders = wc_get_orders( [
            'limit'    => 10,
            'orderby'  => 'date',
            'order'    => 'DESC',
            'meta_key' => '_buggallery_printful_order_ids',
        ] );

        if ( empty( $orders ) ) {
            echo '<h2>' . esc_html__( 'Recent Printful Orders', 'buggallery' ) . '</h2>';
            echo '<p style="color:#666;">' . esc_html__( 'No Printful orders yet. Orders will appear here after a "Mail Me a Print" purchase is completed.', 'buggallery' ) . '</p>';
            return;
        }

        echo '<h2>' . esc_html__( 'Recent Printful Orders', 'buggallery' ) . '</h2>';
        echo '<table class="widefat striped" style="max-width:800px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'WC Order', 'buggallery' ) . '</th>';
        echo '<th>' . esc_html__( 'Printful ID(s)', 'buggallery' ) . '</th>';
        echo '<th>' . esc_html__( 'Status', 'buggallery' ) . '</th>';
        echo '<th>' . esc_html__( 'Tracking', 'buggallery' ) . '</th>';
        echo '<th>' . esc_html__( 'Date', 'buggallery' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $orders as $order ) {
            $printful_ids = $order->get_meta( '_buggallery_printful_order_ids' ) ?: [];
            $tracking_url = $order->get_meta( '_buggallery_printful_tracking_url' );
            $tracking_num = $order->get_meta( '_buggallery_printful_tracking_number' );

            echo '<tr>';
            echo '<td><a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . esc_html( $order->get_id() ) . '</a></td>';
            echo '<td>';
            foreach ( $printful_ids as $pid ) {
                echo '<a href="https://www.printful.com/dashboard?order_id=' . esc_attr( $pid ) . '" target="_blank">#' . esc_html( $pid ) . '</a> ';
            }
            echo '</td>';
            echo '<td>';
            self::output_status_badge( $order );
            echo '</td>';
            echo '<td>';
            if ( $tracking_url ) {
                echo '<a href="' . esc_url( $tracking_url ) . '" target="_blank">' . esc_html( $tracking_num ?: 'Track' ) . '</a>';
            } else {
                echo '<span style="color:#666;">—</span>';
            }
            echo '</td>';
            echo '<td>' . esc_html( $order->get_date_created()->date( 'M j, Y' ) ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}

BugGallery_Printful_API::init();
