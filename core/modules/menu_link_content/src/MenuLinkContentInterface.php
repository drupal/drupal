<?php

/**
 * @file
 * Contains \Drupal\menu_link_content\MenuLinkContentInterface.
 */

namespace Drupal\menu_link_content;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines an interface for custom menu links.
 */
interface MenuLinkContentInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Flags this instance as being wrapped in a menu link plugin instance.
   */
  public function setInsidePlugin();

  /**
   * Gets the title of the menu link.
   *
   * @return string
   *   The title of the link.
   */
  public function getTitle();

  /**
   * Gets the url object pointing to the URL of the menu link content entity.
   *
   * @return \Drupal\Core\Url
   *   A Url object instance.
   */
  public function getUrlObject();

  /**
   * Gets the menu name of the custom menu link.
   *
   * @return string
   *   The menu ID.
   */
  public function getMenuName();

  /**
   * Gets the description of the menu link for the UI.
   *
   * @return string
   *   The description to use on admin pages or as a title attribute.
   */
  public function getDescription();

  /**
   * Gets the menu plugin ID associated with this entity.
   *
   * @return string
   *   The plugin ID.
   */
  public function getPluginId();

  /**
   * Returns whether the menu link is marked as enabled.
   *
   * @return bool
   *   TRUE if is enabled, otherwise FALSE.
   */
  public function isEnabled();

  /**
   * Returns whether the menu link is marked as always expanded.
   *
   * @return bool
   *   TRUE for expanded, FALSE otherwise.
   */
  public function isExpanded();

  /**
   * Gets the plugin ID of the parent menu link.
   *
   * @return string
   *   A plugin ID, or empty string if this link is at the top level.
   */
  public function getParentId();

  /**
   * Returns the weight of the menu link content entity.
   *
   * @return int
   *   A weight for use when ordering links.
   */
  public function getWeight();

  /**
   * Builds up the menu link plugin definition for this entity.
   *
   * @return array
   *   The plugin definition corresponding to this entity.
   *
   * @see \Drupal\Core\Menu\MenuLinkTree::$defaults
   */
  public function getPluginDefinition();

}
