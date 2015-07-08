<?php

/**
 * @file
 * Contains \Drupal\node\ContextProvider\NodeRouteContext.
 */

namespace Drupal\node\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;

/**
 * Sets the current node as a context on node routes.
 */
class NodeRouteContext implements ContextProviderInterface {

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new NodeRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context = new Context(new ContextDefinition('entity:node', NULL, FALSE));
    if (($route_object = $this->routeMatch->getRouteObject()) && ($route_contexts = $route_object->getOption('parameters')) && isset($route_contexts['node'])) {
      if ($node = $this->routeMatch->getParameter('node')) {
        $context->setContextValue($node);
      }
    }
    elseif ($this->routeMatch->getRouteName() == 'node.add') {
      $node_type = $this->routeMatch->getParameter('node_type');
      $context->setContextValue(Node::create(array('type' => $node_type->id())));
    }
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);
    $context->addCacheableDependency($cacheability);
    $result['node'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:node'));
    return ['node' => $context];
  }

}
