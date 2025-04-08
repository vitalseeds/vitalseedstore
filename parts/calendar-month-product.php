<?php
$product_ids = $args["product_ids"] ?? false;

if ($product_ids) {
    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        $image  = $product->get_image('thumbnail');
        if (!$image) {
            continue;
        }
        echo $image;
        echo $product->get_name();
        continue;

        // Template more specifically
        if ($product_images) {
            echo '<div class="sow-by-month">';
            echo '<ul class="sow-by-month-list">';
            foreach ($product_images as $image) {
                echo '<li class="sow-by-month-item">';
                $url = $image['url'];
                $alt = $image['alt'];
                $img_src = wp_get_attachment_image_url($image_id, 'medium');
                $img_srcset = wp_get_attachment_image_srcset($image_id, 'medium');

                ?><img src="<?php echo esc_url($img_src);?>" srcset="<?php echo esc_attr($img_srcset); ?>" sizes="(max-width: 768px) 90vw, 20vw"><?php
                
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }
}
