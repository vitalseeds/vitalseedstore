<?php

/**
 * The template for displaying all single posts.
 *
 * @package storefront
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

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
            get_template_part('content', 'single');

            // if ($hero = get_field('hero')) {
            //     echo '<div class="hero-image-row">';
            //     foreach ($hero as $hero_image) {
            //         if ($hero_image) {
            //             echo get_image_tag($hero_image['id'], $hero_image['alt'], $hero_image['title'], 'left', 'large');
            //         }
            //     }
            //     echo '</div>';
            // }


            if ($seed_sowing = get_field('seed_sowing')) {
                echo "<h2 id='sow'>Seed Sowing</h2>";
                echo $seed_sowing;
            }
            if ($transplanting = get_field('transplanting')) {
                echo "<h2 id='transplant'>Transplanting</h2>";
                echo $transplanting;
            }
            if ($plant_care = get_field('plant_care')) {
                echo "<h2 id='care'>Plant Care</h2>";
                echo $plant_care;
            }
            if ($challenges = get_field('challenges')) {
                echo "<h2 id='challenges'>Challenges</h2>";
                echo $challenges;
            }
            if ($harvest = get_field('harvest')) {
                echo "<h2 id='harvest'>Harvest</h2>";
                echo $harvest;
            }
            if ($culinary_ideas = get_field('culinary_ideas')) {
                echo "<h2 id='culinary'>Culinary Ideas</h2>";
                echo $culinary_ideas;
            }
            if ($seed_saving = get_field('seed_saving')) {
                echo "<h2 id='seeds'>Seed Saving</h2>";
                echo $seed_saving;
            }

            // echo "<code>storefront_single_post_after</code><br/>";
            do_action('storefront_single_post_after');

        endwhile; // End of the loop.
        ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php
do_action('storefront_sidebar');
get_footer();
