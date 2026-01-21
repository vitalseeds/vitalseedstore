<?php
/**
 * Template for the /search page
 *
 * Shows a search form at the top and WooCommerce product results below.
 * Form submits back to /search with query parameters.
 *
 * @package VitalSeedStore
 */

// Enqueue search styles (in case nav menu isn't present)
wp_enqueue_style(
    'vs-product-search',
    get_stylesheet_directory_uri() . '/styles/product-search.css',
    [],
    wp_get_theme()->get('Version')
);

$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <header class="entry-header">
            <h1 class="entry-title"><?php esc_html_e('Search Products', 'vitalseedstore'); ?></h1>
        </header>

        <div class="vs-search-form-wrapper">
            <form role="search" method="get" class="vs-product-search-form" action="<?php echo esc_url(home_url('/search')); ?>">
                <label for="vs-search-input" class="screen-reader-text"><?php esc_html_e('Search for:', 'vitalseedstore'); ?></label>
                <input type="search"
                       id="vs-search-input"
                       class="vs-search-input"
                       placeholder="<?php esc_attr_e('Search products...', 'vitalseedstore'); ?>"
                       value="<?php echo esc_attr($search_query); ?>"
                       name="s"
                       autofocus>
                <button type="submit" class="vs-search-submit button">
                    <?php esc_html_e('Search', 'vitalseedstore'); ?>
                </button>
            </form>
        </div>

        <?php if ($search_query) : ?>
            <?php
            $paged = get_query_var('paged') ? get_query_var('paged') : 1;

            $args = [
                'post_type'      => 'product',
                'post_status'    => 'publish',
                's'              => $search_query,
                'posts_per_page' => wc_get_default_products_per_row() * wc_get_default_product_rows_per_page(),
                'paged'          => $paged,
            ];

            $products = new WP_Query($args);
            ?>

            <div class="vs-search-results">
                <?php if ($products->have_posts()) : ?>
                    <p class="woocommerce-result-count">
                        <?php
                        printf(
                            _n(
                                '%d result found for "%s"',
                                '%d results found for "%s"',
                                $products->found_posts,
                                'vitalseedstore'
                            ),
                            $products->found_posts,
                            esc_html($search_query)
                        );
                        ?>
                    </p>

                    <?php woocommerce_product_loop_start(); ?>

                    <?php while ($products->have_posts()) : $products->the_post(); ?>
                        <?php wc_get_template_part('content', 'product'); ?>
                    <?php endwhile; ?>

                    <?php woocommerce_product_loop_end(); ?>

                    <?php if ($products->max_num_pages > 1) : ?>
                        <nav class="woocommerce-pagination">
                            <?php
                            echo paginate_links([
                                'base'      => esc_url(home_url('/search')) . '?s=' . urlencode($search_query) . '%_%',
                                'format'    => '&paged=%#%',
                                'current'   => $paged,
                                'total'     => $products->max_num_pages,
                                'prev_text' => '&larr;',
                                'next_text' => '&rarr;',
                                'type'      => 'list',
                            ]);
                            ?>
                        </nav>
                    <?php endif; ?>

                <?php else : ?>
                    <p class="woocommerce-info">
                        <?php esc_html_e('No products found matching your search.', 'vitalseedstore'); ?>
                    </p>
                <?php endif; ?>

                <?php wp_reset_postdata(); ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php
get_sidebar();
get_footer();
