<?php
// Set the growing guide id from args in case called from a category/product
// rather than the growing guide itself
$ggid = $args["growing_guide_id"] ?? false;

if ($hero = get_field('hero', $ggid)) {
?>
    <link rel="stylesheet" href="https://unpkg.com/flickity@2/dist/flickity.min.css">
    <script src="https://unpkg.com/flickity@2/dist/flickity.pkgd.min.js"></script>
    <div class="hero-image-row">
        <div class="fl-carousel js-flickity" data-flickity='{ "watchCSS": true }'>
            <?php
            foreach ($hero as $hero_image) {
                if ($hero_image) {
                    $img_src = wp_get_attachment_image_url($hero_image['id'], 'medium');
                    $img_srcset = wp_get_attachment_image_srcset($hero_image['id'], 'medium'); ?>
                    <div class="fl-carousel-cell"><img src="<?php echo esc_url($img_src); ?>"
                            srcset="<?php echo esc_attr($img_srcset); ?>" sizes="(max-width: 768px) 90vw, 20vw"></div>

        <?php
                }
            }
            echo '</div></div>';
        }

        if ($seed_sowing = get_field('seed_sowing', $ggid)) {
            echo "<h2 id='sow'>Seed Sowing</h2>";
            echo $seed_sowing;
        }
        if ($transplanting = get_field('transplanting', $ggid)) {
            echo "<h2 id='transplant'>Transplanting</h2>";
            echo $transplanting;
        }
        if ($plant_care = get_field('plant_care', $ggid)) {
            echo "<h2 id='care'>Plant Care</h2>";
            echo $plant_care;
        }
        if ($challenges = get_field('challenges', $ggid)) {
            echo "<h2 id='challenges'>Challenges</h2>";
            echo $challenges;
        }
        if ($harvest = get_field('harvest', $ggid)) {
            echo "<h2 id='harvest'>Harvest</h2>";
            echo $harvest;
        }
        if ($culinary_ideas = get_field('culinary_ideas', $ggid)) {
            echo "<h2 id='culinary'>Culinary Ideas</h2>";
            echo $culinary_ideas;
        }
        if ($seed_saving = get_field('seed_saving', $ggid)) {
            echo "<h2 id='seeds'>Seed Saving</h2>";
            echo $seed_saving;
        }
