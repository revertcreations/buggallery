<?php
/**
 * BugGallery Sample Content Importer
 *
 * Run via WP-CLI:
 *   wp eval-file /var/www/html/wp-content/sample-content/import.php
 *
 * This script:
 * 1. Creates bug species terms
 * 2. Imports bug photos from the images/ directory
 * 3. Creates bug_photo posts with stories, pricing, and related links
 * 4. Assigns them to the photographer user
 *
 * Images should be placed in: app/sample-content/images/
 * Naming convention: any .jpg, .jpeg, .png files
 */

if ( ! defined( 'ABSPATH' ) ) {
    echo "This script must be run via WP-CLI: wp eval-file import.php\n";
    exit( 1 );
}

// Require media handling functions
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// ─── Configuration ───────────────────────────────────────────

$photographer_user = get_user_by( 'login', 'photographer' );
if ( ! $photographer_user ) {
    WP_CLI::error( 'Photographer user not found. Create one first.' );
}
$photographer_id = $photographer_user->ID;

// ─── Species ─────────────────────────────────────────────────

$species_list = [
    'beetle'      => 'Beetle',
    'butterfly'   => 'Butterfly',
    'moth'        => 'Moth',
    'dragonfly'   => 'Dragonfly',
    'spider'      => 'Spider',
    'ant'         => 'Ant',
    'bee'         => 'Bee',
    'grasshopper' => 'Grasshopper',
    'mantis'      => 'Mantis',
    'caterpillar' => 'Caterpillar',
    'fly'         => 'Fly',
    'wasp'        => 'Wasp',
    'cicada'      => 'Cicada',
    'ladybug'     => 'Ladybug',
];

WP_CLI::log( 'Creating species terms...' );
foreach ( $species_list as $slug => $name ) {
    if ( ! term_exists( $slug, 'bug_species' ) ) {
        wp_insert_term( $name, 'bug_species', [ 'slug' => $slug ] );
        WP_CLI::log( "  Created: $name" );
    } else {
        WP_CLI::log( "  Exists: $name" );
    }
}

// ─── Sample Bug Data ─────────────────────────────────────────
// Maps image filenames to bug data. If a filename listed here
// exists in images/, it will be imported. Otherwise it's skipped.
//
// You can also just drop images in and they'll get generic data.

