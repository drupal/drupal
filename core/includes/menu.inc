<?php

/**
 * @file
 * API for the Drupal menu system.
 */

/**
 * @addtogroup menu
 * @{
 */

/**
 * Returns an array containing the names of system-defined (default) menus.
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\system\Entity\Menu::loadMultiple() instead.
 *
 * @see https://www.drupal.org/node/3027453
 */
function menu_list_system_menus() {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\system\Entity\Menu::loadMultiple() instead. See https://www.drupal.org/node/3027453', E_USER_DEPRECATED);
  return [
    'tools' => 'Tools',
    'admin' => 'Administration',
    'account' => 'User account menu',
    'main' => 'Main navigation',
    'footer' => 'Footer menu',
  ];
}

/**
 * @} End of "addtogroup menu".
 */
