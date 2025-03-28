<link rel="stylesheet" href="https://unpkg.com/flickity@2/dist/flickity.min.css">
<script src="https://unpkg.com/flickity@2/dist/flickity.pkgd.min.js"></script>

<?php
$ggid = $args["growing_guide_id"] ?? false;

if ($images = get_field('images', $ggid)) {
    echo '<div class="growing-guide-images">';
    echo '<div class="hero-image-row">';
    echo "<div class=\"fl-carousel js-flickity\" data-flickity='{ \"watchCSS\": true }'>";
    foreach ($images as $image) {
        $url = $image['url'];
        $alt = $image['alt'];
        $img_src = wp_get_attachment_image_url($image['id'], 'medium');
        $img_srcset = wp_get_attachment_image_srcset($image['id'], 'medium');

        // echo "<img src='$url' alt='$alt' />";

        ?><div class="fl-carousel-cell"><img src="<?php echo esc_url($img_src);?>" srcset="<?php echo esc_attr($img_srcset); ?>" sizes="(max-width: 768px) 90vw, 20vw"></div><?php
        // echo "<img src='$url' alt='$alt' />";
    }
    echo '</div></div></div>';
}
