<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuParentFormSelectorInterface.
 */

namespace Drupal\Core\Menu;

/**
 * Defines an interface for menu selector form elements and menu link options.
 */
interface MenuParentFormSelectorInterface {

  /**
   * Gets the options for a select element to choose a menu and parent.
   *
   * @param string $id
   *   Optional ID of a link plugin. This will exclude the link and its
   *   children from the select options.
   * @param array $menus
   *   Optional array of menu names as keys and titles as values to limit
   *   the select options.  If NULL, all menus will be included.
   *
   * @return array
   *   Keyed array where the keys are contain a menu name and parent ID and
   *   the values are a menu name or link title indented by depth.
   */
  public function getParentSelectOptions($id = '', array $menus = NULL);

  /**
   * Gets a form element to choose a menu and parent.
   *
   * The specific type of form element will vary depending on the
   * implementation, but callers will normally need to set the #title for the
   * element.
   *
   * @param string $menu_parent
   *   A menu name and parent ID concatenated with a ':' character to use as the
   *   default value.
   * @param string $id
   *   (optional) ID of a link plugin. This will exclude the link and its
   *   children from being selected.
   * @param array $menus
   *   (optional) array of menu names as keys and titles as values to limit
   *   the values that may be selected. If NULL, all menus will be included.
   *
   * @return array
   *   A form element to choose a parent, or an empty array if no possible
   *   parents exist for the given parameters. The resulting form value will be
   *   a single string containing the chosen menu name and parent ID separated
   *   by a ':' character.
   */
  public function parentSelectElement($menu_parent, $id = '', array $menus = NULL);

}
