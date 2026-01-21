<?php
/**
 * Product Search - FlexSearch JSON Export and Shortcode
 *
 * Provides client-side product search using FlexSearch with IndexedDB caching.
 * Searches both products and categories.
 *
 * @package VitalSeedStore
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cron hook name
define('VS_PRODUCT_SEARCH_CRON_HOOK', 'vs_product_search_export');

/**
 * Schedule cron on theme activation
 */
function vs_product_search_schedule_cron() {
    if (!wp_next_scheduled(VS_PRODUCT_SEARCH_CRON_HOOK)) {
        wp_schedule_event(strtotime('today 3:00am'), 'daily', VS_PRODUCT_SEARCH_CRON_HOOK);
    }
}
add_action('after_setup_theme', 'vs_product_search_schedule_cron');

/**
 * Clear cron on theme switch
 */
function vs_product_search_clear_cron() {
    wp_clear_scheduled_hook(VS_PRODUCT_SEARCH_CRON_HOOK);
}
add_action('switch_theme', 'vs_product_search_clear_cron');

/**
 * Export products and categories to JSON file
 *
 * @return int Version timestamp
 */
function vs_export_products_json() {
    $upload_dir = wp_upload_dir();
    $base_path = $upload_dir['basedir'];

    $version = time();

    $products = vs_get_products_for_search();
    $categories = vs_get_categories_for_search();

    $items = array_merge($products, $categories);

    $data = [
        'version' => $version,
        'generated' => date('c'),
        'items' => $items
    ];

    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    file_put_contents($base_path . '/search-index.json', $json);

    update_option('vs_product_search_version', $version);

    return $version;
}
add_action(VS_PRODUCT_SEARCH_CRON_HOOK, 'vs_export_products_json');

/**
 * Get all published products formatted for search
 *
 * @return array
 */
function vs_get_products_for_search() {
    $products = [];

    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ];

    $product_ids = get_posts($args);

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product) continue;

        if ($product->get_catalog_visibility() === 'hidden') continue;

        $thumbnail_id = $product->get_image_id();
        $thumbnail_url = $thumbnail_id
            ? wp_get_attachment_image_url($thumbnail_id, 'woocommerce_thumbnail')
            : wc_placeholder_img_src('woocommerce_thumbnail');

        $terms = get_the_terms($product_id, 'product_cat');
        $categories = [];
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $categories[] = $term->name;
            }
        }

        $latin_name = get_field('latin_name', $product_id);

        $products[] = [
            'id' => 'product-' . $product_id,
            'type' => 'product',
            'title' => $product->get_name(),
            'latin_name' => $latin_name ?: null,
            'url' => get_permalink($product_id),
            'thumbnail' => $thumbnail_url,
            'category' => $categories,
            'sku' => $product->get_sku(),
        ];
    }

    return $products;
}

/**
 * Get seed categories formatted for search
 *
 * @return array
 */
function vs_get_categories_for_search() {
    $categories = [];

    $seeds_parent = get_term_by('slug', 'seeds', 'product_cat');
    if (!$seeds_parent) {
        return $categories;
    }

    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'child_of' => $seeds_parent->term_id,
        'hide_empty' => true,
    ]);

    if (is_wp_error($terms)) {
        return $categories;
    }

    foreach ($terms as $term) {
        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
        $thumbnail_url = $thumbnail_id
            ? wp_get_attachment_image_url($thumbnail_id, 'woocommerce_thumbnail')
            : wc_placeholder_img_src('woocommerce_thumbnail');

        $categories[] = [
            'id' => 'category-' . $term->term_id,
            'type' => 'category',
            'title' => $term->name,
            'url' => get_term_link($term),
            'thumbnail' => $thumbnail_url,
            'count' => $term->count,
        ];
    }

    return $categories;
}

/**
 * Register the [vs_product_search] shortcode
 *
 * Progressive enhancement: renders as a link to /search that works without JS.
 * JavaScript enhances the link to open the search popup.
 */
function vs_product_search_shortcode($atts) {
    $atts = shortcode_atts([
        'button_text' => __('Search', 'vitalseedstore'),
        'button_class' => 'vs-search-button',
        'placeholder' => __('Search', 'vitalseedstore'),
    ], $atts);

    vs_enqueue_product_search_assets();

    $version = get_option('vs_product_search_version', time());
    $search_url = home_url('/search');

    ob_start();
    ?>
    <a href="<?php echo esc_url($search_url); ?>"
       class="<?php echo esc_attr($atts['button_class']); ?>"
       data-vs-search-trigger
       data-vs-search-version="<?php echo esc_attr($version); ?>"
       data-vs-search-placeholder="<?php echo esc_attr($atts['placeholder']); ?>">
        <span class="screen-reader-text"><?php echo esc_html($atts['button_text']); ?></span>
        <svg width="20" height="20" class="search-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
    </a>
    <?php
    return ob_get_clean();
}
add_shortcode('vs_product_search', 'vs_product_search_shortcode');

