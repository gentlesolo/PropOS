<?php
/**
 * Plugin Name:  PropOS Sync
 * Plugin URI:   https://propos.app
 * Description:  Syncs listings from PropOS into WordPress as a native Custom Post Type with full SEO support.
 * Version:      1.0.0
 * Author:       PropOS
 * Author URI:   https://propos.app
 * License:      MIT
 * Text Domain:  propos-sync
 */

defined('ABSPATH') || exit;

define('PROPOS_PLUGIN_FILE',    __FILE__);
define('PROPOS_PLUGIN_DIR',     plugin_dir_path(__FILE__));
define('PROPOS_PLUGIN_URL',     plugin_dir_url(__FILE__));
define('PROPOS_API_BASE',       'https://propos.app/api/v1/public');
define('PROPOS_OPTION_KEY',     'propos_api_key');
define('PROPOS_SYNC_INTERVAL',  'propos_every_15_min');
define('PROPOS_CPT',            'propos_listing');

// ── Boot ─────────────────────────────────────────────────────────────────────

add_action('init',                   'propos_register_cpt');
add_action('init',                   'propos_register_taxonomies');
add_action('admin_menu',             'propos_admin_menu');
add_action('admin_init',             'propos_register_settings');
add_action('admin_notices',          'propos_admin_notices');
add_action('propos_sync_listings',   'propos_do_sync');
add_filter('cron_schedules',         'propos_cron_schedules');
add_action('wp_enqueue_scripts',     'propos_enqueue_assets');

register_activation_hook(__FILE__,   'propos_activate');
register_deactivation_hook(__FILE__, 'propos_deactivate');

// ── Activation / Deactivation ────────────────────────────────────────────────

function propos_activate(): void {
    propos_register_cpt();
    propos_register_taxonomies();
    flush_rewrite_rules();

    if (!wp_next_scheduled('propos_sync_listings')) {
        wp_schedule_event(time(), PROPOS_SYNC_INTERVAL, 'propos_sync_listings');
    }
}

function propos_deactivate(): void {
    $timestamp = wp_next_scheduled('propos_sync_listings');
    if ($timestamp) wp_unschedule_event($timestamp, 'propos_sync_listings');
    flush_rewrite_rules();
}

// ── Cron schedule ────────────────────────────────────────────────────────────

function propos_cron_schedules(array $schedules): array {
    $schedules[PROPOS_SYNC_INTERVAL] = [
        'interval' => 15 * MINUTE_IN_SECONDS,
        'display'  => __('Every 15 minutes', 'propos-sync'),
    ];
    return $schedules;
}

// ── Custom Post Type ─────────────────────────────────────────────────────────

