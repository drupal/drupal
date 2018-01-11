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
      $defaults = [];
      $defaults['entity_type_id'] = $entity_type_id;

      $requirements = [];
      if ($this->hasIntegerId($entity_type)) {
        $requirements[$entity_type_id] = '\d+';
      }

      $options = [];
      $options['parameters']['section_storage']['layout_builder_tempstore'] = TRUE;
      $options['parameters'][$entity_type_id]['type'] = 'entity:' . $entity_type_id;

      $template = $entity_type->getLinkTemplate('layout-builder');
      $routes += $this->buildRoute('overrides', 'entity.' . $entity_type_id, $template, $defaults, $requirements, $options);
    }
    return $routes;
  }

  /**
   * Builds the layout routes for the given values.
   *
   * @param string $type
   *   The section storage type.
   * @param string $route_name_prefix
   *   The prefix to use for the route name.
   * @param string $path
   *   The path patten for the routes.
   * @param array $defaults
   *   An array of default parameter values.
   * @param array $requirements
   *   An array of requirements for parameters.
   * @param array $options
   *   An array of options.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  protected function buildRoute($type, $route_name_prefix, $path, array $defaults, array $requirements, array $options) {
    $routes = [];

    $defaults['section_storage_type'] = $type;
    // Provide an empty value to allow the section storage to be upcast.
    $defaults['section_storage'] = '';
    // Trigger the layout builder access check.
    $requirements['_has_layout_section'] = 'true';
    // Trigger the layout builder RouteEnhancer.
    $options['_layout_builder'] = TRUE;

    $main_defaults = $defaults;
    $main_defaults['is_rebuilding'] = FALSE;
    $main_defaults['_controller'] = '\Drupal\layout_builder\Controller\LayoutBuilderController::layout';
    $main_defaults['_title_callback'] = '\Drupal\layout_builder\Controller\LayoutBuilderController::title';
    $route = (new Route($path))
      ->setDefaults($main_defaults)
      ->setRequirements($requirements)
      ->setOptions($options);
    $routes["{$route_name_prefix}.layout_builder"] = $route;

    $save_defaults = $defaults;
    $save_defaults['_controller'] = '\Drupal\layout_builder\Controller\LayoutBuilderController::saveLayout';
    $route = (new Route("$path/save"))
      ->setDefaults($save_defaults)
      ->setRequirements($requirements)
      ->setOptions($options);
    $routes["{$route_name_prefix}.layout_builder_save"] = $route;

    $cancel_defaults = $defaults;
    $cancel_defaults['_controller'] = '\Drupal\layout_builder\Controller\LayoutBuilderController::cancelLayout';
    $route = (new Route("$path/cancel"))
      ->setDefaults($cancel_defaults)
      ->setRequirements($requirements)
      ->setOptions($options);
    $routes["{$route_name_prefix}.layout_builder_cancel"] = $route;

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
