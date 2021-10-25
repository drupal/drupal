<?php

namespace Drupal\user\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * User route subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!$route_name = $entity_type->get('field_ui_base_route')) {
        continue;
      }

      if (!$bundle_entity_type = $entity_type->getBundleEntityType()) {
        continue;
      }

      // Try to get the route from the current collection.
      if (!$entity_route = $collection->get($route_name)) {
        continue;
      }

      $route = new Route(
        $entity_route->getPath() . '/permissions',
        [
          '_title' => 'Manage permissions',
          '_form' => 'Drupal\user\Form\UserPermissionsBundleForm',
          'entity_type_id' => $entity_type_id,
          'bundle_entity_type' => $bundle_entity_type,
        ],
        [
          '_permission' => 'administer permissions',
          '_custom_access' => '\Drupal\user\Form\UserPermissionsBundleForm::access',
        ],
        [
          // Indicate that Drupal\Core\Entity\EntityBundleRouteEnhancer should
          // set the bundle parameter.
          '_field_ui' => TRUE,
          'parameters' => [
            $bundle_entity_type => [
              'type' => "entity:$bundle_entity_type",
              'with_config_overrides' => TRUE,
            ],
          ],
        ] + $entity_route->getOptions()
      );
      $collection->add("entity.$bundle_entity_type.permission_form", $route);
    }
  }

}
