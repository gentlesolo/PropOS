<?php
/**
 * Template: Single Listing
 *
 * Copy this file to your theme root as single-propos_listing.php and
 * customise freely. All PropOS meta is accessible via propos_get_listing_data().
 */

get_header();

while (have_posts()) :
    the_post();
    $listing = propos_get_listing_data();
    $mandate = $listing['mandate_type'] === 'rental' ? 'For Rent' : 'For Sale';
    ?>

    <main id="primary" class="site-main">
        <article id="post-<?php the_ID(); ?>" <?php post_class('propos-single-listing'); ?>>

            <?php if (has_post_thumbnail()) : ?>
                <div class="propos-hero">
                    <?php the_post_thumbnail('large', ['class' => 'propos-hero__img']); ?>
                </div>
            <?php endif; ?>

            <div class="propos-listing-inner">

                <header class="propos-listing-header">
                    <span class="propos-badge <?php echo $listing['mandate_type'] === 'rental' ? 'propos-badge-rental' : 'propos-badge-sale'; ?>">
                        <?php echo esc_html($mandate); ?>
                    </span>
                    <h1 class="propos-listing-title"><?php the_title(); ?></h1>
                    <p class="propos-listing-address">
                        <?php echo esc_html(implode(', ', array_filter([$listing['address'], $listing['city']]))); ?>
                    </p>
                    <div class="propos-price">
                        <?php propos_the_price(); ?>
                    </div>
                </header>

                <div class="propos-listing-meta">
                    <?php if ($listing['bedrooms'])  : ?>
                        <span>🛏 <?php echo esc_html($listing['bedrooms']); ?> Bedrooms</span>
                    <?php endif; ?>
                    <?php if ($listing['bathrooms']) : ?>
                        <span>🚿 <?php echo esc_html($listing['bathrooms']); ?> Bathrooms</span>
                    <?php endif; ?>
                    <?php if ($listing['floor_area']) : ?>
                        <span>📐 <?php echo esc_html($listing['floor_area']); ?> m²</span>
                    <?php endif; ?>
                    <?php if ($listing['days_on_market']) : ?>
                        <span>📅 <?php printf(esc_html__('%d days on market', 'propos-sync'), $listing['days_on_market']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="propos-listing-description">
                    <?php the_content(); ?>
                </div>

                <?php if (!empty($listing['features'])) : ?>
                    <div class="propos-listing-features">
                        <h3><?php esc_html_e('Features', 'propos-sync'); ?></h3>
                        <ul class="propos-features-list">
                            <?php foreach ($listing['features'] as $feature) : ?>
                                <li><?php echo esc_html($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($listing['agent_name']) : ?>
                    <div class="propos-agent-card">
                        <h3><?php esc_html_e('Your Agent', 'propos-sync'); ?></h3>
                        <p class="propos-agent-name"><?php echo esc_html($listing['agent_name']); ?></p>
                        <?php if ($listing['agent_phone']) : ?>
                            <a href="tel:<?php echo esc_attr($listing['agent_phone']); ?>" class="propos-btn">
                                <?php echo esc_html($listing['agent_phone']); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($listing['agent_email']) : ?>
                            <a href="mailto:<?php echo esc_attr($listing['agent_email']); ?>" class="propos-agent-email">
                                <?php echo esc_html($listing['agent_email']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div><!-- .propos-listing-inner -->
        </article>
    </main>

    <?php
endwhile;

get_sidebar();
get_footer();
