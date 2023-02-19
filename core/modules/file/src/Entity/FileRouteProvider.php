<?php

namespace Drupal\file\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for files.
 */
class FileRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();

    $route = (new Route('/file/{file}/delete'))
      ->addDefaults([
        '_entity_form' => 'file.delete',
        '_title' => 'Delete',
      ])
      ->setRequirement('file', '\d+')
      ->setRequirement('_entity_access', 'file.delete')
      ->setOption('_admin_route', TRUE);
    $route_collection->add('entity.file.delete_form', $route);

    return $route_collection;
  }

}
