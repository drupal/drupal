<?php

namespace Drupal\block_content\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Modifies the 'Add content block' local action.
 */
class BlockContentAddLocalAction extends LocalActionDefault {

  /**
   * Constructs a BlockContentAddLocalAction object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteProviderInterface $routeProvider,
    protected RequestStack $requestStack,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $routeProvider);
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
      $container->get('request_stack'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    // If the route specifies a theme, append it to the query string.
    if ($theme = $route_match->getParameter('theme')) {
      $options['query']['theme'] = $theme;
    }

    // If the current request has a region, append it to the query string.
    if ($region = $this->requestStack->getCurrentRequest()->query->getString('region')) {
      $options['query']['region'] = $region;
    }
    return $options;
  }

}
