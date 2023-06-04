<?php

namespace Drupal\block_content\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Block content BC routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The route collection for adding routes.
   *
   * @var \Symfony\Component\Routing\RouteCollection
   */
  protected $collection;

  /**
   * The current base path.
   *
   * @var string
   */
  protected $basePath;

  /**
   * The BC base path.
   *
   * @var string
   */
  protected $basePathBc;

  /**
   * The redirect controller.
   *
   * @var string
   */
  protected $controller;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $this->collection = $collection;

    // @see block_content.routing.yml
    if ($this->setUpBaseRoute('entity.block_content_type.collection')) {
      $this->addRedirectRoute('block_content.type_add');
    }

    $entity_type = $this->entityTypeManager->getDefinition('block_content');
    if ($this->setUpBaseRoute($entity_type->get('field_ui_base_route'))) {
      foreach ($this->childRoutes($entity_type) as $route_name) {
        $this->addRedirectRoute($route_name);
      }
    }
  }

  /**
   * Gets parameters from a base route and saves them in class variables.
   *
   * @param string $base_route_name
   *   The name of a base route that already has a BC variant.
   *
   * @return bool
   *   TRUE if all parameters are set, FALSE if not.
   */
  protected function setUpBaseRoute(string $base_route_name): bool {
    $base_route = $this->collection->get($base_route_name);
    $base_route_bc = $this->collection->get("$base_route_name.bc");
    if (empty($base_route) || empty($base_route_bc)) {
      return FALSE;
    }

    $this->basePath = $base_route->getPath();
    $this->basePathBc = $base_route_bc->getPath();
    $this->controller = $base_route_bc->getDefault('_controller');
    if (empty($this->basePath) || empty($this->basePathBc) || empty($this->controller) || $this->basePathBc === $this->basePath) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Adds a redirect route.
   *
   * @param string $route_name
   *   The name of a route whose path has changed.
   */
  protected function addRedirectRoute(string $route_name): void {
    // Exit early if the BC route is already there.
    if (!empty($this->collection->get("$route_name.bc"))) {
      return;
    }

    $route = $this->collection->get($route_name);
    if (empty($route)) {
      return;
    }

    $new_path = $route->getPath();
    if (!str_starts_with($new_path, $this->basePath)) {
      return;
    }

    $bc_route = clone $route;
    // Set the path to what it was in earlier versions of Drupal.
    $bc_route->setPath($this->basePathBc . substr($new_path, strlen($this->basePath)));
    if ($bc_route->getPath() === $route->getPath()) {
      return;
    }

    // Replace the handler with the stored redirect controller.
    $defaults = array_diff_key($route->getDefaults(), array_flip([
      '_entity_form',
      '_entity_list',
      '_entity_view',
      '_form',
    ]));
    $defaults['_controller'] = $this->controller;
    $bc_route->setDefaults($defaults);

    $this->collection->add("$route_name.bc", $bc_route);
  }

  /**
   * Creates a list of routes that need BC redirects.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return string[]
   *   A list of route names.
   */
  protected function childRoutes(EntityTypeInterface $entity_type): array {
    $route_names = [];

    if ($field_ui_base_route = $entity_type->get('field_ui_base_route')) {
      $updated_routes = new RouteCollection();
      $updated_routes->add($field_ui_base_route, $this->collection->get($field_ui_base_route));
      $event = new RouteBuildEvent($updated_routes);

      // Apply route subscribers that add routes based on field_ui_base_route,
      // in the order of their weights.
      $subscribers = [
        'field_ui' => 'field_ui.subscriber',
        'content_translation' => 'content_translation.subscriber',
      ];
      foreach ($subscribers as $module_name => $service_name) {
        if ($this->moduleHandler->moduleExists($module_name)) {
          \Drupal::service($service_name)->onAlterRoutes($event);
        }
      }

      $updated_routes->remove($field_ui_base_route);
      $route_names = array_merge($route_names, array_keys($updated_routes->all()));
      $route_names = array_merge($route_names, [
        // @see \Drupal\config_translation\Routing\RouteSubscriber::alterRoutes()
        "config_translation.item.add.{$field_ui_base_route}",
        "config_translation.item.edit.{$field_ui_base_route}",
        "config_translation.item.delete.{$field_ui_base_route}",
      ]);
    }

    if ($entity_type_id = $entity_type->getBundleEntityType()) {
      $route_names = array_merge($route_names, [
        // @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider::getRoutes()
        "entity.{$entity_type_id}.delete_form",
        // @see \Drupal\config_translation\Routing\RouteSubscriber::alterRoutes()
        "entity.{$entity_type_id}.config_translation_overview",
        // @see \Drupal\user\Entity\EntityPermissionsRouteProvider::getRoutes()
        "entity.{$entity_type_id}.entity_permissions_form",
      ]);
    }

    if ($entity_id = $entity_type->id()) {
      $route_names = array_merge($route_names, [
        // @see \Drupal\config_translation\Routing\RouteSubscriber::alterRoutes()
        "entity.field_config.config_translation_overview.{$entity_id}",
        "config_translation.item.add.entity.field_config.{$entity_id}_field_edit_form",
        "config_translation.item.edit.entity.field_config.{$entity_id}_field_edit_form",
        "config_translation.item.delete.entity.field_config.{$entity_id}_field_edit_form",
        // @see \Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage::buildRoutes()
        "layout_builder.defaults.{$entity_id}.disable",
        "layout_builder.defaults.{$entity_id}.discard_changes",
        "layout_builder.defaults.{$entity_id}.view",
      ]);
    }

    return $route_names;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    // Go after ContentTranslationRouteSubscriber.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];
    return $events;
  }

}
