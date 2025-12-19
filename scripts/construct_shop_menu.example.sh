# This needs to be copied on the prod server to add the wp path, eg:
# wp --path=/www/sitedir/public vitalseedstore menu populate $MENU_NAME

# wp vitalseedstore menu clear megamenu_copy --yes

MENU_NAME=megamenu_full
PARENT_MENU_ITEM=Shop

wp vitalseedstore menu populate $MENU_NAME \
--parent-category=seeds \
--parent-menu-item=$PARENT_MENU_ITEM \
--clear-submenu \
--megamenu-display-mode=tabbed \
--megamenu-submenu-display-mode=grid4
wp vitalseedstore menu remove_suffix $MENU_NAME "$PARENT_MENU_ITEM" " Seeds" --case-insensitive --yes
wp vitalseedstore menu strip_categories $MENU_NAME "$PARENT_MENU_ITEM" --yes
