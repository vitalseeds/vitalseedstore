<?php
// Set the growing guide id from args in case called from a category/product
// rather than the growing guide itself
$ggid = $args["growing_guide_id"] ?? false;

// Set the heading tag from args or default to h2 for the main growing guide
$htag = $args["heading_tag"] ?? (!$ggid ? 'h2' : 'h3');

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
            echo "<$htag id='sow'>Seed Sowing</$htag>";
            echo $seed_sowing;
        }
        if ($transplanting = get_field('transplanting', $ggid)) {
            echo "<$htag id='transplant'>Transplanting</$htag>";
            echo $transplanting;
        }
        if ($plant_care = get_field('plant_care', $ggid)) {
            echo "<$htag id='care'>Plant Care</$htag>";
            echo $plant_care;
        }
        if ($challenges = get_field('challenges', $ggid)) {
            echo "<$htag id='challenges'>Challenges</$htag>";
            echo $challenges;
        }
        if ($harvest = get_field('harvest', $ggid)) {
            echo "<$htag id='harvest'>Harvest</$htag>";
            echo $harvest;
        }
        if ($culinary_ideas = get_field('culinary_ideas', $ggid)) {
            echo "<$htag id='culinary'>Culinary Ideas</$htag>";
            echo $culinary_ideas;
        }
        if ($seed_saving = get_field('seed_saving', $ggid)) {
            echo "<$htag id='seeds'>Seed Saving</$htag>";
            echo $seed_saving;
        }
