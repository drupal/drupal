<?php

namespace Drupal\content_moderation\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides the moderation configuration routes for config entities.
 */
class EntityTypeModerationRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();

    if ($moderation_route = $this->getModerationFormRoute($entity_type)) {
      $entity_type_id = $entity_type->id();
      $collection->add("entity.{$entity_type_id}.moderation", $moderation_route);
    }

    return $collection;
  }

  /**
   * Gets the moderation-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getModerationFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('moderation-form') && $entity_type->getFormClass('moderation')) {
      $entity_type_id = $entity_type->id();

      $route = new Route($entity_type->getLinkTemplate('moderation-form'));

      // @todo Come up with a new permission.
      $route
        ->setDefaults([
          '_entity_form' => "{$entity_type_id}.moderation",
          '_title' => 'Moderation',
        ])
        ->setRequirement('_permission', 'administer content moderation')
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

}
