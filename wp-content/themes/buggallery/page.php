<?php
/**
 * Page template
 *
 * Provides semantic, mobile-first layout for standard WordPress pages
 * like photographer register/dashboard.
 */

get_header();
?>

<main id="main-content" class="site-main page-main">
    <div class="site-container page-content">
        <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'page-article card' ); ?>>
                    <div class="card-body">
                        <header class="page-header">
                            <h1><?php the_title(); ?></h1>
                        </header>
                        <div class="page-body">
                            <?php the_content(); ?>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
