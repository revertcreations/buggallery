<?php
/**
 * Notifications
 *
 * Handles email notifications for different purchase types:
 * - "Take from Wall" → Email site owner immediately
 * - "Mail Me a Print" → Order confirmation to customer (WooCommerce default)
 *
 * The owner notification is critical: it tells them which print was taken
 * so they can reprint and rehang it in the gallery.
 *
 * PoC SAFETY: When safety mode is enabled, emails are logged to WooCommerce
 * order notes instead of being sent via wp_mail(). This prevents any
 * accidental email delivery during testing.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BugGallery_Notifications {

    /**
     * PoC SAFETY: When true, emails are logged to order notes instead of sent.
     * Set to false only when going live in production.
     */
    const DEFAULT_POC_MODE = true;

    public static function init(): void {
        add_action( 'woocommerce_order_status_processing', [ __CLASS__, 'handle_order_notification' ] );
        add_action( 'woocommerce_order_status_completed', [ __CLASS__, 'handle_order_notification' ] );
    }

    /**
     * Determine whether notifications are running in PoC safety mode.
     *
     * You can override this with:
     * define( 'BUGGALLERY_NOTIFICATIONS_POC_MODE', false );
     * or the filter: buggallery_notifications_poc_mode
     */
    public static function is_poc_mode(): bool {
        if ( defined( 'BUGGALLERY_NOTIFICATIONS_POC_MODE' ) ) {
            return (bool) BUGGALLERY_NOTIFICATIONS_POC_MODE;
        }

        return (bool) apply_filters( 'buggallery_notifications_poc_mode', self::DEFAULT_POC_MODE );
    }

    /**
     * Check order type and send appropriate notifications
     */
    public static function handle_order_notification( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Don't send duplicate notifications
        if ( $order->get_meta( '_buggallery_notification_sent' ) ) {
            return;
        }

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $purchase_type = $product->get_meta( '_buggallery_purchase_type' );

            if ( 'wall' === $purchase_type ) {
                self::notify_wall_sale( $order, $item, $product );
            }
            // "mail" type notifications are handled by WooCommerce + Printful
        }

        $order->update_meta_data( '_buggallery_notification_sent', true );
        $order->save();
    }

    /**
     * Send "Take from Wall" notification to gallery owner
     */
    private static function notify_wall_sale( \WC_Order $order, \WC_Order_Item $item, \WC_Product $product ): void {
        // Get the bug photo details
        $parent_product = wc_get_product( $product->get_parent_id() );
        $bug_photo_id   = $parent_product ? $parent_product->get_meta( '_buggallery_bug_photo_id' ) : null;
        $bug_post       = $bug_photo_id ? get_post( $bug_photo_id ) : null;

        $owner_email = get_option( 'admin_email' );
        $blog_name   = get_bloginfo( 'name' );

        // Build email content
        $bug_name    = $bug_post ? $bug_post->post_title : __( 'Unknown Bug', 'buggallery' );
        $bug_url     = $bug_post ? get_permalink( $bug_post ) : '#';
        $photo_url   = $bug_post ? get_the_post_thumbnail_url( $bug_post->ID, 'medium' ) : '';

        $photographer    = $bug_post ? get_the_author_meta( 'display_name', $bug_post->post_author ) : __( 'Unknown', 'buggallery' );
        $customer_name   = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $sale_amount     = $order->get_formatted_order_total();
        $sale_time       = $order->get_date_created()->date_i18n( 'F j, Y g:i A' );

        $subject = sprintf(
            /* translators: 1: bug name, 2: site name */
            __( '[%2$s] WALL SALE: "%1$s" has been taken!', 'buggallery' ),
            $bug_name,
            $blog_name
        );

        $message = sprintf(
            __(
                'A print has been taken from the gallery wall!

BUG: %1$s
PHOTOGRAPHER: %2$s
CUSTOMER: %3$s
AMOUNT: %4$s
TIME: %5$s

Bug Page: %6$s
Order: %7$s

ACTION REQUIRED: Reprint and rehang this photo in the gallery.

---
This is an automated notification from %8$s.',
                'buggallery'
            ),
            $bug_name,
            $photographer,
            $customer_name,
            $sale_amount,
            $sale_time,
            $bug_url,
            $order->get_edit_order_url(),
            $blog_name
        );

        if ( self::is_poc_mode() ) {
            // PoC SAFETY: Log the email content to the order note instead of sending
            $order->add_order_note(
                sprintf(
                    __( '[PoC MODE] Wall sale email NOT sent. Would have emailed %s.%sSubject: %s%sBody: %s', 'buggallery' ),
                    $owner_email,
                    "\n",
                    $subject,
                    "\n\n",
                    $message
                )
            );

            // Also log to WP debug log for visibility
            if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
                error_log( "[BugGallery PoC] Wall sale notification suppressed. Would send to: $owner_email | Bug: $bug_name" );
            }
        } else {
            // PRODUCTION: Send real email
            $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
            wp_mail( $owner_email, $subject, $message, $headers );

            $order->add_order_note(
                sprintf(
                    __( 'BugGallery: Wall sale notification sent to %s for "%s"', 'buggallery' ),
                    $owner_email,
                    $bug_name
                )
            );
        }
    }
}

BugGallery_Notifications::init();
