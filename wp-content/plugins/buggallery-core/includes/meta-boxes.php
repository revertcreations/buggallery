<?php
/**
 * Custom Meta Boxes for Bug Photos
 *
 * Handles all custom fields for bug_photo posts:
 * - Photographer website URL
 * - Print pricing (wall price, mail price)
 * - Related links (unlimited, ordered, drag-to-reorder)
 *
 * No ACF dependency — built with native WordPress meta box API
 * and custom JavaScript for the sortable repeater.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BugGallery_Meta_Boxes {

    const NONCE_ACTION = 'buggallery_save_meta';
    const NONCE_NAME   = 'buggallery_meta_nonce';

    public static function init(): void {
        add_action( 'add_meta_boxes', [ __CLASS__, 'register_meta_boxes' ] );
        add_action( 'save_post_bug_photo', [ __CLASS__, 'save_meta' ], 10, 2 );
        add_action( 'rest_api_init', [ __CLASS__, 'register_rest_fields' ] );
    }

    /**
     * Register all meta boxes for the bug_photo post type
     */
    public static function register_meta_boxes(): void {
        add_meta_box(
            'buggallery_pricing',
            __( 'Print Pricing', 'buggallery' ),
            [ __CLASS__, 'render_pricing_meta_box' ],
            'bug_photo',
            'side',
            'high'
        );

        add_meta_box(
            'buggallery_related_links',
            __( 'Related Links', 'buggallery' ),
            [ __CLASS__, 'render_related_links_meta_box' ],
            'bug_photo',
            'normal',
            'high'
        );
    }

    /**
     * Render pricing meta box
     */
    public static function render_pricing_meta_box( \WP_Post $post ): void {
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

        $wall_price = get_post_meta( $post->ID, '_buggallery_wall_price', true );
        $mail_price = get_post_meta( $post->ID, '_buggallery_mail_price', true );

        ?>
        <p>
            <label for="buggallery_wall_price">
                <strong><?php esc_html_e( '"Take from Wall" Price ($)', 'buggallery' ); ?></strong>
            </label><br>
            <input
                type="number"
                id="buggallery_wall_price"
                name="buggallery_wall_price"
                value="<?php echo esc_attr( $wall_price ); ?>"
                step="0.01"
                min="0"
                style="width: 100%;"
                placeholder="e.g. 150.00"
            >
        </p>
        <p>
            <label for="buggallery_mail_price">
                <strong><?php esc_html_e( '"Mail Me a Print" Price ($)', 'buggallery' ); ?></strong>
            </label><br>
            <input
                type="number"
                id="buggallery_mail_price"
                name="buggallery_mail_price"
                value="<?php echo esc_attr( $mail_price ); ?>"
                step="0.01"
                min="0"
                style="width: 100%;"
                placeholder="e.g. 75.00"
            >
            <br>
            <small><?php esc_html_e( 'Includes shipping. Printful fulfillment cost will be deducted.', 'buggallery' ); ?></small>
        </p>
        <?php
    }

    /**
     * Render related links meta box with sortable repeater
     */
    public static function render_related_links_meta_box( \WP_Post $post ): void {
        $links = get_post_meta( $post->ID, '_buggallery_related_links', true );
        if ( ! is_array( $links ) ) {
            $links = [];
        }

        ?>
        <p class="description">
            <?php esc_html_e( 'Add related links to other bugs on this site, YouTube videos, Wikipedia articles, or any URL. Drag to reorder. Customers will see these links in this exact order.', 'buggallery' ); ?>
        </p>

        <div id="buggallery-related-links-container">
            <?php if ( ! empty( $links ) ) : ?>
                <?php foreach ( $links as $index => $link ) : ?>
                    <div class="buggallery-link-row" data-index="<?php echo esc_attr( $index ); ?>">
                        <span class="buggallery-drag-handle dashicons dashicons-menu" title="<?php esc_attr_e( 'Drag to reorder', 'buggallery' ); ?>"></span>
                        <input
                            type="text"
                            name="buggallery_links[<?php echo esc_attr( $index ); ?>][title]"
                            value="<?php echo esc_attr( $link['title'] ?? '' ); ?>"
                            placeholder="<?php esc_attr_e( 'Link title', 'buggallery' ); ?>"
                            class="buggallery-link-title"
                        >
                        <input
                            type="url"
                            name="buggallery_links[<?php echo esc_attr( $index ); ?>][url]"
                            value="<?php echo esc_attr( $link['url'] ?? '' ); ?>"
                            placeholder="<?php esc_attr_e( 'https://...', 'buggallery' ); ?>"
                            class="buggallery-link-url"
                        >
                        <select name="buggallery_links[<?php echo esc_attr( $index ); ?>][type]" class="buggallery-link-type">
                            <option value="external" <?php selected( $link['type'] ?? 'external', 'external' ); ?>>
                                <?php esc_html_e( 'External', 'buggallery' ); ?>
                            </option>
                            <option value="internal" <?php selected( $link['type'] ?? 'external', 'internal' ); ?>>
                                <?php esc_html_e( 'Internal Bug', 'buggallery' ); ?>
                            </option>
                        </select>
                        <button type="button" class="button buggallery-remove-link" title="<?php esc_attr_e( 'Remove', 'buggallery' ); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <p>
            <button type="button" class="button button-secondary" id="buggallery-add-link">
                <span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Add Related Link', 'buggallery' ); ?>
            </button>
        </p>

        <!-- Template for new rows (used by JS) -->
        <script type="text/html" id="buggallery-link-row-template">
            <div class="buggallery-link-row" data-index="{{INDEX}}">
                <span class="buggallery-drag-handle dashicons dashicons-menu" title="<?php esc_attr_e( 'Drag to reorder', 'buggallery' ); ?>"></span>
                <input
                    type="text"
                    name="buggallery_links[{{INDEX}}][title]"
                    value=""
                    placeholder="<?php esc_attr_e( 'Link title', 'buggallery' ); ?>"
                    class="buggallery-link-title"
                >
                <input
                    type="url"
                    name="buggallery_links[{{INDEX}}][url]"
                    value=""
                    placeholder="<?php esc_attr_e( 'https://...', 'buggallery' ); ?>"
                    class="buggallery-link-url"
                >
                <select name="buggallery_links[{{INDEX}}][type]" class="buggallery-link-type">
                    <option value="external"><?php esc_html_e( 'External', 'buggallery' ); ?></option>
                    <option value="internal"><?php esc_html_e( 'Internal Bug', 'buggallery' ); ?></option>
                </select>
                <button type="button" class="button buggallery-remove-link" title="<?php esc_attr_e( 'Remove', 'buggallery' ); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </script>
        <?php
    }

    /**
     * Save meta box data
     */
    public static function save_meta( int $post_id, \WP_Post $post ): void {
        // Verify nonce
        if ( ! isset( $_POST[ self::NONCE_NAME ] ) ||
             ! wp_verify_nonce( $_POST[ self::NONCE_NAME ], self::NONCE_ACTION ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save pricing
        if ( isset( $_POST['buggallery_wall_price'] ) ) {
            update_post_meta( $post_id, '_buggallery_wall_price',
                sanitize_text_field( $_POST['buggallery_wall_price'] )
            );
        }

        if ( isset( $_POST['buggallery_mail_price'] ) ) {
            update_post_meta( $post_id, '_buggallery_mail_price',
                sanitize_text_field( $_POST['buggallery_mail_price'] )
            );
        }

        // Save related links
        $links = [];
        if ( isset( $_POST['buggallery_links'] ) && is_array( $_POST['buggallery_links'] ) ) {
            foreach ( $_POST['buggallery_links'] as $link ) {
                if ( empty( $link['url'] ) ) {
                    continue;
                }
                $links[] = [
                    'title' => sanitize_text_field( $link['title'] ?? '' ),
                    'url'   => esc_url_raw( $link['url'] ),
                    'type'  => in_array( $link['type'] ?? '', [ 'internal', 'external' ], true )
                                ? $link['type']
                                : 'external',
                ];
            }
        }
        update_post_meta( $post_id, '_buggallery_related_links', $links );
    }

    /**
     * Register REST API fields for headless/JS access
     */
    public static function register_rest_fields(): void {
        register_rest_field( 'bug_photo', 'buggallery_meta', [
            'get_callback' => function ( array $post ) {
                return [
                    'wall_price'    => get_post_meta( $post['id'], '_buggallery_wall_price', true ),
                    'mail_price'    => get_post_meta( $post['id'], '_buggallery_mail_price', true ),
                    'related_links' => get_post_meta( $post['id'], '_buggallery_related_links', true ) ?: [],
                ];
            },
            'schema' => [
                'description' => 'BugGallery custom fields',
                'type'        => 'object',
            ],
        ] );
    }
}

BugGallery_Meta_Boxes::init();
