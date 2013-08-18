<?php

/**
 * @file
 * Hooks provided by the Menu module.
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
 * @param \Drupal\system\Entity\Menu $menu
 *   A menu entity.
 *
 * @see hook_menu_update()
 * @see hook_menu_delete()
 */
function hook_menu_insert($menu) {
  // For example, we track available menus in a variable.
  $my_menus = variable_get('my_module_menus', array());
  $my_menus[$menu->id()] = $menu->id();
  variable_set('my_module_menus', $my_menus);
}

/**
 * Respond to a custom menu update.
 *
 * This hook is used to notify modules that a custom menu has been updated.
 * Contributed modules may use the information to perform actions based on the
 * information entered into the menu system.
 *
 * @param \Drupal\system\Entity\Menu $menu
 *   A menu entity.
 *
 * @see hook_menu_insert()
 * @see hook_menu_delete()
 */
function hook_menu_update($menu) {
  // For example, we track available menus in a variable.
  $my_menus = variable_get('my_module_menus', array());
  $my_menus[$menu->id()] = $menu->id();
  variable_set('my_module_menus', $my_menus);
}

/**
 * Respond to a custom menu deletion.
 *
 * This hook is used to notify modules that a custom menu along with all links
 * contained in it (if any) has been deleted. Contributed modules may use the
 * information to perform actions based on the information entered into the menu
 * system.
 *
 * @param \Drupal\system\Entity\Menu $menu
 *   A menu entity.
 *
 * @see hook_menu_insert()
 * @see hook_menu_update()
 */
function hook_menu_delete($menu) {
  // Delete the record from our variable.
  $my_menus = variable_get('my_module_menus', array());
  unset($my_menus[$menu->id()]);
  variable_set('my_module_menus', $my_menus);
}

/**
 * @} End of "addtogroup hooks".
 */
