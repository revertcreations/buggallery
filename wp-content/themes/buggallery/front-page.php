<?php
/**
 * Front page template
 *
 * Mobile-first landing page focused on the QR-to-purchase journey.
 */

get_header();

$featured_bugs = new WP_Query( [
    'post_type'      => 'bug_photo',
    'post_status'    => 'publish',
    'posts_per_page' => 6,
    'orderby'        => 'date',
    'order'          => 'DESC',
] );

$gallery_url    = get_post_type_archive_link( 'bug_photo' );
$register_page  = get_page_by_path( 'photographer-register' );
$dashboard_page = get_page_by_path( 'photographer-dashboard' );
$portal_url     = $register_page ? get_permalink( $register_page ) : home_url( '/photographer-register/' );

if ( is_user_logged_in() && $dashboard_page ) {
    $portal_url = get_permalink( $dashboard_page );
}
?>

<main id="main-content" class="site-main home-page">
    <section class="home-hero">
        <div class="site-container home-hero-content">
            <p class="home-kicker"><?php esc_html_e( 'Unmanned Gallery Experience', 'buggallery' ); ?></p>
            <h1><?php esc_html_e( 'Scan. Learn. Own the Print.', 'buggallery' ); ?></h1>
            <p class="home-subhead">
                <?php esc_html_e( 'Each wall print links to a living bug story. Customers scan a QR code, explore the photographer perspective, and buy in a few taps.', 'buggallery' ); ?>
            </p>
            <div class="home-hero-actions">
                <?php if ( $gallery_url ) : ?>
                    <a class="btn btn-primary" href="<?php echo esc_url( $gallery_url ); ?>">
                        <?php esc_html_e( 'Browse Bug Gallery', 'buggallery' ); ?>
                    </a>
                <?php endif; ?>
                <a class="btn btn-secondary" href="<?php echo esc_url( $portal_url ); ?>">
                    <?php esc_html_e( 'Photographer Portal', 'buggallery' ); ?>
                </a>
            </div>
        </div>
    </section>

    <section class="home-steps">
        <div class="site-container">
            <h2><?php esc_html_e( 'How It Works', 'buggallery' ); ?></h2>
            <div class="home-steps-grid">
                <article class="home-step-card card">
                    <div class="card-body">
                        <span class="home-step-number">1</span>
                        <h3><?php esc_html_e( 'Scan at the Wall', 'buggallery' ); ?></h3>
                        <p><?php esc_html_e( 'Every framed print has its own QR code. No staff needed for checkout.', 'buggallery' ); ?></p>
                    </div>
                </article>
                <article class="home-step-card card">
                    <div class="card-body">
                        <span class="home-step-number">2</span>
                        <h3><?php esc_html_e( 'Read the Story', 'buggallery' ); ?></h3>
                        <p><?php esc_html_e( 'The bug page combines photo, context, and related links for deeper exploration.', 'buggallery' ); ?></p>
                    </div>
                </article>
                <article class="home-step-card card">
                    <div class="card-body">
                        <span class="home-step-number">3</span>
                        <h3><?php esc_html_e( 'Choose Purchase Type', 'buggallery' ); ?></h3>
                        <p><?php esc_html_e( 'Take the physical print immediately or order a mailed print fulfilled for shipping.', 'buggallery' ); ?></p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="home-featured">
        <div class="site-container">
            <div class="home-featured-head">
                <h2><?php esc_html_e( 'Latest Bug Stories', 'buggallery' ); ?></h2>
                <?php if ( $gallery_url ) : ?>
                    <a href="<?php echo esc_url( $gallery_url ); ?>"><?php esc_html_e( 'View all', 'buggallery' ); ?></a>
                <?php endif; ?>
            </div>

            <?php if ( $featured_bugs->have_posts() ) : ?>
                <div class="home-feed-grid">
                    <?php while ( $featured_bugs->have_posts() ) : $featured_bugs->the_post();
                        $price = get_post_meta( get_the_ID(), '_buggallery_wall_price', true );
                    ?>
                        <article class="home-feed-card card">
                            <a href="<?php the_permalink(); ?>" class="home-feed-link">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'bug-card', [ 'class' => 'home-feed-image' ] ); ?>
                                <?php endif; ?>
                                <div class="card-body home-feed-meta">
                                    <h3><?php the_title(); ?></h3>
                                    <?php if ( $price ) : ?>
                                        <p class="home-feed-price">
                                            <?php
                                            echo wp_kses_post(
                                                sprintf(
                                                    __( 'From %s', 'buggallery' ),
                                                    buggallery_format_price( $price )
                                                )
                                            );
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </article>
                    <?php endwhile; ?>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <div class="home-empty card">
                    <div class="card-body">
                        <p><?php esc_html_e( 'Bug stories will appear here as photographers publish new work.', 'buggallery' ); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="home-portal-cta">
        <div class="site-container">
            <article class="card home-portal-card">
                <div class="card-body">
                    <h2><?php esc_html_e( 'Photographers: Publish Without WordPress Admin', 'buggallery' ); ?></h2>
                    <p><?php esc_html_e( 'Use the front-end portal to upload high-resolution photos, set wall and mail prices, and build related-link trails in your preferred order.', 'buggallery' ); ?></p>
                    <a class="btn btn-primary" href="<?php echo esc_url( $portal_url ); ?>">
                        <?php echo is_user_logged_in() ? esc_html__( 'Open Dashboard', 'buggallery' ) : esc_html__( 'Join as Photographer', 'buggallery' ); ?>
                    </a>
                </div>
            </article>
        </div>
    </section>
</main>

<?php
get_footer();
