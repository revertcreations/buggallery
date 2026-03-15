<?php
/**
 * Front-End Photographer Registration
 *
 * Provides shortcode [buggallery_register] for self-service photographer signup.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BugGallery_User_Registration {

    public static function init(): void {
        add_shortcode( 'buggallery_register', [ __CLASS__, 'render_registration_form' ] );
        add_action( 'init', [ __CLASS__, 'handle_registration_submission' ] );
    }

    public static function render_registration_form( array $atts = [] ): string {
        if ( is_user_logged_in() ) {
            $dashboard_page = get_page_by_path( 'photographer-dashboard' );
            $dashboard_url  = $dashboard_page ? get_permalink( $dashboard_page ) : home_url( '/' );

            return '<p>' . esc_html__( 'You are already logged in.', 'buggallery' ) . '</p>'
                . '<p><a class="buggallery-btn buggallery-btn-primary" href="' . esc_url( $dashboard_url ) . '">'
                . esc_html__( 'Go to Dashboard', 'buggallery' )
                . '</a></p>';
        }

        $errors = [];
        if ( isset( $_GET['buggallery_register'] ) && 'error' === $_GET['buggallery_register'] ) {
            $raw = isset( $_GET['buggallery_error'] ) ? sanitize_text_field( wp_unslash( $_GET['buggallery_error'] ) ) : '';
            if ( $raw ) {
                $errors[] = $raw;
            }
        }

        if ( isset( $_GET['buggallery_register'] ) && 'success' === $_GET['buggallery_register'] ) {
            $notice = '<p class="buggallery-notice buggallery-notice-success">'
                . esc_html__( 'Account created. Please log in with your photographer account.', 'buggallery' )
                . '</p>';
        } else {
            $notice = '';
        }

        $login_page = get_page_by_path( 'photographer-login' );
        $login_url  = $login_page ? get_permalink( $login_page ) : wp_login_url( home_url( '/photographer-dashboard/' ) );

        ob_start();
        ?>
        <div class="buggallery-register-wrap">
            <h2><?php esc_html_e( 'Photographer Registration', 'buggallery' ); ?></h2>

            <?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <?php if ( ! empty( $errors ) ) : ?>
                <?php foreach ( $errors as $error ) : ?>
                    <p class="buggallery-notice buggallery-notice-error"><?php echo esc_html( $error ); ?></p>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="post" class="buggallery-register-form">
                <?php wp_nonce_field( 'buggallery_register_user', 'buggallery_register_nonce' ); ?>
                <input type="hidden" name="buggallery_register_action" value="1">

                <p>
                    <label for="buggallery_reg_name"><?php esc_html_e( 'Full Name', 'buggallery' ); ?></label>
                    <input type="text" id="buggallery_reg_name" name="buggallery_reg_name" class="buggallery-input" required>
                </p>

                <p>
                    <label for="buggallery_reg_email"><?php esc_html_e( 'Email Address', 'buggallery' ); ?></label>
                    <input type="email" id="buggallery_reg_email" name="buggallery_reg_email" class="buggallery-input" required>
                </p>

                <p>
                    <label for="buggallery_reg_password"><?php esc_html_e( 'Password', 'buggallery' ); ?></label>
                    <input type="password" id="buggallery_reg_password" name="buggallery_reg_password" class="buggallery-input" minlength="8" required>
                    <small><?php esc_html_e( 'Use at least 8 characters.', 'buggallery' ); ?></small>
                </p>

                <p class="buggallery-terms-check">
                    <label>
                        <input type="checkbox" name="buggallery_reg_terms" value="1" required>
                        <?php esc_html_e( 'I agree to upload only content I own and have rights to publish.', 'buggallery' ); ?>
                    </label>
                </p>

                <button type="submit" class="buggallery-btn buggallery-btn-primary">
                    <?php esc_html_e( 'Create Photographer Account', 'buggallery' ); ?>
                </button>
            </form>

            <div class="buggallery-register-login">
                <h3><?php esc_html_e( 'Already registered?', 'buggallery' ); ?></h3>
                <a class="buggallery-btn buggallery-btn-secondary" href="<?php echo esc_url( $login_url ); ?>">
                    <?php esc_html_e( 'Go to Photographer Login', 'buggallery' ); ?>
                </a>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    public static function handle_registration_submission(): void {
        if ( ! isset( $_POST['buggallery_register_action'] ) ) {
            return;
        }

        if ( ! isset( $_POST['buggallery_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['buggallery_register_nonce'] ) ), 'buggallery_register_user' ) ) {
            self::redirect_with_error( __( 'Security check failed. Please try again.', 'buggallery' ) );
        }

        $name     = isset( $_POST['buggallery_reg_name'] ) ? sanitize_text_field( wp_unslash( $_POST['buggallery_reg_name'] ) ) : '';
        $email    = isset( $_POST['buggallery_reg_email'] ) ? sanitize_email( wp_unslash( $_POST['buggallery_reg_email'] ) ) : '';
        $password = isset( $_POST['buggallery_reg_password'] ) ? (string) $_POST['buggallery_reg_password'] : '';
        $terms    = isset( $_POST['buggallery_reg_terms'] ) ? (string) $_POST['buggallery_reg_terms'] : '';

        if ( '' === $name || '' === $email || '' === $password ) {
            self::redirect_with_error( __( 'Please complete all required fields.', 'buggallery' ) );
        }

        if ( ! is_email( $email ) ) {
            self::redirect_with_error( __( 'Please enter a valid email address.', 'buggallery' ) );
        }

        if ( email_exists( $email ) ) {
            self::redirect_with_error( __( 'An account already exists for this email.', 'buggallery' ) );
        }

        if ( strlen( $password ) < 8 ) {
            self::redirect_with_error( __( 'Password must be at least 8 characters.', 'buggallery' ) );
        }

        if ( '1' !== $terms ) {
            self::redirect_with_error( __( 'You must agree to the terms to continue.', 'buggallery' ) );
        }

        $base_username = sanitize_user( current( explode( '@', $email ) ), true );
        if ( '' === $base_username ) {
            $base_username = 'photographer';
        }

        $username = $base_username;
        $suffix   = 1;
        while ( username_exists( $username ) ) {
            $username = $base_username . $suffix;
            $suffix++;
        }

        $user_id = wp_insert_user( [
            'user_login'   => $username,
            'user_pass'    => $password,
            'user_email'   => $email,
            'display_name' => $name,
            'first_name'   => $name,
            'role'         => 'photographer',
        ] );

        if ( is_wp_error( $user_id ) ) {
            self::redirect_with_error( $user_id->get_error_message() );
        }

        $redirect_url = self::current_page_url();
        $redirect_url = add_query_arg( 'buggallery_register', 'success', $redirect_url );

        wp_safe_redirect( $redirect_url );
        exit;
    }

    private static function redirect_with_error( string $message ): void {
        $redirect_url = self::current_page_url();
        $redirect_url = add_query_arg(
            [
                'buggallery_register' => 'error',
                'buggallery_error'    => $message,
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

BugGallery_User_Registration::init();
