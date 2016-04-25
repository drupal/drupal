<?php

namespace Drupal\Core\Render;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired when rendering main content, to select a page display variant.
 *
 * Subscribers of this event can call the following setters to pass additional
 * information along to the selected variant:
 * - self::setPluginConfiguration()
 * - self::setContexts()
 * - self::addCacheableDependency()
 *
 * @see \Drupal\Core\Render\RenderEvents::SELECT_PAGE_DISPLAY_VARIANT
 * @see \Drupal\Core\Render\MainContent\HtmlRenderer
 */
class PageDisplayVariantSelectionEvent extends Event implements RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * The selected page display variant plugin ID.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The configuration for the selected page display variant.
   *
   * @var array
   */
  protected $pluginConfiguration = [];

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * An array of collected contexts to pass to the page display variant.
   *
   * @var \Drupal\Component\Plugin\Context\ContextInterface[]
   */
  protected $contexts = [];

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
   *
   * @return $this
   */
  public function setPluginId($plugin_id) {
    $this->pluginId = $plugin_id;
    return $this;
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
   * Set the configuration for the selected page display variant.
   *
   * @param array $configuration
   *   The configuration for the selected page display variant.
   *
   * @return $this
   */
  public function setPluginConfiguration(array $configuration) {
    $this->pluginConfiguration = $configuration;
    return $this;
  }

  /**
   * Get the configuration for the selected page display variant.
   *
   * @return array
   */
  public function getPluginConfiguration() {
    return $this->pluginConfiguration;
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

  /**
   * Gets the contexts that were set during event dispatch.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface[]
   *   An array of set contexts, keyed by context name.
   */
  public function getContexts() {
    return $this->contexts;
  }

  /**
   * Sets the contexts to be passed to the page display variant.
   *
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts, keyed by context name.
   *
   * @return $this
   */
  public function setContexts(array $contexts) {
    $this->contexts = $contexts;
    return $this;
  }

}
