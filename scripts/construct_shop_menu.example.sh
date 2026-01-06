# This needs to be copied on the prod server to add the wp path, eg:
# wp --path=/www/sitedir/public wp vitalseedstore menu construct_shop_menus

# Example: moving categories around
# OLD_PARENT_ID=$(wp term get product_cat herb-seeds --by=slug --field=term_id) && \
# NEW_PARENT_ID=$(wp term create product_cat "Culinary Herbs" --parent=$OLD_PARENT_ID --slug=culinary-herbs --porcelain) && \
# wp term list product_cat --parent=$OLD_PARENT_ID --field=term_id | xargs -n1 wp term update product_cat --parent=$NEW_PARENT_ID
# wp term update product_cat culinary-herbs --by=slug --parent=$OLD_PARENT_ID
# wp term update product_cat medicinal-herbs --by=slug --parent=$OLD_PARENT_ID

# Hardcoded for simplicity but left forreference
MENU_NAME=megamenu_full
PARENT_MENU_ITEM=Shop

wp vitalseedstore menu construct_shop_menus