/**
 * Add search link as last item in primary navigation menu
 *
 * Progressive enhancement: renders as a link to /search that works without JS.
 * JavaScript enhances the link to open the search popup.
 */
function vs_add_search_to_nav_menu($items, $args) {
    // Only add to primary menu
    if ($args->theme_location !== 'primary') {
        return $items;
    }

    vs_enqueue_product_search_assets();
    $version = get_option('vs_product_search_version', time());
    $search_url = home_url('/search');
    $placeholder = esc_attr__('Search', 'vitalseedstore');

    $search_item = '<li class="menu-item vs-search-menu-item">';
    $search_item .= '<a href="' . esc_url($search_url) . '" class="vs-search-button vs-search-button--header" data-vs-search-trigger data-vs-search-version="' . esc_attr($version) . '" data-vs-search-placeholder="' . $placeholder . '">';
    $search_item .= '<span class="screen-reader-text">' . esc_html__('Search', 'vitalseedstore') . '</span>';
    $search_item .= '<svg width="20" height="20" class="search-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>';
    $search_item .= '</a>';
    $search_item .= '</li>';

    return $items . $search_item;
}
add_filter('wp_nav_menu_items', 'vs_add_search_to_nav_menu', 10, 2);

/**
 * Enqueue search assets (called when shortcode or header search is used)
 */
function vs_enqueue_product_search_assets() {
    static $enqueued = false;
    if ($enqueued) return;
    $enqueued = true;

    $theme_version = wp_get_theme()->get('Version');

    wp_enqueue_script(
        'flexsearch',
        'https://cdn.jsdelivr.net/npm/flexsearch@0.7.31/dist/flexsearch.bundle.min.js',
        [],
        '0.7.31',
        true
    );

    wp_enqueue_script(
        'idb',
        'https://cdn.jsdelivr.net/npm/idb@7/build/umd.js',
        [],
        '7.1.1',
        true
    );

    wp_enqueue_script(
        'vs-product-search',
        get_stylesheet_directory_uri() . '/js/product-search.js',
        ['flexsearch', 'idb'],
        $theme_version,
        true
    );

    $upload_dir = wp_upload_dir();
    wp_localize_script('vs-product-search', 'vsProductSearch', [
        'jsonUrl' => $upload_dir['baseurl'] . '/search-index.json',
        'version' => get_option('vs_product_search_version', 0),
    ]);

    wp_enqueue_style(
        'vs-product-search',
        get_stylesheet_directory_uri() . '/styles/product-search.css',
        ['vitalseedstore-child-style'],
        $theme_version
    );
}

/**
 * Add admin menu for search index management
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Product Search Index',
        'Search Index',
        'manage_options',
        'vs-search-index',
        'vs_search_index_admin_page'
    );
});

/**
 * Admin page to manage search index
 */
function vs_search_index_admin_page() {
    $upload_dir = wp_upload_dir();
    $json_path = $upload_dir['basedir'] . '/search-index.json';
    $json_url = $upload_dir['baseurl'] . '/search-index.json';
    $json_exists = file_exists($json_path);
    $version = get_option('vs_product_search_version', 0);

    if (isset($_POST['vs_regenerate']) && check_admin_referer('vs_regenerate_index')) {
        $version = vs_export_products_json();
        echo '<div class="notice notice-success"><p>Search index regenerated! Version: ' . esc_html($version) . '</p></div>';
        $json_exists = true;
    }

    ?>
    <div class="wrap">
        <h1>Product Search Index</h1>

        <table class="form-table">
            <tr>
                <th>JSON File</th>
                <td>
                    <?php if ($json_exists): ?>
                        <span style="color: green;">&#10003; Exists</span>
                        (<a href="<?php echo esc_url($json_url); ?>" target="_blank">View JSON</a>)
                    <?php else: ?>
                        <span style="color: red;">&#10007; Not found</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Current Version</th>
                <td><?php echo $version ? date('Y-m-d H:i:s', $version) : 'Not set'; ?></td>
            </tr>
            <tr>
                <th>File Path</th>
                <td><code><?php echo esc_html($json_path); ?></code></td>
            </tr>
        </table>

        <form method="post">
            <?php wp_nonce_field('vs_regenerate_index'); ?>
            <p>
                <button type="submit" name="vs_regenerate" class="button button-primary">
                    Regenerate Search Index
                </button>
            </p>
        </form>

        <h2>Shortcode Usage</h2>
        <p><code>[vs_product_search]</code> - Adds a search button</p>
        <p>Optional attributes: <code>button_text="Search"</code>, <code>placeholder="Search"</code></p>
    </div>
    <?php
}

/**
 * Admin action to manually trigger export (legacy endpoint)
 */
add_action('admin_post_vs_export_products', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    check_admin_referer('vs_export_products');

    $version = vs_export_products_json();

    wp_redirect(add_query_arg([
        'vs_export' => 'success',
        'version' => $version
    ], admin_url()));
    exit;
});