$bug_data = [
    'default_stories' => [
        [
            'title'       => 'The Emerald Sentinel',
            'story'       => '<p>Found perched on a rain-soaked leaf at dawn, this beetle\'s iridescent armor caught the first light of morning like a living jewel. In macro, you can see every ridge and texture of its exoskeleton — a masterwork of natural engineering millions of years in the making.</p><p>What struck me most was how still it remained, as if posing for the camera. These beetles are common in the Pacific Northwest, but their beauty is anything but ordinary when you take the time to really look.</p>',
            'species'     => 'beetle',
            'wall_price'  => '175.00',
            'mail_price'  => '85.00',
            'links'       => [
                [ 'title' => 'Beetle anatomy on Wikipedia', 'url' => 'https://en.wikipedia.org/wiki/Beetle', 'type' => 'external' ],
                [ 'title' => 'Iridescence in nature (YouTube)', 'url' => 'https://www.youtube.com/watch?v=3g246GGUNvM', 'type' => 'external' ],
            ],
        ],
        [
            'title'       => 'Wings of Fire',
            'story'       => '<p>This butterfly landed on my lens hood while I was shooting wildflowers — as if demanding to be the subject instead. The wing pattern is a complex warning display, evolved over thousands of generations to tell predators: "I taste terrible. Move along."</p><p>Nature\'s graphic design puts anything we create to shame. Each scale on those wings is a precisely engineered optical structure, refracting light into the colors we see. No pigment required for most of these hues — it\'s pure physics.</p>',
            'species'     => 'butterfly',
            'wall_price'  => '200.00',
            'mail_price'  => '95.00',
            'links'       => [
                [ 'title' => 'How butterfly wings create color', 'url' => 'https://en.wikipedia.org/wiki/Structural_coloration', 'type' => 'external' ],
                [ 'title' => 'Butterfly migration patterns', 'url' => 'https://www.youtube.com/watch?v=gYJnAHLMhOg', 'type' => 'external' ],
            ],
        ],
        [
            'title'       => 'The Patient Hunter',
            'story'       => '<p>I spent three hours lying in wet grass to get this shot. This spider had built a web between two fence posts and was waiting, perfectly motionless, at its center. The morning dew had turned every strand of silk into a string of diamonds.</p><p>Spider silk is five times stronger than steel by weight. This tiny architect constructs a new web every single day, recycling the old one by eating it. The engineering precision visible in this photo is repeated millions of times across every meadow, every morning.</p>',
            'species'     => 'spider',
            'wall_price'  => '150.00',
            'mail_price'  => '75.00',
            'links'       => [
                [ 'title' => 'The incredible strength of spider silk', 'url' => 'https://en.wikipedia.org/wiki/Spider_silk', 'type' => 'external' ],
                [ 'title' => 'Web construction time-lapse (YouTube)', 'url' => 'https://www.youtube.com/watch?v=J4MBMnkIkB0', 'type' => 'external' ],
            ],
        ],
        [
            'title'       => 'Neon Drifter',
            'story'       => '<p>Dragonflies are the fighter jets of the insect world — capable of flying in any direction, hovering in place, and reaching speeds of 35 mph. This one paused just long enough for me to capture the translucent wings backlit by the setting sun.</p><p>Look closely at the wing structure: each one operates independently, giving dragonflies aerial maneuverability that engineers are still trying to replicate in drones. They\'ve been flying like this for 300 million years — longer than dinosaurs existed.</p>',
            'species'     => 'dragonfly',
            'wall_price'  => '225.00',
            'mail_price'  => '100.00',
            'links'       => [
                [ 'title' => 'Dragonfly flight mechanics', 'url' => 'https://en.wikipedia.org/wiki/Dragonfly', 'type' => 'external' ],
                [ 'title' => 'Dragonflies in slow motion (YouTube)', 'url' => 'https://www.youtube.com/watch?v=ktrDMn8PrHU', 'type' => 'external' ],
            ],
        ],
        [
            'title'       => 'Golden Guard',
            'story'       => '<p>Bees don\'t just make honey — they hold our entire food system together. This macro shot reveals the pollen baskets on the hind legs, packed full after a morning of foraging. Every grain of pollen visible here will become food for the next generation.</p><p>I photographed this bee on a lavender bush in my backyard. It took 200 shots to get one where the compound eyes were in perfect focus. Those eyes contain nearly 7,000 individual lenses, each seeing a slightly different angle of the world.</p>',
            'species'     => 'bee',
            'wall_price'  => '165.00',
            'mail_price'  => '80.00',
            'links'       => [
                [ 'title' => 'The waggle dance explained', 'url' => 'https://en.wikipedia.org/wiki/Waggle_dance', 'type' => 'external' ],
                [ 'title' => 'Inside a beehive (YouTube)', 'url' => 'https://www.youtube.com/watch?v=bFJFizIljhY', 'type' => 'external' ],
            ],
        ],
    ],
];

// ─── Import Images & Create Posts ────────────────────────────

$images_dir = __DIR__ . '/images';
// GLOB_BRACE not available on Alpine Linux — search each extension separately
$image_files = array_merge(
    glob( $images_dir . '/*.jpg' ) ?: [],
    glob( $images_dir . '/*.jpeg' ) ?: [],
    glob( $images_dir . '/*.png' ) ?: [],
    glob( $images_dir . '/*.webp' ) ?: []
);

if ( empty( $image_files ) ) {
    WP_CLI::warning( 'No images found in ' . $images_dir );
    WP_CLI::warning( 'Place .jpg, .jpeg, .png, or .webp files in app/sample-content/images/ and re-run.' );
    exit( 0 );
}

WP_CLI::log( '' );
WP_CLI::log( sprintf( 'Found %d images. Importing...', count( $image_files ) ) );

$stories = $bug_data['default_stories'];
$story_count = count( $stories );
$created_post_ids = [];

