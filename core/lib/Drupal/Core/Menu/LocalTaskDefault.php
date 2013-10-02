<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\LocalTaskDefault.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default object used for LocalTaskPlugins.
 */
class LocalTaskDefault extends PluginBase implements LocalTaskInterface, ContainerFactoryPluginInterface {

  /**
   * The route provider to load routes by name.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * TRUE if this plugin is forced active for options attributes.
   *
   * @var bool
   */
  protected $active = FALSE;

  /**
   * Constructs a \Drupal\system\Plugin\LocalTaskDefault object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, RouteProviderInterface $route_provider) {
    $this->routeProvider = $route_provider;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return $this->pluginDefinition['route_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(Request $request) {
    $parameters = isset($this->pluginDefinition['route_parameters']) ? $this->pluginDefinition['route_parameters'] : array();
    $route = $this->routeProvider->getRouteByName($this->getRouteName());
    $variables = $route->compile()->getVariables();

    // Normally the \Drupal\Core\ParamConverter\ParamConverterManager has
    // processed the Request attributes, and in that case the _raw_variables
    // attribute holds the original path strings keyed to the corresponding
    // slugs in the path patterns. For example, if the route's path pattern is
    // /filter/tips/{filter_format} and the path is /filter/tips/plain_text then
    // $raw_variables->get('filter_format') == 'plain_text'.

    $raw_variables = $request->attributes->get('_raw_variables');

    foreach ($variables as $name) {
      if (isset($parameters[$name])) {
        continue;
      }

      if ($raw_variables && $raw_variables->has($name)) {
        $parameters[$name] = $raw_variables->get($name);
      }
      elseif ($request->attributes->has($name)) {
        $parameters[$name] = $request->attributes->get($name);
      }
    }
    // The UrlGenerator will throw an exception if expected parameters are
    // missing. This method should be overridden if that is possible.
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    // Subclasses may pull in the request or specific attributes as parameters.
    return $this->t($this->pluginDefinition['title']);
  }

  /**
   * Returns the weight of the local task.
   *
   * @return int
   *   The weight of the task. If not defined in the annotation returns 0 by
   *   default or -10 for the root tab.
   */
  public function getWeight() {
    // By default the weight is 0, or -10 for the root tab.
    if (!isset($this->pluginDefinition['weight'])) {
      if ($this->pluginDefinition['tab_root_id'] == $this->pluginDefinition['id']) {
        $this->pluginDefinition['weight'] = -10;
      }
      else {
        $this->pluginDefinition['weight'] = 0;
      }
    }
    return (int) $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(Request $request) {
    $options = $this->pluginDefinition['options'];
    if ($this->active) {
      if (empty($options['attributes']['class']) || !in_array('active', $options['attributes']['class'])) {
        $options['attributes']['class'][] = 'active';
      }
    }
    return (array) $options;
  }

  /**
   * {@inheritdoc}
   */
  public function setActive($active = TRUE) {
    $this->active = $active;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getActive() {
    return $this->active;
  }

}
