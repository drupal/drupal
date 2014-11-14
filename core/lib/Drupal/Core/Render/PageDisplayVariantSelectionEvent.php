<?php

/**
 * @file
 * Contains \Drupal\Core\Render\PageDisplayVariantSelectionEvent.
 */

namespace Drupal\Core\Render;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired when rendering main content, to select a page display variant.
 *
 * @see \Drupal\Core\Render\RenderEvents::SELECT_PAGE_DISPLAY_VARIANT
 * @see \Drupal\Core\Render\MainContent\HtmlRenderer
 */
class PageDisplayVariantSelectionEvent extends Event {

  /**
   * The selected page display variant plugin ID.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs the page display variant plugin selection event.
   *
   * @param string
   *   The ID of the page display variant plugin to use by default.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match, for context.
   */
  public function __construct($plugin_id, RouteMatchInterface $route_match) {
    $this->pluginId = $plugin_id;
    $this->routeMatch = $route_match;
  }

  /**
   * The selected page display variant plugin ID.
   *
   * @param string $plugin_id
   *   The ID of the page display variant plugin to use.
   */
  public function setPluginId($plugin_id) {
    $this->pluginId = $plugin_id;
  }

  /**
   * The selected page display variant plugin ID.
   *
   * @return string;
   */
  public function getPluginId() {
    return $this->pluginId;
  }

  /**
   * Gets the current route match.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The current route match, for context.
   */
  public function getRouteMatch() {
    return $this->routeMatch;
  }

}
