<?php
/**
 * Front-End Photographer Login
 *
 * Provides shortcode [buggallery_login] for a branded login experience.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BugGallery_User_Login {

    public static function init(): void {
        add_shortcode( 'buggallery_login', [ __CLASS__, 'render_login_form' ] );
        add_action( 'init', [ __CLASS__, 'handle_login_submission' ] );
    }

    public static function render_login_form( array $atts = [] ): string {
        if ( is_user_logged_in() ) {
            $dashboard_page = get_page_by_path( 'photographer-dashboard' );
            $dashboard_url  = $dashboard_page ? get_permalink( $dashboard_page ) : home_url( '/' );

            return '<p>' . esc_html__( 'You are already logged in.', 'buggallery' ) . '</p>'
                . '<p><a class="buggallery-btn buggallery-btn-primary" href="' . esc_url( $dashboard_url ) . '">'
                . esc_html__( 'Go to Dashboard', 'buggallery' )
                . '</a></p>';
        }

        $error_message = '';
        if ( isset( $_GET['buggallery_login'] ) && 'error' === $_GET['buggallery_login'] ) {
            $error_message = isset( $_GET['buggallery_error'] )
                ? sanitize_text_field( wp_unslash( $_GET['buggallery_error'] ) )
                : __( 'Login failed. Please try again.', 'buggallery' );
        }

        $register_page = get_page_by_path( 'photographer-register' );
        $register_url  = $register_page ? get_permalink( $register_page ) : home_url( '/photographer-register/' );

        ob_start();
        ?>
        <div class="buggallery-login-wrap">
            <h2><?php esc_html_e( 'Photographer Login', 'buggallery' ); ?></h2>

            <?php if ( $error_message ) : ?>
                <p class="buggallery-notice buggallery-notice-error"><?php echo esc_html( $error_message ); ?></p>
            <?php endif; ?>

            <form method="post" class="buggallery-login-form">
                <?php wp_nonce_field( 'buggallery_login_user', 'buggallery_login_nonce' ); ?>
                <input type="hidden" name="buggallery_login_action" value="1">

                <p>
                    <label for="buggallery_login_username"><?php esc_html_e( 'Username or Email', 'buggallery' ); ?></label>
                    <input type="text" id="buggallery_login_username" name="buggallery_login_username" class="buggallery-input" required>
                </p>

                <p>
                    <label for="buggallery_login_password"><?php esc_html_e( 'Password', 'buggallery' ); ?></label>
                    <input type="password" id="buggallery_login_password" name="buggallery_login_password" class="buggallery-input" required>
                </p>

                <p class="buggallery-login-remember">
                    <label>
                        <input type="checkbox" name="buggallery_login_remember" value="1">
                        <?php esc_html_e( 'Remember me', 'buggallery' ); ?>
                    </label>
                </p>

                <button type="submit" class="buggallery-btn buggallery-btn-primary">
                    <?php esc_html_e( 'Log In', 'buggallery' ); ?>
                </button>
            </form>

            <div class="buggallery-register-login">
                <p>
                    <?php esc_html_e( 'Need an account?', 'buggallery' ); ?>
                    <a href="<?php echo esc_url( $register_url ); ?>"><?php esc_html_e( 'Register as photographer', 'buggallery' ); ?></a>
                </p>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    public static function handle_login_submission(): void {
        if ( ! isset( $_POST['buggallery_login_action'] ) ) {
            return;
        }

        if ( ! isset( $_POST['buggallery_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['buggallery_login_nonce'] ) ), 'buggallery_login_user' ) ) {
            self::redirect_with_error( __( 'Security check failed. Please try again.', 'buggallery' ) );
        }

        $username = isset( $_POST['buggallery_login_username'] )
            ? sanitize_text_field( wp_unslash( $_POST['buggallery_login_username'] ) )
            : '';
        $password = isset( $_POST['buggallery_login_password'] )
            ? (string) $_POST['buggallery_login_password']
            : '';
        $remember = isset( $_POST['buggallery_login_remember'] ) && '1' === (string) $_POST['buggallery_login_remember'];

        if ( '' === $username || '' === $password ) {
            self::redirect_with_error( __( 'Please enter your login and password.', 'buggallery' ) );
        }

        if ( is_email( $username ) ) {
            $user_obj = get_user_by( 'email', $username );
            if ( $user_obj instanceof \WP_User ) {
                $username = $user_obj->user_login;
            }
        }

        $user = wp_signon( [
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember,
        ], is_ssl() );

        if ( is_wp_error( $user ) ) {
            self::redirect_with_error( __( 'Invalid credentials. Please try again.', 'buggallery' ) );
        }

        $dashboard_page = get_page_by_path( 'photographer-dashboard' );
        $redirect_url   = $dashboard_page ? get_permalink( $dashboard_page ) : home_url( '/' );

        if ( ! in_array( 'photographer', (array) $user->roles, true ) && ! in_array( 'administrator', (array) $user->roles, true ) ) {
            wp_logout();
            self::redirect_with_error( __( 'This login is for photographer accounts only.', 'buggallery' ) );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    private static function redirect_with_error( string $message ): void {
        $redirect_url = self::current_page_url();
        $redirect_url = add_query_arg(
            [
                'buggallery_login' => 'error',
                'buggallery_error' => $message,
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

BugGallery_User_Login::init();
