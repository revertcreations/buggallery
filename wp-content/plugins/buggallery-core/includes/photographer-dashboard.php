<?php
/**
 * Photographer Dashboard
 *
 * Provides a front-end dashboard for photographers to:
 * - View their uploaded bug photos
 * - Upload new bug photos from the front-end portal
 * - See sales statistics
 *
 * Uses a shortcode [buggallery_dashboard] that can be placed on any page.
 * Designed for non-technical photographer self-service.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BugGallery_Photographer_Dashboard {

    public static function init(): void {
        add_shortcode( 'buggallery_dashboard', [ __CLASS__, 'render_dashboard' ] );
        add_action( 'init', [ __CLASS__, 'handle_form_submission' ] );
    }

    /**
     * Render the photographer dashboard
     */
    public static function render_dashboard( array $atts = [] ): string {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to access your dashboard.', 'buggallery' ) . '</p>'
                 . wp_login_form( [ 'echo' => false, 'redirect' => get_permalink() ] );
        }

        $user = wp_get_current_user();

        // Check if user is a photographer or admin
        if ( ! in_array( 'photographer', (array) $user->roles, true ) &&
             ! in_array( 'administrator', (array) $user->roles, true ) ) {
            return '<p>' . esc_html__( 'This dashboard is for registered photographers only.', 'buggallery' ) . '</p>';
        }

        ob_start();

        // Get photographer's bug photos
        $bugs = new WP_Query( [
            'post_type'      => 'bug_photo',
            'author'         => $user->ID,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );

        $website_url = get_user_meta( $user->ID, 'buggallery_website_url', true );

        ?>
        <div class="buggallery-dashboard">
            <div class="buggallery-dashboard-header">
                <h2><?php printf( esc_html__( 'Welcome, %s', 'buggallery' ), esc_html( $user->display_name ) ); ?></h2>

                <div class="buggallery-dashboard-stats">
                    <div class="stat">
                        <span class="stat-number"><?php echo esc_html( $bugs->found_posts ); ?></span>
                        <span class="stat-label"><?php esc_html_e( 'Bug Photos', 'buggallery' ); ?></span>
                    </div>
                </div>
            </div>

            <!-- Photographer Profile -->
            <div class="buggallery-profile-section">
                <h3><?php esc_html_e( 'Your Profile', 'buggallery' ); ?></h3>
                <form method="post" class="buggallery-profile-form">
                    <?php wp_nonce_field( 'buggallery_update_profile', 'buggallery_profile_nonce' ); ?>
                    <p>
                        <label for="buggallery_website_url">
                            <?php esc_html_e( 'Your Website URL', 'buggallery' ); ?>
                        </label>
                        <input
                            type="url"
                            id="buggallery_website_url"
                            name="buggallery_website_url"
                            value="<?php echo esc_attr( $website_url ); ?>"
                            placeholder="https://yourwebsite.com"
                            class="buggallery-input"
                        >
                        <small><?php esc_html_e( 'Displayed on your bug photo pages so visitors can find your work.', 'buggallery' ); ?></small>
                    </p>
                    <button type="submit" name="buggallery_update_profile" class="buggallery-btn buggallery-btn-secondary">
                        <?php esc_html_e( 'Update Profile', 'buggallery' ); ?>
                    </button>
                </form>
            </div>

            <!-- Add New Bug Photo -->
            <div class="buggallery-actions">
                <a href="#buggallery-upload-form" class="buggallery-btn buggallery-btn-primary">
                    <?php esc_html_e( '+ Upload New Bug Photo', 'buggallery' ); ?>
                </a>
            </div>

            <?php if ( shortcode_exists( 'buggallery_upload_form' ) ) : ?>
                <?php echo do_shortcode( '[buggallery_upload_form]' ); ?>
            <?php endif; ?>

            <!-- Bug Photos Grid -->
            <?php if ( $bugs->have_posts() ) : ?>
                <div class="buggallery-dashboard-grid">
                    <?php while ( $bugs->have_posts() ) : $bugs->the_post(); ?>
                        <div class="buggallery-dashboard-card">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="card-image">
                                    <?php the_post_thumbnail( 'medium' ); ?>
                                </div>
                            <?php endif; ?>
                            <div class="card-content">
                                <h4><?php the_title(); ?></h4>
                                <p class="card-status">
                                    <span class="status-badge status-<?php echo esc_attr( get_post_status() ); ?>">
                                        <?php echo esc_html( ucfirst( get_post_status() ) ); ?>
                                    </span>
                                </p>
                                <div class="card-actions">
                                    <a href="<?php the_permalink(); ?>" class="buggallery-btn-sm">
                                        <?php esc_html_e( 'View', 'buggallery' ); ?>
                                    </a>
                                    <?php if ( current_user_can( 'manage_options' ) ) : ?>
                                        <a href="<?php echo esc_url( get_edit_post_link() ); ?>" class="buggallery-btn-sm">
                                            <?php esc_html_e( 'Edit', 'buggallery' ); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <div class="buggallery-empty-state">
                    <p><?php esc_html_e( 'You haven\'t uploaded any bug photos yet.', 'buggallery' ); ?></p>
                    <p><?php esc_html_e( 'Click the button above to upload your first bug photo!', 'buggallery' ); ?></p>
                </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Handle profile form submission
     */
    public static function handle_form_submission(): void {
        if ( ! isset( $_POST['buggallery_update_profile'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['buggallery_profile_nonce'] ?? '', 'buggallery_update_profile' ) ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            return;
        }

        $user_id = get_current_user_id();

        if ( isset( $_POST['buggallery_website_url'] ) ) {
            update_user_meta(
                $user_id,
                'buggallery_website_url',
                esc_url_raw( $_POST['buggallery_website_url'] )
            );
        }
    }
}

BugGallery_Photographer_Dashboard::init();
