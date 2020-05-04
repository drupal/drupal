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
 */
function menu_list_system_menus() {
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
