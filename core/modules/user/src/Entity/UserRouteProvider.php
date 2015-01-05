<?php

/**
 * @file
 * Contains \Drupal\user\Entity\UserRouteProvider.
 */

namespace Drupal\user\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for the user entity.
 */
class UserRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();
    $route = (new Route('/user/{user}'))
      ->setDefaults([
        '_entity_view' => 'user.full',
        '_title_callback' => 'Drupal\user\Controller\UserController::userTitle',
      ])
      ->setRequirement('_entity_access', 'user.view');
    $route_collection->add('entity.user.canonical', $route);

    $route = (new Route('/user/{user}/edit'))
      ->setDefaults([
        '_entity_form' => 'user.default',
        '_title_callback' => 'Drupal\user\Controller\UserController::userTitle',
      ])
      ->setOption('_admin_route', TRUE)
      ->setRequirement('_entity_access', 'user.update');
    $route_collection->add('entity.user.edit_form', $route);

    $route = (new Route('/user/{user}/cancel'))
      ->setDefaults([
        '_title' => 'Cancel account',
        '_entity_form' => 'user.cancel',
      ])
      ->setOption('_admin_route', TRUE)
      ->setRequirement('_entity_access', 'user.delete');
    $route_collection->add('entity.user.cancel_form', $route);

    return $route_collection;
  }

}
