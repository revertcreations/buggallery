<?php
/**
 * Custom User Roles
 *
 * Registers the 'photographer' role with specific capabilities
 * for uploading and managing bug photos.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BugGallery_Roles {

    /**
     * Create the photographer role on plugin activation
     */
    public static function create_roles(): void {
        add_role( 'photographer', __( 'Photographer', 'buggallery' ), [
            // Basic WordPress capabilities
            'read'                   => true,
            'upload_files'           => true,
            'edit_posts'             => true,
            'edit_published_posts'   => true,
            'publish_posts'          => true,
            'delete_posts'           => true,
            'delete_published_posts' => true,

            // Photographers should NOT be able to:
            'edit_others_posts'      => false,
            'delete_others_posts'    => false,
            'manage_categories'      => false,
            'edit_pages'             => false,
            'manage_options'         => false,
        ] );
    }

    /**
     * Remove the photographer role on plugin deactivation
     */
    public static function remove_roles(): void {
        remove_role( 'photographer' );
    }

    /**
     * Initialize role-related hooks
     */
    public static function init(): void {
        add_action( 'admin_init', [ __CLASS__, 'restrict_admin_access' ] );
        add_filter( 'login_redirect', [ __CLASS__, 'photographer_login_redirect' ], 10, 3 );
        add_filter( 'show_admin_bar', [ __CLASS__, 'maybe_hide_admin_bar' ] );
    }

    /**
     * Determine whether a user should be treated as a photographer role.
     */
    private static function is_photographer_user( $user ): bool {
        return $user instanceof \WP_User
            && in_array( 'photographer', (array) $user->roles, true );
    }

    /**
     * Get the photographer dashboard URL fallback.
     */
    private static function get_dashboard_url(): string {
        $dashboard_page = get_page_by_path( 'photographer-dashboard' );
        return $dashboard_page ? get_permalink( $dashboard_page ) : home_url( '/' );
    }

    /**
     * Restrict admin dashboard access for photographers
     * Redirect them to the front-end dashboard instead
     */
    public static function restrict_admin_access(): void {
        if ( wp_doing_ajax() ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            return;
        }

        $user = wp_get_current_user();
        if ( ! self::is_photographer_user( $user ) ) {
            return;
        }

        wp_safe_redirect( self::get_dashboard_url() );
        exit;
    }

    /**
     * Redirect photographers to the front-end dashboard after login
     */
    public static function photographer_login_redirect( string $redirect_to, string $requested, $user ): string {
        if ( is_wp_error( $user ) || ! ( $user instanceof \WP_User ) ) {
            return $redirect_to;
        }

        if ( self::is_photographer_user( $user ) ) {
            return self::get_dashboard_url();
        }

        return $redirect_to;
    }

    /**
     * Hide the admin bar for photographers on the front-end.
     */
    public static function maybe_hide_admin_bar( bool $show ): bool {
        $user = wp_get_current_user();
        if ( self::is_photographer_user( $user ) ) {
            return false;
        }

        return $show;
    }
}

BugGallery_Roles::init();
