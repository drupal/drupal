<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for the Layout Builder UI.
 *
 * @internal
 */
class LayoutBuilderRoutes {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new LayoutBuilderRoutes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Generates layout builder routes.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function getRoutes() {
    $routes = [];

    foreach ($this->getEntityTypes() as $entity_type_id => $entity_type) {
      $integer_id = $this->hasIntegerId($entity_type);

      $template = $entity_type->getLinkTemplate('layout-builder');
      $route = (new Route($template))
        ->setDefaults([
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::layout',
          '_title_callback' => '\Drupal\layout_builder\Controller\LayoutBuilderController::title',
          'entity' => NULL,
          'entity_type_id' => $entity_type_id,
          'is_rebuilding' => FALSE,
        ])
        ->addRequirements([
          '_has_layout_section' => 'true',
        ])
        ->addOptions([
          '_layout_builder' => TRUE,
          'parameters' => [
            $entity_type_id => [
              'type' => 'entity:{entity_type_id}',
              'layout_builder_tempstore' => TRUE,
            ],
          ],
        ]);
      if ($integer_id) {
        $route->setRequirement($entity_type_id, '\d+');
      }
      $routes["entity.$entity_type_id.layout_builder"] = $route;

      $route = (new Route("$template/save"))
        ->setDefaults([
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::saveLayout',
          'entity' => NULL,
          'entity_type_id' => $entity_type_id,
        ])
        ->addRequirements([
          '_has_layout_section' => 'true',
        ])
        ->addOptions([
          '_layout_builder' => TRUE,
          'parameters' => [
            $entity_type_id => [
              'type' => 'entity:{entity_type_id}',
              'layout_builder_tempstore' => TRUE,
            ],
          ],
        ]);
      if ($integer_id) {
        $route->setRequirement($entity_type_id, '\d+');
      }
      $routes["entity.$entity_type_id.save_layout"] = $route;

      $route = (new Route("$template/cancel"))
        ->setDefaults([
          '_controller' => '\Drupal\layout_builder\Controller\LayoutBuilderController::cancelLayout',
          'entity' => NULL,
          'entity_type_id' => $entity_type_id,
        ])
        ->addRequirements([
          '_has_layout_section' => 'true',
        ])
        ->addOptions([
          '_layout_builder' => TRUE,
          'parameters' => [
            $entity_type_id => [
              'type' => 'entity:{entity_type_id}',
              'layout_builder_tempstore' => TRUE,
            ],
          ],
        ]);
      if ($integer_id) {
        $route->setRequirement($entity_type_id, '\d+');
      }
      $routes["entity.$entity_type_id.cancel_layout"] = $route;
    }
    return $routes;
  }

  /**
   * Determines if this entity type's ID is stored as an integer.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type.
   *
   * @return bool
   *   TRUE if this entity type's ID key is always an integer, FALSE otherwise.
   */
  protected function hasIntegerId(EntityTypeInterface $entity_type) {
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type->id());
    return $field_storage_definitions[$entity_type->getKey('id')]->getType() === 'integer';
  }

  /**
   * Returns an array of relevant entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of entity types.
   */
  protected function getEntityTypes() {
    return array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $entity_type) {
      return $entity_type->hasLinkTemplate('layout-builder');
    });
  }

}
