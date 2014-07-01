<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLinkInterface.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;

/**
 * Default object used for LocalTaskPlugins.
 */
interface MenuLinkInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Return a localized link render array.
   *
   * @param bool $title_attribute
   *   If TRUE, add the link description (if present) as the title attribute.
   *
   * @return array
   *   A render array with localized or translated strings.
   */
  public function build($title_attribute = TRUE);

  /**
   * Returns the weight of the menu link.
   *
   * @return int
   *   The weight of the menu link, 0 by default.
   */
  public function getWeight();

  /**
   * Returns the localized title to be shown for this link.
   *
   * @return string
   *   The title of the menu link.
   */
  public function getTitle();

  /**
   * Returns the description of the menu link.
   */
  public function getDescription();

  /**
   * Returns the menu name of the menu link.
   */
  public function getMenuName();

  /**
   * Returns the provider (module name) of the menu link.
   */
  public function getProvider();

  /**
   * Returns the plugin ID of the menu link's parent, or an empty string.
   */
  public function getParent();

  /**
   * Returns whether the menu link is hidden.
   *
   * @return bool
   *   TRUE for hidden, FALSE otherwise.
   */
  public function isHidden();

  /**
   * Returns whether the child menu links should always been shown.
   *
   * @return bool
   *   TRUE for expanded, FALSE otherwise.
   */
  public function isExpanded();

  /**
   * Returns whether this link was discovered.
   *
   * @return bool
   *    TRUE if it is a discovered link, FALSE otherwise.
   */
  public function isDiscovered();

  /**
   * Returns whether this link can be reset.
   *
   * In general, only links that store overrides using the
   * menu_link.static.overrides service should return TRUE for this method.
   *
   * @return bool
   *   TRUE if it can be reset, FALSE otherwise.
   */
  public function isResetable();

  /**
   * Returns whether this link can be translated.
   *
   * @return bool
   *   TRUE if the link can be translated, FALSE otherwise.
   */
  public function isTranslatable();

  /**
   * Returns whether this link can be deleted.
   *
   * @return bool
   *   TRUE if the link can be deleted, FALSE otherwise.
   */
  public function isDeletable();

  /**
   * Returns a URL object containing either the external path or route.
   *
   * @param bool $title_attribute
   *   If TRUE, add the link description (if present) as the title attribute.
   *
   * @return \Drupal\Core\Url
   *   A a URL object containing either the external path or route.
   */
  public function getUrlObject($title_attribute = TRUE);

  /**
   * Returns the options for this link.
   *
   * @return array
   *   The options for the menu link.
   */
  public function getOptions();

  /**
   * Returns any metadata for this link.
   *
   * @return array
   *   The metadata for the menu link.
   */
  public function getMetaData();

  /**
   * Returns whether the rendered link can be cached.
   *
   * The plugin class may make some or all of the data used in the Url object
   * and build array dynamic.  For example, it could include the current
   * user name in the title, the current time in the description, or a
   * destination query string.  In addition the route parameters may
   * be dynamic so an access check should be performed for each user.
   *
   * @return bool
   *   TRUE if the link can be cached, FALSE otherwise.
   */
  public function isCacheable();

  /**
   * Updates and saves values for a menu link.
   *
   * The override is written depending on the implementation.
   * Static links, for example, have a dedicated override storage service.
   *
   * Depending on the implementation details of the class, not all definition
   * values may be changed. For example, changes to the title of a static
   * link will be discarded.
   *
   * In general, this method should not be called directly, but will be called
   * automatically from MenuLinkTreeInterface::updateLink()
   *
   * @param array $new_definition_values
   *   The new values for the link definition. This will usually be just a
   *   subset of the plugin definition.
   * @param bool $persist
   *   TRUE to have the link persist the changed values to any additional
   *   storage.
   *
   * @return array
   *   The plugin definition incorporating any allowed changes.
   */
  public function updateLink(array $new_definition_values, $persist);

  /**
   * Delete a menu link.
   *
   * In general, this method should not be called directly, but will be called
   * automatically from MenuLinkTreeInterface::deleteLink()
   *
   * This method will only delete the link from any additional storage, but
   * not from the menu.link_tree service.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the link is not deletable.
   */
  public function deleteLink();

  /**
   * Returns the name of a class that can build an editing form for this link.
   *
   * To instantiate the form class, use an instance of the
   * \Drupal\Core\DependencyInjection\ClassResolverInterface, such as from the
   * class_resolver service. Then call the setMenuLinkInstance() method
   * on the form instance with the menu link plugin instance.
   *
   * @return string
   *   A class that implements \Drupal\Core\Menu\Form\MenuLinkFormInterface.
   */
  public function getFormClass();

  /**
   * Returns parameters for a delete link, or an empty value.
   *
   * @return array
   *   Array with keys route_name and route_parameters
   */
  public function getDeleteRoute();

  /**
   * Returns parameters for a custom edit link, or an empty value.
   *
   * Plugins should return a value here if they have a special edit form,
   * or if they need to define additional local tasks, local actions, etc.
   * that are visible from the edit form.
   *
   * @return array
   *   Array with keys route_name and route_parameters
   */
  public function getEditRoute();

  /**
   * Returns parameters for a translate link, or an empty value.
   *
   * @return array
   *   Array with keys route_name and route_parameters
   */
  public function getTranslateRoute();

}
