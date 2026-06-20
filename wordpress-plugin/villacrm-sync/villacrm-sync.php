<?php
/**
 * Plugin Name:  VillaCRM Sync
 * Plugin URI:   https://villacrm.app
 * Description:  Syncs listings from VillaCRM into WordPress as a native Custom Post Type with full SEO support.
 * Version:      1.0.0
 * Author:       VillaCRM
 * Author URI:   https://villacrm.app
 * License:      MIT
 * Text Domain:  villacrm-sync
 */

defined('ABSPATH') || exit;

define('VILLACRM_PLUGIN_FILE',    __FILE__);
define('VILLACRM_PLUGIN_DIR',     plugin_dir_path(__FILE__));
define('VILLACRM_PLUGIN_URL',     plugin_dir_url(__FILE__));
define('VILLACRM_API_BASE',       'https://villacrm.app/api/v1/public');
define('VILLACRM_OPTION_KEY',     'villacrm_api_key');
define('VILLACRM_SYNC_INTERVAL',  'villacrm_every_15_min');
define('VILLACRM_CPT',            'villacrm_listing');

// ── Boot ─────────────────────────────────────────────────────────────────────

add_action('init',                   'villacrm_register_cpt');
add_action('init',                   'villacrm_register_taxonomies');
add_action('admin_menu',             'villacrm_admin_menu');
add_action('admin_init',             'villacrm_register_settings');
add_action('admin_notices',          'villacrm_admin_notices');
add_action('villacrm_sync_listings',   'villacrm_do_sync');
add_filter('cron_schedules',         'villacrm_cron_schedules');
add_action('wp_enqueue_scripts',     'villacrm_enqueue_assets');

register_activation_hook(__FILE__,   'villacrm_activate');
register_deactivation_hook(__FILE__, 'villacrm_deactivate');

// ── Activation / Deactivation ────────────────────────────────────────────────

function villacrm_activate(): void {
    villacrm_register_cpt();
    villacrm_register_taxonomies();
    flush_rewrite_rules();

    if (!wp_next_scheduled('villacrm_sync_listings')) {
        wp_schedule_event(time(), VILLACRM_SYNC_INTERVAL, 'villacrm_sync_listings');
    }
}

function villacrm_deactivate(): void {
    $timestamp = wp_next_scheduled('villacrm_sync_listings');
    if ($timestamp) wp_unschedule_event($timestamp, 'villacrm_sync_listings');
    flush_rewrite_rules();
}

// ── Cron schedule ────────────────────────────────────────────────────────────

function villacrm_cron_schedules(array $schedules): array {
    $schedules[VILLACRM_SYNC_INTERVAL] = [
        'interval' => 15 * MINUTE_IN_SECONDS,
        'display'  => __('Every 15 minutes', 'villacrm-sync'),
    ];
    return $schedules;
}

// ── Custom Post Type ─────────────────────────────────────────────────────────

function villacrm_register_cpt(): void {
    register_post_type(VILLACRM_CPT, [
        'labels' => [
            'name'               => __('VillaCRM Listings',      'villacrm-sync'),
            'singular_name'      => __('VillaCRM Listing',       'villacrm-sync'),
            'menu_name'          => __('Listings (VillaCRM)',     'villacrm-sync'),
            'all_items'          => __('All Listings',          'villacrm-sync'),
            'add_new'            => __('Sync Now',              'villacrm-sync'),
        ],
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => ['slug' => 'listings', 'with_front' => false],
        'supports'           => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-building',
        'menu_position'      => 5,
    ]);
}

// ── Taxonomies ───────────────────────────────────────────────────────────────

