<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\ContextualLinkInterface.
 */

namespace Drupal\Core\Menu;

/**
 * Defines a contextual link plugin.
 *
 * Contextual links by default are in the module_name.links.contextual.yml
 * file. These YAML files contain a list of contextual link plugin definitions,
 * keyed by the plugin ID. Each definition must define a route_name and a group
 * and might define title, options, and weight. See the getter methods on this
 * interface for an explanation of each.
 *
 * @ingroup menu
 */
interface ContextualLinkInterface {

  /**
   * Returns the localized title to be shown for this contextual link.
   *
   * Subclasses may add optional arguments like NodeInterface $node = NULL that
   * will be supplied by the ControllerResolver.
   *
   * @return string
   *   The title to be shown for this action.
   *
   * @see \Drupal\Core\Menu\ContextualLinksManager::getTitle()
   */
  public function getTitle();

  /**
   * Returns the route name of the contextual link.
   *
   * @return string
   *   The name of the route this contextual link links to.
   */
  public function getRouteName();

  /**
   * Returns the group this contextual link should be rendered in.
   *
   * A contextual link group is a set of contextual links that are displayed
   * together on a certain page. For example, the 'block' group displays all
   * links related to the block, such as the block instance edit link as well as
   * the views edit link, if it is a view block.
   *
   * @return string
   *   The contextual links group name.
   */
  public function getGroup();

  /**
   * Returns the link options passed to the link generator.
   *
   * @return array
   *   An associative array of options.
   */
  public function getOptions();

  /**
   * Returns the weight of the contextual link.
   *
   * The contextual links in one group are sorted by weight for display.
   *
   * @return int
   *   The weight as positive/negative integer.
   */
  public function getWeight();

}
