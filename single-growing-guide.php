<?php

/**
 * The template for displaying all single posts.
 *
 * @package storefront
 */

get_header(); ?>


<div id="primary" class="content-area">
    <main id="main" class="site-main growing-guide" role="main">

        <article>
            <?php
            while (have_posts()) :
                // echo "<code>the_post</code><br/>";
                the_post();

                // echo "<code>storefront_single_post_before</code><br/>";
                do_action('storefront_single_post_before');

                // echo "<code>content</code><br/>";
                remove_action('storefront_single_post_bottom', 'storefront_edit_post_link', 5);
                remove_action('storefront_single_post', 'storefront_post_content', 30);
                remove_action('storefront_single_post_bottom', 'storefront_post_taxonomy', 5);
                // get_template_part('content', 'single');
                the_title('<h1 class="entry-title">', '</h1>');

                get_template_part('parts/growingguide', 'sections', ['show_images' => true]);
                // echo "<code>storefront_single_post_after</code><br/>";
                do_action('storefront_single_post_after');

            endwhile; // End of the loop.
            ?>
        </article>

        <?php

        $category = function_exists('get_field') ? get_field('product_category') : null;

        if ($category && $category instanceof WP_Term) {
            echo "<h2>" . $category->name . "</h2>";
            $category_id = $category->term_id;
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $category_id,
                    ),
                ),
            );
            $products = new WP_Query($args);
            if ($products->have_posts()) {
                wc_set_loop_prop('columns', 4);
                woocommerce_product_loop_start();
                while ($products->have_posts()) {
                    $products->the_post();
                    wc_get_template_part('content', 'product');
                }
                woocommerce_product_loop_end();
                wp_reset_postdata();
            }
        }
        ?>
    </main><!-- #main -->
</div><!-- #primary -->

<?php
do_action('storefront_sidebar');
get_footer();