function propos_register_cpt(): void {
    register_post_type(PROPOS_CPT, [
        'labels' => [
            'name'               => __('PropOS Listings',      'propos-sync'),
            'singular_name'      => __('PropOS Listing',       'propos-sync'),
            'menu_name'          => __('Listings (PropOS)',     'propos-sync'),
            'all_items'          => __('All Listings',          'propos-sync'),
            'add_new'            => __('Sync Now',              'propos-sync'),
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

function propos_register_taxonomies(): void {
    register_taxonomy('listing_type', PROPOS_CPT, [
        'label'        => __('Listing Type', 'propos-sync'),
        'hierarchical' => true,
        'rewrite'      => ['slug' => 'listing-type'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('listing_location', PROPOS_CPT, [
        'label'        => __('Location', 'propos-sync'),
        'hierarchical' => false,
        'rewrite'      => ['slug' => 'location'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('property_features', PROPOS_CPT, [
        'label'        => __('Features', 'propos-sync'),
        'hierarchical' => false,
        'rewrite'      => ['slug' => 'feature'],
        'show_in_rest' => true,
    ]);
}

// ── Admin settings ───────────────────────────────────────────────────────────

function propos_admin_menu(): void {
    add_submenu_page(
        'edit.php?post_type=' . PROPOS_CPT,
        __('PropOS Settings', 'propos-sync'),
        __('Settings', 'propos-sync'),
        'manage_options',
        'propos-settings',
        'propos_settings_page',
    );
}

function propos_register_settings(): void {
    register_setting('propos_settings_group', PROPOS_OPTION_KEY, [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_setting('propos_settings_group', 'propos_webhook_secret', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    // Auto-generate a webhook secret on first activation if none exists
    if (!get_option('propos_webhook_secret')) {
        update_option('propos_webhook_secret', wp_generate_password(64, false));
    }
}

function propos_admin_notices(): void {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, PROPOS_CPT) === false) return;

    if (!get_option(PROPOS_OPTION_KEY)) {
        echo '<div class="notice notice-warning"><p>'
            . sprintf(
                /* translators: %s: settings page URL */
                __('<strong>PropOS Sync:</strong> No API key configured. <a href="%s">Add your key →</a>', 'propos-sync'),
                esc_url(admin_url('edit.php?post_type=' . PROPOS_CPT . '&page=propos-settings'))
            )
            . '</p></div>';
    }
}

function propos_settings_page(): void {
    $last_sync   = get_option('propos_last_sync');
    $last_status = get_option('propos_last_sync_status', '');
    $last_count  = get_option('propos_last_sync_count', 0);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('PropOS Sync Settings', 'propos-sync'); ?></h1>

        <?php if ($last_sync): ?>
        <div class="notice notice-<?php echo $last_status === 'ok' ? 'success' : 'error'; ?> is-dismissible">
            <p>
                <?php if ($last_status === 'ok'): ?>
                    <?php printf(esc_html__('Last sync: %1$s — %2$d listing(s) updated.', 'propos-sync'), esc_html($last_sync), intval($last_count)); ?>
                <?php else: ?>
                    <?php printf(esc_html__('Last sync failed at %s. Check your API key.', 'propos-sync'), esc_html($last_sync)); ?>
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields('propos_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="propos_api_key"><?php esc_html_e('PropOS API Read Key', 'propos-sync'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="propos_api_key" name="<?php echo esc_attr(PROPOS_OPTION_KEY); ?>"
                               value="<?php echo esc_attr(get_option(PROPOS_OPTION_KEY)); ?>"
                               class="regular-text" placeholder="pk_pub_live_…" autocomplete="off" />
                        <p class="description">
                            <?php esc_html_e('Generate a "Public Read" key in your PropOS dashboard under Settings → API Keys.', 'propos-sync'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="propos_webhook_secret"><?php esc_html_e('Webhook Signing Secret', 'propos-sync'); ?></label>
                    </th>
                    <td>
                        <?php $secret = get_option('propos_webhook_secret', ''); ?>
                        <input type="text" id="propos_webhook_secret" name="propos_webhook_secret"
                               value="<?php echo esc_attr($secret); ?>"
                               class="regular-text" autocomplete="off" readonly
                               style="font-family:monospace;background:#f6f7f7;" />
                        <p class="description">
                            <?php esc_html_e('Copy this secret into PropOS (Settings → Webhooks → Register Endpoint) so PropOS can sign payloads sent to your WordPress site. This is auto-generated and read-only here.', 'propos-sync'); ?>
                        </p>
                        <p>
                            <button type="button" id="propos-copy-secret" class="button button-secondary" style="margin-top:4px;">
                                <?php esc_html_e('Copy to Clipboard', 'propos-sync'); ?>
                            </button>
                        </p>
                        <script>
                        document.getElementById('propos-copy-secret').addEventListener('click', function() {
                            var el = document.getElementById('propos_webhook_secret');
                            navigator.clipboard.writeText(el.value).then(function() {
                                document.getElementById('propos-copy-secret').textContent = '✓ Copied!';
                                setTimeout(function() {
                                    document.getElementById('propos-copy-secret').textContent = '<?php echo esc_js(__('Copy to Clipboard', 'propos-sync')); ?>';
                                }, 2000);
                            });
                        });
                        </script>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Settings', 'propos-sync')); ?>
        </form>

        <hr>
        <h2><?php esc_html_e('Manual Sync', 'propos-sync'); ?></h2>
        <p><?php esc_html_e('Listings sync automatically every 15 minutes. Click below to sync immediately.', 'propos-sync'); ?></p>
        <form method="post">
            <?php wp_nonce_field('propos_manual_sync', 'propos_nonce'); ?>
            <input type="hidden" name="propos_action" value="sync_now">
            <?php submit_button(__('Sync Now', 'propos-sync'), 'secondary'); ?>
        </form>

        <?php
        if (
            isset($_POST['propos_action'], $_POST['propos_nonce']) &&
            $_POST['propos_action'] === 'sync_now' &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['propos_nonce'])), 'propos_manual_sync')
        ) {
            $count = propos_do_sync();
            if ($count !== false) {
                echo '<div class="notice notice-success inline"><p>'
                    . sprintf(esc_html__('Sync complete — %d listing(s) processed.', 'propos-sync'), intval($count))
                    . '</p></div>';
            } else {
                echo '<div class="notice notice-error inline"><p>'
                    . esc_html__('Sync failed. Verify your API key is correct.', 'propos-sync')
                    . '</p></div>';
            }
        }
        ?>

        <hr>
        <h2><?php esc_html_e('Webhook (optional)', 'propos-sync'); ?></h2>
        <p>
            <?php esc_html_e('For instant updates, register this URL as a webhook endpoint in PropOS (Settings → Webhooks):', 'propos-sync'); ?>
            <br>
            <code><?php echo esc_url(rest_url('propos-sync/v1/webhook')); ?></code>
        </p>
    </div>
    <?php
}

// ── REST webhook endpoint ─────────────────────────────────────────────────────

add_action('rest_api_init', function () {
    register_rest_route('propos-sync/v1', '/webhook', [
        'methods'             => 'POST',
        'callback'            => 'propos_handle_webhook',
        'permission_callback' => '__return_true',
    ]);
});

function propos_handle_webhook(\WP_REST_Request $request): \WP_REST_Response {
    $secret    = get_option('propos_webhook_secret', '');
    $body      = $request->get_body();
    $signature = $request->get_header('x-propos-signature-256');

    if ($secret && $signature) {
        $expected = 'sha256=' . hash_hmac('sha256', $body, $secret);
        if (!hash_equals($expected, $signature)) {
            return new \WP_REST_Response(['error' => 'Invalid signature.'], 401);
        }
    }

    $payload = json_decode($body, true);
    $event   = $payload['event'] ?? '';

    if (in_array($event, ['listing.published', 'listing.updated', 'listing.price_reduced'], true)) {
        propos_do_sync();
    } elseif ($event === 'listing.deleted') {
        $listingId = $payload['listing_id'] ?? 0;
        propos_unpublish_listing($listingId);
    }

    return new \WP_REST_Response(['received' => true], 200);
}

// ── Sync logic ────────────────────────────────────────────────────────────────

function propos_do_sync(): int|false {
    $apiKey = get_option(PROPOS_OPTION_KEY, '');
    if (!$apiKey) return false;

    $page  = 1;
    $total = 0;

    do {
        $response = wp_remote_get(PROPOS_API_BASE . '/listings?' . http_build_query([
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
            propos_set_sync_status('error', 0);
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            propos_set_sync_status('error', 0);
            return false;
        }

        $data     = json_decode(wp_remote_retrieve_body($response), true);
        $listings = $data['data'] ?? [];
        $lastPage = $data['meta']['last_page'] ?? 1;

        foreach ($listings as $listing) {
            propos_upsert_listing($listing);
            $total++;
        }

        $page++;
    } while ($page <= $lastPage);

    propos_set_sync_status('ok', $total);
    return $total;
}

function propos_upsert_listing(array $l): void {
    $propos_id = (int) ($l['id'] ?? 0);
    if (!$propos_id) return;

    // Check if post already exists
    $existing = get_posts([
        'post_type'      => PROPOS_CPT,
        'meta_key'       => '_propos_listing_id',
        'meta_value'     => $propos_id,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    $city    = $l['property']['city']  ?? '';
    $address = $l['property']['address'] ?? '';
    $price   = number_format((float) ($l['listing_price'] ?? 0), 0);

    $postData = [
        'post_type'    => PROPOS_CPT,
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
        '_propos_listing_id'   => $propos_id,
        '_propos_price'        => $l['listing_price']  ?? '',
        '_propos_status'       => $l['status']         ?? '',
        '_propos_mandate_type' => $l['mandate_type']   ?? '',
        '_propos_city'         => $city,
        '_propos_address'      => $address,
        '_propos_bedrooms'     => $l['property']['bedrooms']    ?? '',
        '_propos_bathrooms'    => $l['property']['bathrooms']   ?? '',
        '_propos_floor_area'   => $l['property']['floor_area_sqm'] ?? '',
        '_propos_cover_photo'  => $l['cover_photo']    ?? '',
        '_propos_agent_name'   => $l['agent']['name']  ?? '',
        '_propos_agent_phone'  => $l['agent']['phone'] ?? '',
        '_propos_agent_email'  => $l['agent']['email'] ?? '',
        '_propos_days_on_market' => $l['days_on_market'] ?? '',
        '_propos_features'     => maybe_serialize($l['features'] ?? []),
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
        propos_sideload_image($l['cover_photo'], $postId);
    }
}

function propos_unpublish_listing(int $propos_id): void {
    $posts = get_posts([
        'post_type'      => PROPOS_CPT,
        'meta_key'       => '_propos_listing_id',
        'meta_value'     => $propos_id,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    foreach ($posts as $id) {
        wp_update_post(['ID' => $id, 'post_status' => 'draft']);
        update_post_meta($id, '_propos_status', 'archived');
    }
}

function propos_sideload_image(string $url, int $postId): void {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $attachment_id = media_sideload_image($url, $postId, null, 'id');
    if (!is_wp_error($attachment_id)) {
        set_post_thumbnail($postId, $attachment_id);
    }
}

function propos_set_sync_status(string $status, int $count): void {
    update_option('propos_last_sync',        current_time('mysql'));
    update_option('propos_last_sync_status', $status);
    update_option('propos_last_sync_count',  $count);
}

// ── Template loader ───────────────────────────────────────────────────────────

add_filter('template_include', 'propos_template_loader');

function propos_template_loader(string $template): string {
    // Only override if the theme has NOT provided its own template
    if (is_singular(PROPOS_CPT)) {
        $theme_file = locate_template(['single-' . PROPOS_CPT . '.php', 'single.php']);
        if (!$theme_file || basename($theme_file) === 'single.php') {
            $plugin_tpl = PROPOS_PLUGIN_DIR . 'templates/single-propos_listing.php';
            if (file_exists($plugin_tpl)) return $plugin_tpl;
        }
    }

    if (is_post_type_archive(PROPOS_CPT) || is_tax(['listing_type', 'listing_location', 'property_features'])) {
        $theme_file = locate_template(['archive-' . PROPOS_CPT . '.php', 'archive.php']);
        if (!$theme_file || basename($theme_file) === 'archive.php') {
            $plugin_tpl = PROPOS_PLUGIN_DIR . 'templates/archive-propos_listing.php';
            if (file_exists($plugin_tpl)) return $plugin_tpl;
        }
    }

    return $template;
}

// ── Shortcodes ────────────────────────────────────────────────────────────────

add_shortcode('propos_listings',   'propos_shortcode_listings');
add_shortcode('propos_listing',    'propos_shortcode_single');
add_shortcode('propos_inquiry',    'propos_shortcode_inquiry');

/**
 * [propos_listings limit="6" mandate="sale" city="Lagos" columns="3"]
 */
function propos_shortcode_listings(array $atts): string {
    $atts = shortcode_atts([
        'limit'   => 6,
        'mandate' => '', // sale | rental | ''
        'city'    => '',
        'columns' => 3,
        'orderby' => 'date',
        'order'   => 'DESC',
    ], $atts, 'propos_listings');

    $args = [
        'post_type'      => PROPOS_CPT,
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
        return '<p class="propos-no-listings">' . esc_html__('No listings found.', 'propos-sync') . '</p>';
    }

    $cols = max(1, min(4, intval($atts['columns'])));
    ob_start();
    ?>
    <div class="propos-shortcode-grid" style="display:grid;grid-template-columns:repeat(<?php echo $cols; ?>,1fr);gap:20px;">
    <?php while ($query->have_posts()) : $query->the_post();
        $listing = propos_get_listing_data(); ?>
        <div class="propos-listing-card" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
            <a href="<?php the_permalink(); ?>" style="text-decoration:none;color:inherit;">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('medium', ['style' => 'width:100%;height:200px;object-fit:cover;display:block;']); ?>
                <?php endif; ?>
                <div style="padding:14px;">
                    <div style="font-weight:700;font-size:18px;color:#1e40af;margin-bottom:4px;">
                        <?php propos_the_price(); ?>
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
 * [propos_listing id="42"]  — renders a single listing card inline.
 */
function propos_shortcode_single(array $atts): string {
    $atts = shortcode_atts(['id' => 0], $atts, 'propos_listing');
    $id   = intval($atts['id']);
    if (!$id) return '';

    $post = get_post($id);
    if (!$post || $post->post_type !== PROPOS_CPT) return '';

    $listing = propos_get_listing_data($id);
    ob_start();
    ?>
    <div class="propos-listing-card" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;max-width:400px;">
        <a href="<?php echo esc_url(get_permalink($id)); ?>" style="text-decoration:none;color:inherit;">
            <?php if (has_post_thumbnail($id)) : echo get_the_post_thumbnail($id, 'medium', ['style' => 'width:100%;height:220px;object-fit:cover;display:block;']); endif; ?>
            <div style="padding:16px;">
                <div style="font-weight:700;font-size:20px;color:#1e40af;margin-bottom:4px;"><?php propos_the_price($id); ?></div>
                <h3 style="font-size:15px;margin:0 0 6px;"><?php echo esc_html(get_the_title($id)); ?></h3>
                <p style="font-size:13px;color:#6b7280;margin:0;"><?php echo esc_html(implode(', ', array_filter([$listing['address'], $listing['city']]))); ?></p>
            </div>
        </a>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * [propos_inquiry listing_id="42" button_label="Enquire Now"]
 */
function propos_shortcode_inquiry(array $atts): string {
    $atts = shortcode_atts([
        'listing_id'   => 0,
        'button_label' => __('Send Enquiry', 'propos-sync'),
        'primary_color'=> '#1E40AF',
    ], $atts, 'propos_inquiry');

    $api_key = get_option(PROPOS_OPTION_KEY, '');
    if (!$api_key) return '';

    return sprintf(
        '<script src="https://cdn.propos.app/widgets.js" defer></script>
         <propos-inquiry-form agency-key="%s" listing-id="%d" primary-color="%s"></propos-inquiry-form>',
        esc_attr($api_key),
        intval($atts['listing_id']),
        esc_attr($atts['primary_color']),
    );
}

// ── Frontend assets ───────────────────────────────────────────────────────────

function propos_enqueue_assets(): void {
    if (!is_singular(PROPOS_CPT) && !is_post_type_archive(PROPOS_CPT)
        && !is_tax(['listing_type', 'listing_location', 'property_features'])) return;

    wp_enqueue_style(
        'propos-sync',
        PROPOS_PLUGIN_URL . 'assets/propos-sync.css',
        [],
        '1.0.0',
    );
}

// ── Template functions (for theme developers) ─────────────────────────────────

/**
 * Display listing price formatted nicely.
 * Usage in template: <?php propos_the_price(); ?>
 */
function propos_the_price(int $postId = 0): void {
    $postId = $postId ?: get_the_ID();
    $price  = get_post_meta($postId, '_propos_price', true);
    echo '<span class="propos-price">' . esc_html(number_format((float) $price, 0)) . '</span>';
}

/**
 * Get all listing meta as an associative array.
 */
function propos_get_listing_data(int $postId = 0): array {
    $postId = $postId ?: get_the_ID();
    return [
        'id'            => (int) get_post_meta($postId, '_propos_listing_id', true),
        'price'         => (float) get_post_meta($postId, '_propos_price', true),
        'status'        => get_post_meta($postId, '_propos_status', true),
        'mandate_type'  => get_post_meta($postId, '_propos_mandate_type', true),
        'city'          => get_post_meta($postId, '_propos_city', true),
        'address'       => get_post_meta($postId, '_propos_address', true),
        'bedrooms'      => (int) get_post_meta($postId, '_propos_bedrooms', true),
        'bathrooms'     => (int) get_post_meta($postId, '_propos_bathrooms', true),
        'floor_area'    => (float) get_post_meta($postId, '_propos_floor_area', true),
        'cover_photo'   => get_post_meta($postId, '_propos_cover_photo', true),
        'agent_name'    => get_post_meta($postId, '_propos_agent_name', true),
        'agent_phone'   => get_post_meta($postId, '_propos_agent_phone', true),
        'agent_email'   => get_post_meta($postId, '_propos_agent_email', true),
        'days_on_market'=> (int) get_post_meta($postId, '_propos_days_on_market', true),
        'features'      => maybe_unserialize(get_post_meta($postId, '_propos_features', true)) ?: [],
    ];
}
