<?php
// Set the growing guide id from args in case called from a category/product
// rather than the growing guide itself
$ggid = $args["growing_guide_id"] ?? false;

// Set the heading tag from args or default to h2 for the main growing guide
$htag = $args["heading_tag"] ?? (!$ggid ? 'h2' : 'h3');
$show_images = $args["show_images"] ?? false;

if ($show_images){
    get_template_part('parts/growingguide', 'images', ['growing_guide_id' => $ggid]);
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
