<?php
/**
 * Template: Listing Archive
 *
 * Copy this file to your theme root as archive-propos_listing.php and
 * customise freely. WP_Query is already set up by WordPress.
 */

get_header();
?>

<main id="primary" class="site-main">

    <header class="propos-archive-header">
        <h1><?php post_type_archive_title(); ?></h1>
    </header>

    <?php if (have_posts()) : ?>

        <?php
        // ── Taxonomy filter bar ────────────────────────────────────────────
        $types     = get_terms(['taxonomy' => 'listing_type',     'hide_empty' => true]);
        $locations = get_terms(['taxonomy' => 'listing_location', 'hide_empty' => true]);
        ?>

        <div class="propos-filters">
            <a href="<?php echo esc_url(get_post_type_archive_link(PROPOS_CPT)); ?>"
               class="propos-filter-btn <?php echo (!get_query_var('listing_type') && !get_query_var('listing_location')) ? 'active' : ''; ?>">
                <?php esc_html_e('All', 'propos-sync'); ?>
            </a>
            <?php if (!is_wp_error($types)) : foreach ($types as $term) : ?>
                <a href="<?php echo esc_url(get_term_link($term)); ?>"
                   class="propos-filter-btn <?php echo is_tax('listing_type', $term->slug) ? 'active' : ''; ?>">
                    <?php echo esc_html(ucfirst($term->name)); ?>
                </a>
            <?php endforeach; endif; ?>
            <?php if (!is_wp_error($locations)) : foreach ($locations as $term) : ?>
                <a href="<?php echo esc_url(get_term_link($term)); ?>"
                   class="propos-filter-btn <?php echo is_tax('listing_location', $term->slug) ? 'active' : ''; ?>">
                    <?php echo esc_html($term->name); ?>
                </a>
            <?php endforeach; endif; ?>
        </div>

        <div class="propos-archive-grid">
            <?php while (have_posts()) : the_post();
                $listing = propos_get_listing_data();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('propos-listing-card'); ?>>
                    <a href="<?php the_permalink(); ?>" class="propos-card-link">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="propos-card-img">
                                <?php the_post_thumbnail('medium_large', ['loading' => 'lazy']); ?>
                                <span class="propos-badge <?php echo $listing['mandate_type'] === 'rental' ? 'propos-badge-rental' : 'propos-badge-sale'; ?>">
                                    <?php echo $listing['mandate_type'] === 'rental' ? esc_html__('For Rent', 'propos-sync') : esc_html__('For Sale', 'propos-sync'); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="propos-card-body">
                            <div class="propos-card-price">
                                <?php propos_the_price(); ?>
                            </div>
                            <h2 class="propos-card-title"><?php the_title(); ?></h2>
                            <p class="propos-card-location">
                                <?php echo esc_html(implode(', ', array_filter([$listing['address'], $listing['city']]))); ?>
                            </p>
                            <div class="propos-listing-meta">
                                <?php if ($listing['bedrooms'])  : ?>
                                    <span>🛏 <?php echo esc_html($listing['bedrooms']); ?></span>
                                <?php endif; ?>
                                <?php if ($listing['bathrooms']) : ?>
                                    <span>🚿 <?php echo esc_html($listing['bathrooms']); ?></span>
                                <?php endif; ?>
                                <?php if ($listing['floor_area']) : ?>
                                    <span>📐 <?php echo esc_html($listing['floor_area']); ?>m²</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </article>
            <?php endwhile; ?>
        </div>

        <div class="propos-pagination">
            <?php the_posts_pagination(['mid_size' => 2]); ?>
        </div>

    <?php else : ?>
        <p class="propos-no-listings"><?php esc_html_e('No listings available at this time.', 'propos-sync'); ?></p>
    <?php endif; ?>

</main>

<?php
get_sidebar();
get_footer();
