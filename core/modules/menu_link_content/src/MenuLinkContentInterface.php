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
   * Gets the route name of the menu link.
   *
   * @return string|NULL
   *   Returns the route name, or NULL if it is an external link.
   */
  public function getRouteName();

  /**
   * Gets the route parameters of the menu link content entity.
   *
   * @return array
   *   The route parameters, or an empty array.
   */
  public function getRouteParameters();

  /**
   * Sets the route parameters of the custom menu link.
   *
   * @param array $route_parameters
   *   The route parameters, usually derived from the path entered by the
   *   administrator. For example, for a link to a node with route
   *   'entity.node.canonical' the route needs the node ID as a parameter:
   *   @code
   *     array('node' => 2)
   *   @endcode
   *
   * @return $this
   */
  public function setRouteParameters(array $route_parameters);

  /**
   * Gets the external URL.
   *
   * @return string|NULL
   *   Returns the external URL if the menu link points to an external URL,
   *   otherwise NULL.
   */
  public function getUrl();

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
   * Gets the options for the menu link content entity.
   *
   * @return array
   *   The options that may be passed to the URL generator.
   */
  public function getOptions();

  /**
   * Sets the query options of the menu link content entity.
   *
   * @param array $options
   *   The new option.
   *
   * @return $this
   */
  public function setOptions(array $options);

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

}
