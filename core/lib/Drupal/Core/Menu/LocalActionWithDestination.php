<?php

namespace Drupal\Core\Menu;

use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Modifies the local action to add a destination.
 */
class LocalActionWithDestination extends LocalActionDefault {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteProviderInterface $route_provider,
    protected RedirectDestinationInterface $redirectDestination,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('router.route_provider'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    // Append the current path or destination to the query string.
    $options['query']['destination'] = $this->redirectDestination->get();
    return $options;
  }

}
