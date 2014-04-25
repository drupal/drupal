<?php

/**
 * @file
 * Hooks provided by the Menu UI module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Respond to a custom menu creation.
 *
 * This hook is used to notify modules that a custom menu has been created.
 * Contributed modules may use the information to perform actions based on the
 * information entered into the menu system.
 *
 * @param \Drupal\system\MenuInterface $menu
 *   The menu entity that was created.
 *
 * @see hook_menu_update()
 * @see hook_menu_delete()
 */
function hook_menu_insert(\Drupal\system\MenuInterface $menu) {
  drupal_set_message(t('You have just created a menu with a machine name %id.', array(
    '%id' => $menu->id(),
  )));
}

/**
 * Respond to a custom menu update.
 *
 * This hook is used to notify modules that a custom menu has been updated.
 * Contributed modules may use the information to perform actions based on the
 * information entered into the menu system.
 *
 * @param \Drupal\system\MenuInterface $menu
 *   The menu entity that was updated.
 *
 * @see hook_menu_insert()
 * @see hook_menu_delete()
 */
function hook_menu_update(\Drupal\system\MenuInterface $menu) {
  if ($type->original->id() != $type->id()) {
    drupal_set_message(t('You have just changed the machine name of the menu %old_id to %id.', array(
      '%old_id' => $menu->original->id(),
      '%id' => $menu->id(),
    )));
  }
}

/**
 * Respond to a custom menu deletion.
 *
 * This hook is used to notify modules that a custom menu along with all links
 * contained in it (if any) has been deleted. Contributed modules may use the
 * information to perform actions based on the information entered into the menu
 * system.
 *
 * @param \Drupal\system\MenuInterface $menu
 *   The menu entity that was deleted.
 *
 * @see hook_menu_insert()
 * @see hook_menu_update()
 */
function hook_menu_delete(\Drupal\system\MenuInterface $menu) {
  drupal_set_message(t('You have just deleted the menu with machine name %id.', array(
    '%id' => $menu->id(),
  )));
}

/**
 * @} End of "addtogroup hooks".
 */
