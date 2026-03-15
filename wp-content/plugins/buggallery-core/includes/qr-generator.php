<?php
/**
 * QR Code Generator
 *
 * Generates QR codes for each bug_photo post.
 * QR codes link directly to the bug's story page.
 *
 * Uses the Google Charts API for QR generation in this PoC.
 * Production should use a local PHP library (chillerlan/php-qrcode)
 * to avoid external API dependency.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BugGallery_QR_Generator {

    public static function init(): void {
        add_action( 'save_post_bug_photo', [ __CLASS__, 'generate_qr_on_publish' ], 20, 2 );
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_qr_meta_box' ] );
        add_action( 'wp_ajax_buggallery_download_qr', [ __CLASS__, 'ajax_download_qr' ] );
    }

    /**
     * Get the QR code URL for a bug photo
     */
    public static function get_qr_url( int $post_id, int $size = 400 ): string {
        $bug_url    = get_permalink( $post_id );
        $encoded    = urlencode( $bug_url );

        // QuickChart.io QR API (free, no key needed, reliable)
        // For production: replace with local generation via chillerlan/php-qrcode
        return "https://quickchart.io/qr?text={$encoded}&size={$size}&margin=2&ecLevel=H&format=png";
    }

    /**
     * Get a high-resolution QR code URL for print (1000x1000)
     */
    public static function get_print_qr_url( int $post_id ): string {
        return self::get_qr_url( $post_id, 1000 );
    }

    /**
     * Generate and cache QR code locally when a bug photo is published
     */
    public static function generate_qr_on_publish( int $post_id, \WP_Post $post ): void {
        if ( 'publish' !== $post->post_status ) {
            return;
        }

        // Store the bug page URL for reference
        update_post_meta( $post_id, '_buggallery_qr_url', get_permalink( $post_id ) );
    }

    /**
     * Add QR code display meta box in admin
     */
    public static function add_qr_meta_box(): void {
        add_meta_box(
            'buggallery_qr_code',
            __( 'QR Code', 'buggallery' ),
            [ __CLASS__, 'render_qr_meta_box' ],
            'bug_photo',
            'side',
            'default'
        );
    }

    /**
     * Render QR code in admin sidebar
     */
    public static function render_qr_meta_box( \WP_Post $post ): void {
        if ( 'publish' !== $post->post_status ) {
            echo '<p>' . esc_html__( 'Publish this bug photo to generate a QR code.', 'buggallery' ) . '</p>';
            return;
        }

        $qr_url    = self::get_qr_url( $post->ID, 200 );
        $print_url = self::get_print_qr_url( $post->ID );
        $bug_url   = get_permalink( $post->ID );

        ?>
        <div style="text-align: center;">
            <img
                src="<?php echo esc_url( $qr_url ); ?>"
                alt="<?php esc_attr_e( 'QR Code', 'buggallery' ); ?>"
                style="max-width: 200px; height: auto; border: 1px solid #ddd; padding: 8px; background: #fff;"
            >
            <p style="margin-top: 8px;">
                <small><?php echo esc_html( $bug_url ); ?></small>
            </p>
            <p>
                <a
                    href="<?php echo esc_url( $print_url ); ?>"
                    class="button"
                    download="qr-<?php echo esc_attr( $post->post_name ); ?>.png"
                    target="_blank"
                >
                    <?php esc_html_e( 'Download Print-Quality QR', 'buggallery' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * AJAX handler for QR code download
     */
    public static function ajax_download_qr(): void {
        $post_id = absint( $_GET['post_id'] ?? 0 );

        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( 'Unauthorized' );
        }

        $url = self::get_print_qr_url( $post_id );
        wp_redirect( $url );
        exit;
    }
}

BugGallery_QR_Generator::init();
