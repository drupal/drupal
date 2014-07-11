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
 * hook_ENTITY_TYPE_presave() for entity 'menu_link'.
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
 * @} End of "addtogroup hooks".
 */
