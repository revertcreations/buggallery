<?php
/**
 * Main template file
 *
 * Fallback template — in practice, most traffic hits
 * single-bug_photo.php via QR codes.
 */

get_header();
?>

<main id="main-content" class="site-main">
    <div class="site-container">
        <?php if ( have_posts() ) : ?>
            <div class="post-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                    <article class="card">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail( 'bug-card', [ 'class' => 'card-image' ] ); ?>
                            </a>
                        <?php endif; ?>
                        <div class="card-body">
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <?php the_excerpt(); ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p><?php esc_html_e( 'No posts found.', 'buggallery' ); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
