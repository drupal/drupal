<?php

/**
 * @file
 * Hooks provided by the Menu link module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter a menu link after it has been translated and before it is rendered.
 *
 * This hook is invoked from _menu_link_translate() after a menu link has been
 * translated; i.e., after the user access to the link's target page has
 * been checked. It is only invoked if $menu_link['options']['alter'] has been
 * set to a non-empty value (e.g. TRUE). This flag should be set using
 * hook_menu_link_presave().
 *
 * Implementations of this hook are able to alter any property of the menu link.
 * For example, this hook may be used to add a page-specific query string to all
 * menu links, or hide a certain link by setting:
 * @code
 *   'hidden' => 1,
 * @endcode
 *
 * @param \Drupal\menu_link\Entity\MenuLink $menu_link
 *   A menu link entity.
 *
 * @see hook_menu_link_alter()
 */
function hook_translated_menu_link_alter(\Drupal\menu_link\Entity\MenuLink &$menu_link, $map) {
  if ($menu_link->href == 'devel/cache/clear') {
    $menu_link->localized_options['query'] = drupal_get_destination();
  }
}

/**
 * Alter menu links when loaded and before they are rendered.
 *
 * This hook is only invoked if $menu_link->options['alter'] has been set to a
 * non-empty value (e.g., TRUE). This flag should be set using
 * hook_menu_link_presave().
 * @ todo The paragraph above is lying! This hasn't been (re)implemented yet.
 *
 * Implementations of this hook are able to alter any property of the menu link.
 * For example, this hook may be used to add a page-specific query string to all
 * menu links, or hide a certain link by setting:
 * @code
 *   'hidden' => 1,
 * @endcode
 *
 * @param array $menu_links
 *   An array of menu link entities.
 *
 * @see hook_menu_link_presave()
 */
function hook_menu_link_load($menu_links) {
  foreach ($menu_links as $menu_link) {
    if ($menu_link->href == 'devel/cache/clear') {
      $menu_link->options['query'] = drupal_get_destination();
    }
  }
}


/**
 * Alter the data of a menu link entity before it is created or updated.
 *
 * @param \Drupal\menu_link\Entity\MenuLink $menu_link
 *   A menu link entity.
 *
 * @see hook_menu_link_load()
 */
function hook_menu_link_presave(\Drupal\menu_link\Entity\MenuLink $menu_link) {
  // Make all new admin links hidden (a.k.a disabled).
  if (strpos($menu_link->link_path, 'admin') === 0 && $menu_link->isNew()) {
    $menu_link->hidden = 1;
  }
  // Flag a link to be altered by hook_menu_link_load().
  if ($menu_link->link_path == 'devel/cache/clear') {
    $menu_link->options['alter'] = TRUE;
  }
  // Flag a menu link to be altered by hook_menu_link_load(), but only if it is
  // derived from a menu router item; i.e., do not alter a custom menu link
  // pointing to the same path that has been created by a user.
  if ($menu_link->link_path == 'user' && $menu_link->module == 'system') {
    $menu_link->options['alter'] = TRUE;
  }
}

/**
 * Inform modules that a menu link has been created.
 *
 * This hook is used to notify modules that menu links have been
 * created. Contributed modules may use the information to perform
 * actions based on the information entered into the menu system.
 *
 * @param \Drupal\menu_link\Entity\MenuLink $menu_link
 *   A menu link entity.
 *
 * @see hook_menu_link_presave()
 * @see hook_menu_link_update()
 * @see hook_menu_link_delete()
 */
function hook_menu_link_insert(\Drupal\menu_link\Entity\MenuLink $menu_link) {
  // In our sample case, we track menu items as editing sections
  // of the site. These are stored in our table as 'disabled' items.
  $record['mlid'] = $menu_link->id();
  $record['menu_name'] = $menu_link->menu_name;
  $record['status'] = 0;
  db_insert('menu_example')->fields($record)->execute();
}

/**
 * Inform modules that a menu link has been updated.
 *
 * This hook is used to notify modules that menu items have been
 * updated. Contributed modules may use the information to perform
 * actions based on the information entered into the menu system.
 *
 * @param \Drupal\menu_link\Entity\MenuLink $menu_link
 *   A menu link entity.
 *
 * @see hook_menu_link_presave()
 * @see hook_menu_link_insert()
 * @see hook_menu_link_delete()
 */
function hook_menu_link_update(\Drupal\menu_link\Entity\MenuLink $menu_link) {
  // If the parent menu has changed, update our record.
  $menu_name = db_query("SELECT menu_name FROM {menu_example} WHERE mlid = :mlid", array(':mlid' => $menu_link->id()))->fetchField();
  if ($menu_name != $menu_link->menu_name) {
    db_update('menu_example')
      ->fields(array('menu_name' => $menu_link->menu_name))
      ->condition('mlid', $menu_link->id())
      ->execute();
  }
}

/**
 * Inform modules that a menu link has been deleted.
 *
 * This hook is used to notify modules that menu links have been
 * deleted. Contributed modules may use the information to perform
 * actions based on the information entered into the menu system.
 *
 * @param \Drupal\menu_link\Entity\MenuLink $menu_link
 *   A menu link entity.
 *
 * @see hook_menu_link_presave()
 * @see hook_menu_link_insert()
 * @see hook_menu_link_update()
 */
function hook_menu_link_delete(\Drupal\menu_link\Entity\MenuLink $menu_link) {
  // Delete the record from our table.
  db_delete('menu_example')
    ->condition('mlid', $menu_link->id())
    ->execute();
}

/**
 * @} End of "addtogroup hooks".
 */
