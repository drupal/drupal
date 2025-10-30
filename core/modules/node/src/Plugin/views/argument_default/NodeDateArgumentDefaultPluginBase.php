<?php

namespace Drupal\node\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\argument\Date;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for node date values.
 */
abstract class NodeDateArgumentDefaultPluginBase extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * Constructs a new NodeDateArgumentDefaultPluginBase instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected RouteMatchInterface $routeMatch, protected DateFormatterInterface $dateFormatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument(): string|bool {
    // Return a time value from the current node if available.
    if (($node = $this->routeMatch->getParameter('node')) && $node instanceof NodeInterface) {

      // The Date argument handlers provide their own format strings, otherwise
      // use a default.
      $format = $this->argument instanceof Date ? $this->argument->getArgFormat() : 'Y-m-d';

      return $this->dateFormatter->format($this->getNodeDateValue($node), 'custom', $format);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * Gets a timestamp value from the passed node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to get the timestamp value from.
   *
   * @return int
   *   A timestamp value from a node field.
   */
  abstract protected function getNodeDateValue(NodeInterface $node): int;

}
