<?php

/**
 * @file
 * Contains \Drupal\block\EventSubscriber\NodeRouteContext.
 */

namespace Drupal\block\EventSubscriber;

use Drupal\block\Event\BlockContextEvent;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;

/**
 * Sets the current node as a context on node routes.
 */
class NodeRouteContext extends BlockContextSubscriberBase {

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
  public function onBlockActiveContext(BlockContextEvent $event) {
    if (($route_object = $this->routeMatch->getRouteObject()) && ($route_contexts = $route_object->getOption('parameters')) && isset($route_contexts['node'])) {
      $context = new Context(new ContextDefinition($route_contexts['node']['type']));
      if ($node = $this->routeMatch->getParameter('node')) {
        $context->setContextValue($node);
      }
      $event->setContext('node.node', $context);
    }
    elseif ($this->routeMatch->getRouteName() == 'node.add') {
      $node_type = $this->routeMatch->getParameter('node_type');
      $context = new Context(new ContextDefinition('entity:node'));
      $context->setContextValue(Node::create(array('type' => $node_type->id())));
      $event->setContext('node.node', $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onBlockAdministrativeContext(BlockContextEvent $event) {
    $context = new Context(new ContextDefinition('entity:node'));
    $event->setContext('node', $context);
  }

}
