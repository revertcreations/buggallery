<?php
/**
 * Template: Bug Photo Archive
 *
 * Gallery grid showing all bug photos.
 * Mobile-first responsive grid.
 */

get_header();
?>

<main id="main-content" class="site-main archive-page">
    <div class="site-container">
        <header class="archive-header">
            <h1><?php esc_html_e( 'Bug Gallery', 'buggallery' ); ?></h1>
            <p class="archive-description">
                <?php esc_html_e( 'Explore our collection of stunning bug photography. Tap any photo to learn its story and own a print.', 'buggallery' ); ?>
            </p>
        </header>

        <?php if ( have_posts() ) : ?>
            <div class="bug-grid">
                <?php while ( have_posts() ) : the_post();
                    $species      = get_the_terms( get_the_ID(), 'bug_species' );
                    $species_name = ( $species && ! is_wp_error( $species ) ) ? $species[0]->name : '';
                    $wall_price   = get_post_meta( get_the_ID(), '_buggallery_wall_price', true );
                ?>
                    <a href="<?php the_permalink(); ?>" class="bug-card">
                        <div class="bug-card-media">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'bug-card', [ 'class' => 'bug-card-image' ] ); ?>
                            <?php endif; ?>
                        </div>

                        <div class="bug-card-meta">
                            <?php if ( $species_name ) : ?>
                                <span class="bug-card-species"><?php echo esc_html( $species_name ); ?></span>
                            <?php endif; ?>

                            <h2 class="bug-card-title"><?php the_title(); ?></h2>

                            <?php if ( $wall_price ) : ?>
                                <span class="bug-card-price">
                                    <?php
                                    echo wp_kses_post(
                                        sprintf(
                                            __( 'From %s', 'buggallery' ),
                                            buggallery_format_price( $wall_price )
                                        )
                                    );
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>

            <?php the_posts_pagination( [
                'mid_size'  => 1,
                'prev_text' => __( 'Previous', 'buggallery' ),
                'next_text' => __( 'Next', 'buggallery' ),
            ] ); ?>
        <?php else : ?>
            <div class="empty-state">
                <p><?php esc_html_e( 'No bug photos have been added yet. Check back soon!', 'buggallery' ); ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
