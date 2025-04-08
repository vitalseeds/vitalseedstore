<?php
$categories = $args["categories"] ?? false;

if ($categories) {

    echo '<div class="sow-by-month vital-image-list">';
    echo '<ul class="sow-by-month-list">';

    foreach ($categories as $category) {

        $image_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
        $term_link = get_term_link( $category, 'product_cat' );

        // echo $image;
        // echo $category->name;
        // continue;

        if (!$image_id) {
            continue;
        }

        echo '<li class="sow-by-month-item">';

        $img_src = wp_get_attachment_image_url($image_id, 'medium');
        $img_srcset = wp_get_attachment_image_srcset($image_id, 'medium');
        echo '<a href="' .$term_link . '">';
        ?><img src="<?php echo esc_url($img_src);?>" srcset="<?php echo esc_attr($img_srcset); ?>" sizes="(max-width: 768px) 90vw, 20vw"><?php
        echo '</a>';
        echo '<figcaption>' . $category->name . '</figcaption>';

        echo '</li>';
    }

    echo '</ul>';
    echo '</div>';
}
