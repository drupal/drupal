<?php

namespace Drupal\content_moderation\Entity\Routing;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamic route provider for the Content moderation module.
 *
 * Provides the following routes:
 * - The latest version tab, showing the latest revision of an entity, not the
 *   default one.
 *
 * @internal
 */
class EntityModerationRouteProvider implements EntityRouteProviderInterface, EntityHandlerInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new DefaultHtmlRouteProvider.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_manager) {
    $this->entityFieldManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();

    if ($moderation_route = $this->getLatestVersionRoute($entity_type)) {
      $entity_type_id = $entity_type->id();
      $collection->add("entity.{$entity_type_id}.latest_version", $moderation_route);
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
  protected function getLatestVersionRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('latest-version') && $entity_type->hasViewBuilderClass()) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_type->getLinkTemplate('latest-version'));
      $route
        ->addDefaults([
          '_entity_view' => "{$entity_type_id}.full",
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
        ])
        // If the entity type is a node, unpublished content will be visible
        // if the user has the "view all unpublished content" permission.
        ->setRequirement('_entity_access', "{$entity_type_id}.view")
        ->setRequirement('_content_moderation_latest_version', 'TRUE')
        ->setOption('_content_moderation_entity_type', $entity_type_id)
        ->setOption('parameters', [
          $entity_type_id => [
            'type' => 'entity:' . $entity_type_id,
            'load_pending_revision' => 1,
          ],
        ]);

      // Entity types with serial IDs can specify this in their route
      // requirements, improving the matching process.
      if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
        $route->setRequirement($entity_type_id, '\d+');
      }
      return $route;
    }
  }

  /**
   * Gets the type of the ID key for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type.
   *
   * @return string|null
   *   The type of the ID key for a given entity type, or NULL if the entity
   *   type does not support fields.
   */
  protected function getEntityTypeIdKeyType(EntityTypeInterface $entity_type) {
    if (!$entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      return NULL;
    }

    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type->id());
    return $field_storage_definitions[$entity_type->getKey('id')]->getType();
  }

}
