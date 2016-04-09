<?php

namespace Drupal\Core\Menu;

/**
 * Provides an object which returns the available contextual links.
 */
interface ContextualLinkManagerInterface {

  /**
   * Gets the contextual link plugins by contextual link group.
   *
   * @param string $group_name
   *   The group name.
   *
   * @return array
   *   A list of contextual links plugin definitions.
   */
  public function getContextualLinkPluginsByGroup($group_name);

  /**
   * Gets the contextual links prepared as expected by links.html.twig.
   *
   * @param string $group_name
   *   The group name.
   * @param array $route_parameters
   *   The incoming route parameters. The route parameters need to have the same
   *   name on all contextual link routes, e.g. you cannot use 'node' and
   *   'entity' in parallel.
   * @param array $metadata
   *   Additional metadata of contextual links, like the position (optional).
   *
   * @return array
   *   An array of link information, keyed by the plugin ID. Each entry is an
   *   associative array with the following keys:
   *     - route_name: The route name to link to.
   *     - route_parameters: The route parameters for the contextual link.
   *     - title: The title of the contextual link.
   *     - weight: The weight of the contextual link.
   *     - localized_options: The options of the link, which will be passed
   *       to the link generator.
   *     - metadata: The array of additional metadata that was passed in.
   */
  public function getContextualLinksArrayByGroup($group_name, array $route_parameters, array $metadata = array());

}
