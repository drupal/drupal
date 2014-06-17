<?php

/**
 * @file
 * Contains \Drupal\block\EventSubscriber\NodeRouteContext.
 */

namespace Drupal\block\EventSubscriber;

use Drupal\Core\Plugin\Context\Context;
use Drupal\node\Entity\Node;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Sets the current node as a context on node routes.
 */
class NodeRouteContext extends BlockConditionContextSubscriberBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new NodeRouteContext.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  protected function determineBlockContext() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->attributes->has(RouteObjectInterface::ROUTE_OBJECT) && ($route_contexts = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)->getOption('parameters')) && isset($route_contexts['node'])) {
      $context = new Context($route_contexts['node']);
      if ($request->attributes->has('node')) {
        $context->setContextValue($request->attributes->get('node'));
      }
      $this->addContext('node', $context);
    }
    elseif ($request->attributes->get(RouteObjectInterface::ROUTE_NAME) == 'node.add') {
      $node_type = $request->attributes->get('node_type');
      $context = new Context(array('type' => 'entity:node'));
      $context->setContextValue(Node::create(array('type' => $node_type->id())));
      $this->addContext('node', $context);
    }
  }

}
