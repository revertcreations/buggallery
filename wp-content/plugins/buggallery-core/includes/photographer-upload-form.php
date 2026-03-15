<?php
/**
 * Front-End Photographer Upload Form
 *
 * Provides shortcode [buggallery_upload_form] for a self-service upload experience.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BugGallery_Photographer_Upload_Form {

    public static function init(): void {
        add_shortcode( 'buggallery_upload_form', [ __CLASS__, 'render_form' ] );
        add_action( 'init', [ __CLASS__, 'handle_submission' ] );
    }

    public static function render_form( array $atts = [] ): string {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to upload your bug photo.', 'buggallery' ) . '</p>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'photographer', (array) $user->roles, true ) && ! in_array( 'administrator', (array) $user->roles, true ) ) {
            return '<p>' . esc_html__( 'Only photographers can upload bug photos.', 'buggallery' ) . '</p>';
        }

        $notice = '';
        if ( isset( $_GET['buggallery_upload'] ) && 'success' === $_GET['buggallery_upload'] ) {
            $notice = '<p class="buggallery-notice buggallery-notice-success">'
                . esc_html__( 'Bug photo uploaded successfully.', 'buggallery' )
                . '</p>';
        }

        if ( isset( $_GET['buggallery_upload'] ) && 'error' === $_GET['buggallery_upload'] ) {
            $error = isset( $_GET['buggallery_error'] ) ? sanitize_text_field( wp_unslash( $_GET['buggallery_error'] ) ) : __( 'Upload failed.', 'buggallery' );
            $notice = '<p class="buggallery-notice buggallery-notice-error">' . esc_html( $error ) . '</p>';
        }

        ob_start();
        ?>
        <div id="buggallery-upload-form" class="buggallery-upload-wrap">
            <h3><?php esc_html_e( 'Upload a New Bug Photo', 'buggallery' ); ?></h3>
            <?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <form method="post" enctype="multipart/form-data" class="buggallery-upload-form">
                <?php wp_nonce_field( 'buggallery_upload_bug_photo', 'buggallery_upload_nonce' ); ?>
                <input type="hidden" name="buggallery_upload_action" value="1">

                <p>
                    <label for="buggallery_bug_title"><?php esc_html_e( 'Bug Name', 'buggallery' ); ?></label>
                    <input type="text" id="buggallery_bug_title" name="buggallery_bug_title" class="buggallery-input" required>
                </p>

                <p>
                    <label for="buggallery_bug_species"><?php esc_html_e( 'Species', 'buggallery' ); ?></label>
                    <select id="buggallery_bug_species" name="buggallery_bug_species" class="buggallery-input">
                        <option value=""><?php esc_html_e( '— Select species —', 'buggallery' ); ?></option>
                        <?php
                        $species_terms = get_terms( [
                            'taxonomy'   => 'bug_species',
                            'hide_empty' => false,
                            'orderby'    => 'name',
                            'order'      => 'ASC',
                        ] );
                        if ( ! is_wp_error( $species_terms ) ) :
                            foreach ( $species_terms as $term ) :
                        ?>
                            <option value="<?php echo esc_attr( $term->term_id ); ?>">
                                <?php echo esc_html( $term->name ); ?>
                            </option>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </select>
                </p>

                <p>
                    <label for="buggallery_bug_story"><?php esc_html_e( 'Story / Description', 'buggallery' ); ?></label>
                    <textarea id="buggallery_bug_story" name="buggallery_bug_story" rows="7" class="buggallery-input" required></textarea>
                </p>

                <div class="buggallery-price-grid">
                    <p>
                        <label for="buggallery_wall_price"><?php esc_html_e( 'Take from Wall Price (USD)', 'buggallery' ); ?></label>
                        <input type="number" id="buggallery_wall_price" name="buggallery_wall_price" class="buggallery-input" min="0" step="0.01" value="150.00" required>
                    </p>
                    <p>
                        <label for="buggallery_mail_price"><?php esc_html_e( 'Mail Me a Print Price (USD)', 'buggallery' ); ?></label>
                        <input type="number" id="buggallery_mail_price" name="buggallery_mail_price" class="buggallery-input" min="0" step="0.01" value="75.00" required>
                    </p>
                </div>

                <p>
                    <label for="buggallery_featured_image"><?php esc_html_e( 'High-Resolution Bug Photo', 'buggallery' ); ?></label>
                    <input type="file" id="buggallery_featured_image" name="buggallery_featured_image" accept="image/*" required>
                </p>

                <div class="buggallery-related-links-editor">
                    <div class="buggallery-related-links-header">
                        <h4><?php esc_html_e( 'Related Links', 'buggallery' ); ?></h4>
                        <button type="button" id="buggallery-add-related-link" class="buggallery-btn buggallery-btn-secondary">
                            <?php esc_html_e( '+ Add Related Link', 'buggallery' ); ?>
                        </button>
                    </div>
                    <p class="buggallery-help-text"><?php esc_html_e( 'Add as many links as you like. Drag rows to set display order.', 'buggallery' ); ?></p>

                    <div id="buggallery-related-links-list" class="buggallery-related-links-list"></div>

                    <script type="text/html" id="buggallery-related-link-template">
                        <div class="buggallery-related-link-row" data-index="{{INDEX}}" draggable="true">
                            <span class="buggallery-drag-handle" aria-hidden="true">::</span>
                            <input type="text" name="buggallery_links[{{INDEX}}][title]" placeholder="Link title" class="buggallery-input">
                            <input type="url" name="buggallery_links[{{INDEX}}][url]" placeholder="https://example.com" class="buggallery-input">
                            <select name="buggallery_links[{{INDEX}}][type]" class="buggallery-input">
                                <option value="external">External</option>
                                <option value="internal">Internal Bug</option>
                            </select>
                            <button type="button" class="buggallery-remove-related-link buggallery-btn buggallery-btn-secondary"><?php esc_html_e( 'Remove', 'buggallery' ); ?></button>
                        </div>
                    </script>
                </div>

                <button type="submit" class="buggallery-btn buggallery-btn-primary">
                    <?php esc_html_e( 'Publish Bug Photo', 'buggallery' ); ?>
                </button>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    public static function handle_submission(): void {
        if ( ! isset( $_POST['buggallery_upload_action'] ) ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            self::redirect_with_error( __( 'You must be logged in to upload.', 'buggallery' ) );
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'photographer', (array) $user->roles, true ) && ! in_array( 'administrator', (array) $user->roles, true ) ) {
            self::redirect_with_error( __( 'You do not have permission to upload.', 'buggallery' ) );
        }

        if ( ! isset( $_POST['buggallery_upload_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['buggallery_upload_nonce'] ) ), 'buggallery_upload_bug_photo' ) ) {
            self::redirect_with_error( __( 'Security check failed. Please try again.', 'buggallery' ) );
        }

        $title      = isset( $_POST['buggallery_bug_title'] ) ? sanitize_text_field( wp_unslash( $_POST['buggallery_bug_title'] ) ) : '';
        $story      = isset( $_POST['buggallery_bug_story'] ) ? wp_kses_post( wp_unslash( $_POST['buggallery_bug_story'] ) ) : '';
        $wall_price = isset( $_POST['buggallery_wall_price'] ) ? self::sanitize_price( wp_unslash( $_POST['buggallery_wall_price'] ) ) : '150.00';
        $mail_price = isset( $_POST['buggallery_mail_price'] ) ? self::sanitize_price( wp_unslash( $_POST['buggallery_mail_price'] ) ) : '75.00';

        if ( '' === $title || '' === $story ) {
            self::redirect_with_error( __( 'Bug name and story are required.', 'buggallery' ) );
        }

        if ( empty( $_FILES['buggallery_featured_image']['name'] ) ) {
            self::redirect_with_error( __( 'Please upload a bug photo image.', 'buggallery' ) );
        }

        $post_id = wp_insert_post( [
            'post_type'    => 'bug_photo',
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_content' => $story,
            'post_author'  => get_current_user_id(),
        ], true );

        if ( is_wp_error( $post_id ) ) {
            self::redirect_with_error( $post_id->get_error_message() );
        }

        update_post_meta( $post_id, '_buggallery_wall_price', $wall_price );
        update_post_meta( $post_id, '_buggallery_mail_price', $mail_price );
        update_post_meta( $post_id, '_buggallery_related_links', self::sanitize_links( $_POST['buggallery_links'] ?? [] ) );

        // Assign species taxonomy if selected
        if ( ! empty( $_POST['buggallery_bug_species'] ) ) {
            $species_id = absint( $_POST['buggallery_bug_species'] );
            if ( term_exists( $species_id, 'bug_species' ) ) {
                wp_set_object_terms( $post_id, $species_id, 'bug_species' );
            }
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload( 'buggallery_featured_image', $post_id );
        if ( is_wp_error( $attachment_id ) ) {
            wp_delete_post( $post_id, true );
            self::redirect_with_error( $attachment_id->get_error_message() );
        }

        set_post_thumbnail( $post_id, $attachment_id );

        $redirect_url = self::current_page_url();
        $redirect_url = add_query_arg( 'buggallery_upload', 'success', $redirect_url );
        wp_safe_redirect( $redirect_url );
        exit;
    }

    private static function sanitize_links( $raw_links ): array {
        if ( ! is_array( $raw_links ) ) {
            return [];
        }

        $clean_links = [];
        foreach ( $raw_links as $raw_link ) {
            $url = isset( $raw_link['url'] ) ? esc_url_raw( wp_unslash( $raw_link['url'] ) ) : '';
            if ( '' === $url ) {
                continue;
            }

            $type = isset( $raw_link['type'] ) ? sanitize_key( wp_unslash( $raw_link['type'] ) ) : 'external';
            if ( ! in_array( $type, [ 'internal', 'external' ], true ) ) {
                $type = 'external';
            }

            $clean_links[] = [
                'title' => isset( $raw_link['title'] ) ? sanitize_text_field( wp_unslash( $raw_link['title'] ) ) : '',
                'url'   => $url,
                'type'  => $type,
            ];
        }

        return $clean_links;
    }

    private static function sanitize_price( string $value ): string {
        $number = floatval( preg_replace( '/[^0-9.\-]/', '', $value ) );
        if ( $number < 0 ) {
            $number = 0;
        }

        return number_format( $number, 2, '.', '' );
    }

    private static function redirect_with_error( string $message ): void {
        $redirect_url = self::current_page_url();
        $redirect_url = add_query_arg(
            [
                'buggallery_upload' => 'error',
                'buggallery_error'  => $message,
            ],
            $redirect_url
        );

        wp_safe_redirect( $redirect_url );
        exit;
    }

    private static function current_page_url(): string {
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
        return home_url( $request_uri );
    }
}

BugGallery_Photographer_Upload_Form::init();
