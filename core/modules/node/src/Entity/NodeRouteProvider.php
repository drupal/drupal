<?php

namespace Drupal\node\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for nodes.
 */
class NodeRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();
    $route = (new Route('/node/{node}'))
      ->addDefaults([
        '_controller' => '\Drupal\node\Controller\NodeViewController::view',
        '_title_callback' => '\Drupal\node\Controller\NodeViewController::title',
      ])
      ->setRequirement('node', '\d+')
      ->setRequirement('_entity_access', 'node.view');
    $route_collection->add('entity.node.canonical', $route);

    $route = (new Route('/node/{node}/delete'))
      ->addDefaults([
        '_entity_form' => 'node.delete',
        '_title' => 'Delete',
      ])
      ->setRequirement('node', '\d+')
      ->setRequirement('_entity_access', 'node.delete')
      ->setOption('_node_operation_route', TRUE);
    $route_collection->add('entity.node.delete_form', $route);

    $route = (new Route('/node/{node}/edit'))
      ->setDefault('_entity_form', 'node.edit')
      ->setRequirement('_entity_access', 'node.update')
      ->setRequirement('node', '\d+')
      ->setOption('_node_operation_route', TRUE);
    $route_collection->add('entity.node.edit_form', $route);

    return $route_collection;
  }

}