function villacrm_register_taxonomies(): void {
    register_taxonomy('listing_type', VILLACRM_CPT, [
        'label'        => __('Listing Type', 'villacrm-sync'),
        'hierarchical' => true,
        'rewrite'      => ['slug' => 'listing-type'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('listing_location', VILLACRM_CPT, [
        'label'        => __('Location', 'villacrm-sync'),
        'hierarchical' => false,
        'rewrite'      => ['slug' => 'location'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('property_features', VILLACRM_CPT, [
        'label'        => __('Features', 'villacrm-sync'),
        'hierarchical' => false,
        'rewrite'      => ['slug' => 'feature'],
        'show_in_rest' => true,
    ]);
}

// ── Admin settings ───────────────────────────────────────────────────────────

function villacrm_admin_menu(): void {
    add_submenu_page(
        'edit.php?post_type=' . VILLACRM_CPT,
        __('VillaCRM Settings', 'villacrm-sync'),
        __('Settings', 'villacrm-sync'),
        'manage_options',
        'villacrm-settings',
        'villacrm_settings_page',
    );
}

function villacrm_register_settings(): void {
    register_setting('villacrm_settings_group', VILLACRM_OPTION_KEY, [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_setting('villacrm_settings_group', 'villacrm_webhook_secret', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    // Auto-generate a webhook secret on first activation if none exists
    if (!get_option('villacrm_webhook_secret')) {
        update_option('villacrm_webhook_secret', wp_generate_password(64, false));
    }
}

function villacrm_admin_notices(): void {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, VILLACRM_CPT) === false) return;

    if (!get_option(VILLACRM_OPTION_KEY)) {
        echo '<div class="notice notice-warning"><p>'
            . sprintf(
                /* translators: %s: settings page URL */
                __('<strong>VillaCRM Sync:</strong> No API key configured. <a href="%s">Add your key →</a>', 'villacrm-sync'),
                esc_url(admin_url('edit.php?post_type=' . VILLACRM_CPT . '&page=villacrm-settings'))
            )
            . '</p></div>';
    }
}

function villacrm_settings_page(): void {
    $last_sync   = get_option('villacrm_last_sync');
    $last_status = get_option('villacrm_last_sync_status', '');
    $last_count  = get_option('villacrm_last_sync_count', 0);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('VillaCRM Sync Settings', 'villacrm-sync'); ?></h1>

        <?php if ($last_sync): ?>
        <div class="notice notice-<?php echo $last_status === 'ok' ? 'success' : 'error'; ?> is-dismissible">
            <p>
                <?php if ($last_status === 'ok'): ?>
                    <?php printf(esc_html__('Last sync: %1$s — %2$d listing(s) updated.', 'villacrm-sync'), esc_html($last_sync), intval($last_count)); ?>
                <?php else: ?>
                    <?php printf(esc_html__('Last sync failed at %s. Check your API key.', 'villacrm-sync'), esc_html($last_sync)); ?>
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields('villacrm_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="villacrm_api_key"><?php esc_html_e('VillaCRM API Read Key', 'villacrm-sync'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="villacrm_api_key" name="<?php echo esc_attr(VILLACRM_OPTION_KEY); ?>"
                               value="<?php echo esc_attr(get_option(VILLACRM_OPTION_KEY)); ?>"
                               class="regular-text" placeholder="pk_pub_live_…" autocomplete="off" />
                        <p class="description">
                            <?php esc_html_e('Generate a "Public Read" key in your VillaCRM dashboard under Settings → API Keys.', 'villacrm-sync'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="villacrm_webhook_secret"><?php esc_html_e('Webhook Signing Secret', 'villacrm-sync'); ?></label>
                    </th>
                    <td>
                        <?php $secret = get_option('villacrm_webhook_secret', ''); ?>
                        <input type="text" id="villacrm_webhook_secret" name="villacrm_webhook_secret"
                               value="<?php echo esc_attr($secret); ?>"
                               class="regular-text" autocomplete="off" readonly
                               style="font-family:monospace;background:#f6f7f7;" />
                        <p class="description">
                            <?php esc_html_e('Copy this secret into VillaCRM (Settings → Webhooks → Register Endpoint) so VillaCRM can sign payloads sent to your WordPress site. This is auto-generated and read-only here.', 'villacrm-sync'); ?>
                        </p>
                        <p>
                            <button type="button" id="villacrm-copy-secret" class="button button-secondary" style="margin-top:4px;">
                                <?php esc_html_e('Copy to Clipboard', 'villacrm-sync'); ?>
                            </button>
                        </p>
                        <script>
                        document.getElementById('villacrm-copy-secret').addEventListener('click', function() {
                            var el = document.getElementById('villacrm_webhook_secret');
                            navigator.clipboard.writeText(el.value).then(function() {
                                document.getElementById('villacrm-copy-secret').textContent = '✓ Copied!';
                                setTimeout(function() {
                                    document.getElementById('villacrm-copy-secret').textContent = '<?php echo esc_js(__('Copy to Clipboard', 'villacrm-sync')); ?>';
                                }, 2000);
                            });
                        });
                        </script>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Settings', 'villacrm-sync')); ?>
        </form>

        <hr>
        <h2><?php esc_html_e('Manual Sync', 'villacrm-sync'); ?></h2>
        <p><?php esc_html_e('Listings sync automatically every 15 minutes. Click below to sync immediately.', 'villacrm-sync'); ?></p>
        <form method="post">
            <?php wp_nonce_field('villacrm_manual_sync', 'villacrm_nonce'); ?>
            <input type="hidden" name="villacrm_action" value="sync_now">
            <?php submit_button(__('Sync Now', 'villacrm-sync'), 'secondary'); ?>
        </form>

        <?php
        if (
            isset($_POST['villacrm_action'], $_POST['villacrm_nonce']) &&
            $_POST['villacrm_action'] === 'sync_now' &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['villacrm_nonce'])), 'villacrm_manual_sync')
        ) {
            $count = villacrm_do_sync();
            if ($count !== false) {
                echo '<div class="notice notice-success inline"><p>'
                    . sprintf(esc_html__('Sync complete — %d listing(s) processed.', 'villacrm-sync'), intval($count))
                    . '</p></div>';
            } else {
                echo '<div class="notice notice-error inline"><p>'
                    . esc_html__('Sync failed. Verify your API key is correct.', 'villacrm-sync')
                    . '</p></div>';
            }
        }
        ?>

        <hr>
        <h2><?php esc_html_e('Webhook (optional)', 'villacrm-sync'); ?></h2>
        <p>
            <?php esc_html_e('For instant updates, register this URL as a webhook endpoint in VillaCRM (Settings → Webhooks):', 'villacrm-sync'); ?>
            <br>
            <code><?php echo esc_url(rest_url('villacrm-sync/v1/webhook')); ?></code>
        </p>
    </div>
    <?php
}

// ── REST webhook endpoint ─────────────────────────────────────────────────────

add_action('rest_api_init', function () {
    register_rest_route('villacrm-sync/v1', '/webhook', [
        'methods'             => 'POST',
        'callback'            => 'villacrm_handle_webhook',
        'permission_callback' => '__return_true',
    ]);
});

function villacrm_handle_webhook(\WP_REST_Request $request): \WP_REST_Response {
    $secret    = get_option('villacrm_webhook_secret', '');
    $body      = $request->get_body();
    $signature = $request->get_header('x-villacrm-signature-256');

    if ($secret && $signature) {
        $expected = 'sha256=' . hash_hmac('sha256', $body, $secret);
        if (!hash_equals($expected, $signature)) {
            return new \WP_REST_Response(['error' => 'Invalid signature.'], 401);
        }
    }

    $payload = json_decode($body, true);
    $event   = $payload['event'] ?? '';

    if (in_array($event, ['listing.published', 'listing.updated', 'listing.price_reduced'], true)) {
        villacrm_do_sync();
    } elseif ($event === 'listing.deleted') {
        $listingId = $payload['listing_id'] ?? 0;
        villacrm_unpublish_listing($listingId);
    }

    return new \WP_REST_Response(['received' => true], 200);
}

// ── Sync logic ────────────────────────────────────────────────────────────────

function villacrm_do_sync(): int|false {
    $apiKey = get_option(VILLACRM_OPTION_KEY, '');
    if (!$apiKey) return false;

    $page  = 1;
    $total = 0;

    do {
        $response = wp_remote_get(VILLACRM_API_BASE . '/listings?' . http_build_query([
            'per_page' => 50,
            'page'     => $page,
        ]), [
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
                'Accept'        => 'application/json',
            ],
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            villacrm_set_sync_status('error', 0);
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            villacrm_set_sync_status('error', 0);
            return false;
        }

        $data     = json_decode(wp_remote_retrieve_body($response), true);
        $listings = $data['data'] ?? [];
        $lastPage = $data['meta']['last_page'] ?? 1;

        foreach ($listings as $listing) {
            villacrm_upsert_listing($listing);
            $total++;
        }

        $page++;
    } while ($page <= $lastPage);

    villacrm_set_sync_status('ok', $total);
    return $total;
}

function villacrm_upsert_listing(array $l): void {
    $villacrm_id = (int) ($l['id'] ?? 0);
    if (!$villacrm_id) return;

    // Check if post already exists
    $existing = get_posts([
        'post_type'      => VILLACRM_CPT,
        'meta_key'       => '_villacrm_listing_id',
        'meta_value'     => $villacrm_id,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    $city    = $l['property']['city']  ?? '';
    $address = $l['property']['address'] ?? '';
    $price   = number_format((float) ($l['listing_price'] ?? 0), 0);

    $postData = [
        'post_type'    => VILLACRM_CPT,
        'post_status'  => 'publish',
        'post_title'   => $l['headline'] ?: "{$city} — {$price}",
        'post_content' => $l['description'] ?? '',
        'post_excerpt' => wp_trim_words($l['description'] ?? '', 30),
    ];

    if (!empty($existing)) {
        $postId = $existing[0];
        $postData['ID'] = $postId;
        wp_update_post($postData);
    } else {
        $postId = wp_insert_post($postData);
        if (is_wp_error($postId)) return;
    }

    // Meta fields
    $meta = [
        '_villacrm_listing_id'   => $villacrm_id,
        '_villacrm_price'        => $l['listing_price']  ?? '',
        '_villacrm_status'       => $l['status']         ?? '',
        '_villacrm_mandate_type' => $l['mandate_type']   ?? '',
        '_villacrm_city'         => $city,
        '_villacrm_address'      => $address,
        '_villacrm_bedrooms'     => $l['property']['bedrooms']    ?? '',
        '_villacrm_bathrooms'    => $l['property']['bathrooms']   ?? '',
        '_villacrm_floor_area'   => $l['property']['floor_area_sqm'] ?? '',
        '_villacrm_cover_photo'  => $l['cover_photo']    ?? '',
        '_villacrm_agent_name'   => $l['agent']['name']  ?? '',
        '_villacrm_agent_phone'  => $l['agent']['phone'] ?? '',
        '_villacrm_agent_email'  => $l['agent']['email'] ?? '',
        '_villacrm_days_on_market' => $l['days_on_market'] ?? '',
        '_villacrm_features'     => maybe_serialize($l['features'] ?? []),
    ];

    foreach ($meta as $key => $value) {
        update_post_meta($postId, $key, $value);
    }

    // Taxonomies
    if ($l['mandate_type'] ?? '') {
        wp_set_object_terms($postId, $l['mandate_type'], 'listing_type', false);
    }
    if ($city) {
        wp_set_object_terms($postId, $city, 'listing_location', false);
    }
    if (!empty($l['features'])) {
        wp_set_object_terms($postId, $l['features'], 'property_features', false);
    }

    // Featured image via cover photo URL (side-load once)
    if (!empty($l['cover_photo']) && !has_post_thumbnail($postId)) {
        villacrm_sideload_image($l['cover_photo'], $postId);
    }
}

function villacrm_unpublish_listing(int $villacrm_id): void {
    $posts = get_posts([
        'post_type'      => VILLACRM_CPT,
        'meta_key'       => '_villacrm_listing_id',
        'meta_value'     => $villacrm_id,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    foreach ($posts as $id) {
        wp_update_post(['ID' => $id, 'post_status' => 'draft']);
        update_post_meta($id, '_villacrm_status', 'archived');
    }
}

function villacrm_sideload_image(string $url, int $postId): void {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $attachment_id = media_sideload_image($url, $postId, null, 'id');
    if (!is_wp_error($attachment_id)) {
        set_post_thumbnail($postId, $attachment_id);
    }
}

function villacrm_set_sync_status(string $status, int $count): void {
    update_option('villacrm_last_sync',        current_time('mysql'));
    update_option('villacrm_last_sync_status', $status);
    update_option('villacrm_last_sync_count',  $count);
}

// ── Template loader ───────────────────────────────────────────────────────────

add_filter('template_include', 'villacrm_template_loader');

function villacrm_template_loader(string $template): string {
    // Only override if the theme has NOT provided its own template
    if (is_singular(VILLACRM_CPT)) {
        $theme_file = locate_template(['single-' . VILLACRM_CPT . '.php', 'single.php']);
        if (!$theme_file || basename($theme_file) === 'single.php') {
            $plugin_tpl = VILLACRM_PLUGIN_DIR . 'templates/single-villacrm_listing.php';
            if (file_exists($plugin_tpl)) return $plugin_tpl;
        }
    }

    if (is_post_type_archive(VILLACRM_CPT) || is_tax(['listing_type', 'listing_location', 'property_features'])) {
        $theme_file = locate_template(['archive-' . VILLACRM_CPT . '.php', 'archive.php']);
        if (!$theme_file || basename($theme_file) === 'archive.php') {
            $plugin_tpl = VILLACRM_PLUGIN_DIR . 'templates/archive-villacrm_listing.php';
            if (file_exists($plugin_tpl)) return $plugin_tpl;
        }
    }

    return $template;
}

// ── Shortcodes ────────────────────────────────────────────────────────────────

add_shortcode('villacrm_listings',   'villacrm_shortcode_listings');
add_shortcode('villacrm_listing',    'villacrm_shortcode_single');
add_shortcode('villacrm_inquiry',    'villacrm_shortcode_inquiry');

/**
 * [villacrm_listings limit="6" mandate="sale" city="Lagos" columns="3"]
 */
function villacrm_shortcode_listings(array $atts): string {
    $atts = shortcode_atts([
        'limit'   => 6,
        'mandate' => '', // sale | rental | ''
        'city'    => '',
        'columns' => 3,
        'orderby' => 'date',
        'order'   => 'DESC',
    ], $atts, 'villacrm_listings');

    $args = [
        'post_type'      => VILLACRM_CPT,
        'post_status'    => 'publish',
        'posts_per_page' => intval($atts['limit']),
        'orderby'        => sanitize_key($atts['orderby']),
        'order'          => strtoupper($atts['order']) === 'ASC' ? 'ASC' : 'DESC',
        'tax_query'      => [],
    ];

    if ($atts['mandate']) {
        $args['tax_query'][] = [
            'taxonomy' => 'listing_type',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($atts['mandate']),
        ];
    }

    if ($atts['city']) {
        $args['tax_query'][] = [
            'taxonomy' => 'listing_location',
            'field'    => 'name',
            'terms'    => sanitize_text_field($atts['city']),
        ];
    }

    $query = new WP_Query($args);
    if (!$query->have_posts()) {
        return '<p class="villacrm-no-listings">' . esc_html__('No listings found.', 'villacrm-sync') . '</p>';
    }

    $cols = max(1, min(4, intval($atts['columns'])));
    ob_start();
    ?>
    <div class="villacrm-shortcode-grid" style="display:grid;grid-template-columns:repeat(<?php echo $cols; ?>,1fr);gap:20px;">
    <?php while ($query->have_posts()) : $query->the_post();
        $listing = villacrm_get_listing_data(); ?>
        <div class="villacrm-listing-card" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
            <a href="<?php the_permalink(); ?>" style="text-decoration:none;color:inherit;">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('medium', ['style' => 'width:100%;height:200px;object-fit:cover;display:block;']); ?>
                <?php endif; ?>
                <div style="padding:14px;">
                    <div style="font-weight:700;font-size:18px;color:#10B981;margin-bottom:4px;">
                        <?php villacrm_the_price(); ?>
                    </div>
                    <h3 style="font-size:14px;margin:0 0 6px;font-weight:600;"><?php the_title(); ?></h3>
                    <p style="font-size:12px;color:#6b7280;margin:0 0 8px;">
                        <?php echo esc_html(implode(', ', array_filter([$listing['address'], $listing['city']]))); ?>
                    </p>
                    <div style="font-size:12px;color:#6b7280;display:flex;gap:10px;flex-wrap:wrap;">
                        <?php if ($listing['bedrooms'])  : ?><span>🛏 <?php echo esc_html($listing['bedrooms']); ?></span><?php endif; ?>
                        <?php if ($listing['bathrooms']) : ?><span>🚿 <?php echo esc_html($listing['bathrooms']); ?></span><?php endif; ?>
                        <?php if ($listing['floor_area']): ?><span>📐 <?php echo esc_html($listing['floor_area']); ?>m²</span><?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
    <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * [villacrm_listing id="42"]  — renders a single listing card inline.
 */
function villacrm_shortcode_single(array $atts): string {
    $atts = shortcode_atts(['id' => 0], $atts, 'villacrm_listing');
    $id   = intval($atts['id']);
    if (!$id) return '';

    $post = get_post($id);
    if (!$post || $post->post_type !== VILLACRM_CPT) return '';

    $listing = villacrm_get_listing_data($id);
    ob_start();
    ?>
    <div class="villacrm-listing-card" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;max-width:400px;">
        <a href="<?php echo esc_url(get_permalink($id)); ?>" style="text-decoration:none;color:inherit;">
            <?php if (has_post_thumbnail($id)) : echo get_the_post_thumbnail($id, 'medium', ['style' => 'width:100%;height:220px;object-fit:cover;display:block;']); endif; ?>
            <div style="padding:16px;">
                <div style="font-weight:700;font-size:20px;color:#10B981;margin-bottom:4px;"><?php villacrm_the_price($id); ?></div>
                <h3 style="font-size:15px;margin:0 0 6px;"><?php echo esc_html(get_the_title($id)); ?></h3>
                <p style="font-size:13px;color:#6b7280;margin:0;"><?php echo esc_html(implode(', ', array_filter([$listing['address'], $listing['city']]))); ?></p>
            </div>
        </a>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * [villacrm_inquiry listing_id="42" button_label="Enquire Now"]
 */
function villacrm_shortcode_inquiry(array $atts): string {
    $atts = shortcode_atts([
        'listing_id'   => 0,
        'button_label' => __('Send Enquiry', 'villacrm-sync'),
        'primary_color'=> '#10B981',
    ], $atts, 'villacrm_inquiry');

    $api_key = get_option(VILLACRM_OPTION_KEY, '');
    if (!$api_key) return '';

    return sprintf(
        '<script src="https://cdn.villacrm.app/widgets.js" defer></script>
         <villacrm-inquiry-form agency-key="%s" listing-id="%d" primary-color="%s"></villacrm-inquiry-form>',
        esc_attr($api_key),
        intval($atts['listing_id']),
        esc_attr($atts['primary_color']),
    );
}

// ── Frontend assets ───────────────────────────────────────────────────────────

function villacrm_enqueue_assets(): void {
    if (!is_singular(VILLACRM_CPT) && !is_post_type_archive(VILLACRM_CPT)
        && !is_tax(['listing_type', 'listing_location', 'property_features'])) return;

    wp_enqueue_style(
        'villacrm-sync',
        VILLACRM_PLUGIN_URL . 'assets/villacrm-sync.css',
        [],
        '1.0.0',
    );
}

// ── Template functions (for theme developers) ─────────────────────────────────

/**
 * Display listing price formatted nicely.
 * Usage in template: <?php villacrm_the_price(); ?>
 */
function villacrm_the_price(int $postId = 0): void {
    $postId = $postId ?: get_the_ID();
    $price  = get_post_meta($postId, '_villacrm_price', true);
    echo '<span class="villacrm-price">' . esc_html(number_format((float) $price, 0)) . '</span>';
}

/**
 * Get all listing meta as an associative array.
 */
function villacrm_get_listing_data(int $postId = 0): array {
    $postId = $postId ?: get_the_ID();
    return [
        'id'            => (int) get_post_meta($postId, '_villacrm_listing_id', true),
        'price'         => (float) get_post_meta($postId, '_villacrm_price', true),
        'status'        => get_post_meta($postId, '_villacrm_status', true),
        'mandate_type'  => get_post_meta($postId, '_villacrm_mandate_type', true),
        'city'          => get_post_meta($postId, '_villacrm_city', true),
        'address'       => get_post_meta($postId, '_villacrm_address', true),
        'bedrooms'      => (int) get_post_meta($postId, '_villacrm_bedrooms', true),
        'bathrooms'     => (int) get_post_meta($postId, '_villacrm_bathrooms', true),
        'floor_area'    => (float) get_post_meta($postId, '_villacrm_floor_area', true),
        'cover_photo'   => get_post_meta($postId, '_villacrm_cover_photo', true),
        'agent_name'    => get_post_meta($postId, '_villacrm_agent_name', true),
        'agent_phone'   => get_post_meta($postId, '_villacrm_agent_phone', true),
        'agent_email'   => get_post_meta($postId, '_villacrm_agent_email', true),
        'days_on_market'=> (int) get_post_meta($postId, '_villacrm_days_on_market', true),
        'features'      => maybe_unserialize(get_post_meta($postId, '_villacrm_features', true)) ?: [],
    ];
}
