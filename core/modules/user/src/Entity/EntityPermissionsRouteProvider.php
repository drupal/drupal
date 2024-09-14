<?php

namespace Drupal\user\Entity;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for the entity permissions form.
 *
 * Use this class as a route provider for an entity type such as Vocabulary. It
 * will provide routes for the entity permissions form.
 */
class EntityPermissionsRouteProvider implements EntityRouteProviderInterface, EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityPermissionsRouteProvider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();

    $entity_type_id = $entity_type->id();

    if ($entity_permissions_route = $this->getEntityPermissionsRoute($entity_type)) {
      $collection->add("entity.$entity_type_id.entity_permissions_form", $entity_permissions_route);
    }

    return $collection;
  }

  /**
   * Gets the entity permissions route.
   *
   * Built only for entity types that are bundles of other entity types and
   * define the 'entity-permissions-form' link template.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityPermissionsRoute(EntityTypeInterface $entity_type): ?Route {
    if (!$entity_type->hasLinkTemplate('entity-permissions-form')) {
      return NULL;
    }

    if (!$bundle_of_id = $entity_type->getBundleOf()) {
      return NULL;
    }

    $entity_type_id = $entity_type->id();
    $route = new Route(
      $entity_type->getLinkTemplate('entity-permissions-form'),
      [
        '_title' => 'Manage permissions',
        '_form' => 'Drupal\user\Form\EntityPermissionsForm',
        'entity_type_id' => $bundle_of_id,
        'bundle_entity_type' => $entity_type_id,
      ],
      [
        '_permission' => 'administer permissions',
      ],
      [
        // Indicate that Drupal\Core\Entity\Enhancer\EntityBundleRouteEnhancer should
        // set the bundle parameter.
        '_field_ui' => TRUE,
        'parameters' => [
          $entity_type_id => [
            'type' => "entity:$entity_type_id",
            'with_config_overrides' => TRUE,
          ],
        ],
        '_admin_route' => TRUE,
      ]
    );

    return $route;
  }

}
