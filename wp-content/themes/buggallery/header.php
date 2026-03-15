<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main-content"><?php esc_html_e( 'Skip to main content', 'buggallery' ); ?></a>

<header class="site-header">
    <div class="site-container">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo">
            Bug<span>Gallery</span>
        </a>
        <nav class="site-nav" aria-label="<?php esc_attr_e( 'Primary', 'buggallery' ); ?>">
            <?php $gallery_url = get_post_type_archive_link( 'bug_photo' ); ?>
            <?php if ( $gallery_url ) : ?>
                <a href="<?php echo esc_url( $gallery_url ); ?>">
                    <?php esc_html_e( 'Gallery', 'buggallery' ); ?>
                </a>
            <?php endif; ?>

            <?php
            $register_page  = get_page_by_path( 'photographer-register' );
            $login_page     = get_page_by_path( 'photographer-login' );
            $dashboard_page = get_page_by_path( 'photographer-dashboard' );
            ?>

            <?php if ( ! is_user_logged_in() ) : ?>
                <?php if ( $register_page ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $register_page ) ); ?>">
                        <?php esc_html_e( 'Register', 'buggallery' ); ?>
                    </a>
                <?php endif; ?>
                <a href="<?php echo esc_url( $login_page ? get_permalink( $login_page ) : wp_login_url( home_url( '/photographer-dashboard/' ) ) ); ?>">
                    <?php esc_html_e( 'Login', 'buggallery' ); ?>
                </a>
            <?php else : ?>
                <?php if ( $dashboard_page ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $dashboard_page ) ); ?>">
                        <?php esc_html_e( 'Dashboard', 'buggallery' ); ?>
                    </a>
                <?php endif; ?>
                <?php if ( current_user_can( 'manage_options' ) ) : ?>
                    <a href="<?php echo esc_url( admin_url() ); ?>">
                        <?php esc_html_e( 'Admin', 'buggallery' ); ?>
                    </a>
                <?php endif; ?>
                <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
                    <?php esc_html_e( 'Logout', 'buggallery' ); ?>
                </a>
            <?php endif; ?>

            <?php if ( function_exists( 'WC' ) ) : ?>
                <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="site-nav-cart" aria-label="<?php esc_attr_e( 'Shopping cart', 'buggallery' ); ?>">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    <?php $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?>
                    <span class="site-nav-cart-count<?php echo $cart_count ? '' : ' is-empty'; ?>"><?php echo esc_html( $cart_count ); ?></span>
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div id="content" class="site-content">
