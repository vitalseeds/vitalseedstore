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

                get_template_part('parts/growersguide', 'sections');
                // echo "<code>storefront_single_post_after</code><br/>";
                do_action('storefront_single_post_after');

            endwhile; // End of the loop.
            ?>
        </article>
    </main><!-- #main -->
</div><!-- #primary -->

<?php
do_action('storefront_sidebar');
get_footer();