foreach ( $image_files as $index => $image_path ) {
    $filename = basename( $image_path );
    $story_index = $index % $story_count;
    $data = $stories[ $story_index ];

    // Adjust title if we're cycling through stories
    $title = $data['title'];
    if ( $index >= $story_count ) {
        $title = $data['title'] . ' ' . ( intval( $index / $story_count ) + 1 );
    }

    WP_CLI::log( '' );
    WP_CLI::log( "[$index] Importing: $filename as \"$title\"" );

    // Check if post already exists
    $existing = get_posts( [
        'post_type'  => 'bug_photo',
        'title'      => $title,
        'numberposts' => 1,
    ] );

    if ( ! empty( $existing ) ) {
        WP_CLI::log( "  Skipping: post already exists (ID: {$existing[0]->ID})" );
        $created_post_ids[] = $existing[0]->ID;
        continue;
    }

    // Create the post
    $post_id = wp_insert_post( [
        'post_title'   => $title,
        'post_content' => $data['story'],
        'post_status'  => 'publish',
        'post_type'    => 'bug_photo',
        'post_author'  => $photographer_id,
    ] );

    if ( is_wp_error( $post_id ) ) {
        WP_CLI::warning( "  Failed to create post: " . $post_id->get_error_message() );
        continue;
    }

    WP_CLI::log( "  Created post ID: $post_id" );

    // Upload and attach image
    $file_array = [
        'name'     => $filename,
        'tmp_name' => $image_path,
    ];

    // Copy file to temp location (media_handle_sideload moves the file)
    $tmp = wp_tempnam( $filename );
    copy( $image_path, $tmp );
    $file_array['tmp_name'] = $tmp;

    $attachment_id = media_handle_sideload( $file_array, $post_id, $title );

    if ( is_wp_error( $attachment_id ) ) {
        WP_CLI::warning( "  Failed to upload image: " . $attachment_id->get_error_message() );
    } else {
        set_post_thumbnail( $post_id, $attachment_id );
        WP_CLI::log( "  Attached image (attachment ID: $attachment_id)" );
    }

    // Set species
    $species_slug = $data['species'];
    wp_set_object_terms( $post_id, $species_slug, 'bug_species' );
    WP_CLI::log( "  Species: $species_slug" );

    // Set pricing meta
    update_post_meta( $post_id, '_buggallery_wall_price', $data['wall_price'] );
    update_post_meta( $post_id, '_buggallery_mail_price', $data['mail_price'] );
    WP_CLI::log( "  Wall: \${$data['wall_price']} | Mail: \${$data['mail_price']}" );

    // Set related links
    update_post_meta( $post_id, '_buggallery_related_links', $data['links'] );
    WP_CLI::log( '  Related links: ' . count( $data['links'] ) );

    // Sync WooCommerce product (must call directly since hook may not fire during import)
    if ( class_exists( 'BugGallery_Woo_Integration' ) ) {
        BugGallery_Woo_Integration::sync_product( $post_id, get_post( $post_id ) );
        $woo_product_id = get_post_meta( $post_id, '_buggallery_product_id', true );
        WP_CLI::log( "  WooCommerce product synced (ID: $woo_product_id)" );
    }

    $created_post_ids[] = $post_id;
}

// ─── Add Internal Cross-Links ────────────────────────────────

if ( count( $created_post_ids ) > 1 ) {
    WP_CLI::log( '' );
    WP_CLI::log( 'Adding internal cross-links between bugs...' );

    foreach ( $created_post_ids as $i => $post_id ) {
        $existing_links = get_post_meta( $post_id, '_buggallery_related_links', true ) ?: [];

        // Link to the next bug in the list (circular)
        $next_index = ( $i + 1 ) % count( $created_post_ids );
        $next_id = $created_post_ids[ $next_index ];
        $next_title = get_the_title( $next_id );

        $existing_links[] = [
            'title' => "See also: $next_title",
            'url'   => get_permalink( $next_id ),
            'type'  => 'internal',
        ];

        update_post_meta( $post_id, '_buggallery_related_links', $existing_links );
        WP_CLI::log( "  Linked \"" . get_the_title( $post_id ) . "\" -> \"$next_title\"" );
    }
}

// ─── Create Photographer Dashboard Page ──────────────────────

$dashboard_page = get_page_by_path( 'photographer-dashboard' );
if ( ! $dashboard_page ) {
    $page_id = wp_insert_post( [
        'post_title'   => 'Photographer Dashboard',
        'post_content' => '[buggallery_dashboard]',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_name'    => 'photographer-dashboard',
    ] );
    WP_CLI::log( '' );
    WP_CLI::log( "Created Photographer Dashboard page (ID: $page_id)" );
}

// ─── Summary ─────────────────────────────────────────────────

WP_CLI::log( '' );
WP_CLI::success( sprintf(
    'Import complete! Created %d bug photos. Visit http://localhost:8888/bug/ to see the gallery.',
    count( $created_post_ids )
) );
WP_CLI::log( '' );
WP_CLI::log( 'Admin login:' );
WP_CLI::log( '  URL:      http://localhost:8888/wp-admin/' );
WP_CLI::log( '  User:     admin' );
WP_CLI::log( '  Password: password' );
WP_CLI::log( '' );
WP_CLI::log( 'Photographer login:' );
WP_CLI::log( '  User:     photographer' );
WP_CLI::log( '  Password: photographer123' );
