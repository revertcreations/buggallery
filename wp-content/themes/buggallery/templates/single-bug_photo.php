<?php
/**
 * Template: Single Bug Photo
 *
 * This is THE key page — the QR code destination.
 * Three clear sections:
 * 1. Bug photo + story
 * 2. Photographer name + website link
 * 3. Purchase options ("Take from Wall" / "Mail Me a Print")
 *
 * Plus: related links chain at the bottom.
 * Fully mobile-optimised.
 */

get_header();

while ( have_posts() ) :
    the_post();

    $post_id         = get_the_ID();
    $photographer_id = get_the_author_meta( 'ID' );
    $photographer    = get_the_author_meta( 'display_name' );
    $photographer_url = get_user_meta( $photographer_id, 'buggallery_website_url', true );
    $wall_price      = get_post_meta( $post_id, '_buggallery_wall_price', true ) ?: '150.00';
    $mail_price      = get_post_meta( $post_id, '_buggallery_mail_price', true ) ?: '75.00';
    $related_links   = get_post_meta( $post_id, '_buggallery_related_links', true ) ?: [];
    $purchase_urls   = class_exists( 'BugGallery_Woo_Integration' )
                         ? BugGallery_Woo_Integration::get_purchase_urls( $post_id )
                         : [ 'wall' => '#', 'mail' => '#' ];

    // Species taxonomy
    $species = get_the_terms( $post_id, 'bug_species' );
    $species_name = ( $species && ! is_wp_error( $species ) ) ? $species[0]->name : '';
?>

<main id="main-content" class="bug-page">
    <!-- SECTION 1: Hero Photo & Story -->
    <section class="bug-hero">
        <?php if ( has_post_thumbnail() ) : ?>
            <div class="bug-hero-image">
                <?php the_post_thumbnail( 'full', [
                    'class'   => 'bug-photo',
                    'loading' => 'eager',
                    'sizes'   => '100vw',
                ] ); ?>
            </div>
        <?php endif; ?>

        <div class="bug-content site-container">
            <?php if ( $species_name ) : ?>
                <span class="bug-species-badge"><?php echo esc_html( $species_name ); ?></span>
            <?php endif; ?>

            <h1 class="bug-title"><?php the_title(); ?></h1>

            <div class="bug-story">
                <?php the_content(); ?>
            </div>
        </div>
    </section>

    <!-- SECTION 2: Photographer Attribution -->
    <section class="bug-photographer site-container">
        <div class="photographer-card">
            <?php echo get_avatar( $photographer_id, 48, '', $photographer, [ 'class' => 'photographer-avatar' ] ); ?>
            <div class="photographer-info">
                <span class="photographer-label"><?php esc_html_e( 'Photographed by', 'buggallery' ); ?></span>
                <?php if ( $photographer_url ) : ?>
                    <a href="<?php echo esc_url( $photographer_url ); ?>" target="_blank" rel="noopener" class="photographer-name">
                        <?php echo esc_html( $photographer ); ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    </a>
                <?php else : ?>
                    <span class="photographer-name"><?php echo esc_html( $photographer ); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- SECTION 3: Purchase Options -->
    <section class="bug-purchase site-container">
        <h2 class="purchase-heading"><?php esc_html_e( 'Own This Print', 'buggallery' ); ?></h2>

        <div class="purchase-options">
            <!-- Take from Wall -->
            <a href="<?php echo esc_url( $purchase_urls['wall'] ); ?>" class="purchase-option purchase-wall">
                <div class="purchase-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                </div>
                <div class="purchase-details">
                    <span class="purchase-title"><?php esc_html_e( 'Take from Wall', 'buggallery' ); ?></span>
                    <span class="purchase-desc"><?php esc_html_e( 'Take this print home with you now', 'buggallery' ); ?></span>
                </div>
                <span class="purchase-price"><?php echo wp_kses_post( buggallery_format_price( $wall_price ) ); ?></span>
            </a>

            <!-- Mail Me a Print -->
            <a href="<?php echo esc_url( $purchase_urls['mail'] ); ?>" class="purchase-option purchase-mail">
                <div class="purchase-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 22 3 22 13 16 8"></polygon></svg>
                </div>
                <div class="purchase-details">
                    <span class="purchase-title"><?php esc_html_e( 'Mail Me a Print', 'buggallery' ); ?></span>
                    <span class="purchase-desc"><?php esc_html_e( 'Shipped to your door via Printful', 'buggallery' ); ?></span>
                </div>
                <span class="purchase-price"><?php echo wp_kses_post( buggallery_format_price( $mail_price ) ); ?></span>
            </a>
        </div>
    </section>

    <!-- SECTION 4: Related Links Chain -->
    <?php if ( ! empty( $related_links ) ) : ?>
        <section class="bug-related site-container">
            <h2 class="related-heading"><?php esc_html_e( 'Explore More', 'buggallery' ); ?></h2>
            <div class="related-links-chain">
                <?php foreach ( $related_links as $index => $link ) :
                    $is_external = ( $link['type'] ?? 'external' ) === 'external';
                    $target      = $is_external ? ' target="_blank" rel="noopener"' : '';
                ?>
                    <a
                        href="<?php echo esc_url( $link['url'] ); ?>"
                        class="related-link <?php echo $is_external ? 'related-external' : 'related-internal'; ?>"
                        <?php echo $target; ?>
                    >
                        <span class="related-link-number"><?php echo esc_html( $index + 1 ); ?></span>
                        <span class="related-link-title"><?php echo esc_html( $link['title'] ?: $link['url'] ); ?></span>
                        <?php if ( $is_external ) : ?>
                            <svg class="related-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        <?php else : ?>
                            <svg class="related-link-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- QR Code (hidden on mobile, useful for admin/print preview) -->
    <?php if ( current_user_can( 'edit_post', $post_id ) && class_exists( 'BugGallery_QR_Generator' ) ) : ?>
        <section class="bug-qr site-container" style="display: none;">
            <h3><?php esc_html_e( 'QR Code for Print Display', 'buggallery' ); ?></h3>
            <img src="<?php echo esc_url( BugGallery_QR_Generator::get_print_qr_url( $post_id ) ); ?>" alt="QR Code" style="max-width: 200px;">
        </section>
    <?php endif; ?>
</main>

<?php
endwhile;

get_footer();
