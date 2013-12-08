<?php

/**
 * @file
 * Contains \Drupal\field_ui\Routing\RouteSubscriber.
 */

namespace Drupal\field_ui\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Field UI routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(EntityManagerInterface $manager, RouteProviderInterface $route_provider) {
    $this->manager = $manager;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  protected function routes(RouteCollection $collection) {
    foreach ($this->manager->getDefinitions() as $entity_type => $entity_info) {
      $defaults = array();
      if ($entity_info['fieldable'] && isset($entity_info['links']['admin-form'])) {
        // First try to get the route from the dynamic_routes collection.
        if (!$entity_route = $collection->get($entity_info['links']['admin-form'])) {
          // Then try to get the route from the route provider itself, checking
          // all previous collections.
          try {
            $entity_route = $this->routeProvider->getRouteByName($entity_info['links']['admin-form']);
          }
          // If the route was not found, skip this entity type.
          catch (RouteNotFoundException $e) {
            continue;
          }
        }
        $path = $entity_route->getPath();

        $route = new Route(
          "$path/fields/{field_instance}",
          array(
            '_form' => '\Drupal\field_ui\Form\FieldInstanceEditForm',
            '_title_callback' => '\Drupal\field_ui\Form\FieldInstanceEditForm::getTitle',
          ),
          array('_permission' => 'administer ' . $entity_type . ' fields')
        );
        $collection->add("field_ui.instance_edit_$entity_type", $route);

        $route = new Route(
          "$path/fields/{field_instance}/field",
          array('_form' => '\Drupal\field_ui\Form\FieldEditForm'),
          array('_permission' => 'administer ' . $entity_type . ' fields')
        );
        $collection->add("field_ui.field_edit_$entity_type", $route);

        $route = new Route(
          "$path/fields/{field_instance}/delete",
          array('_entity_form' => 'field_instance.delete'),
          array('_permission' => 'administer ' . $entity_type . ' fields')
        );
        $collection->add("field_ui.delete_$entity_type", $route);

        // If the entity type has no bundles, use the entity type.
        $defaults['entity_type'] = $entity_type;
        if (empty($entity_info['entity_keys']['bundle'])) {
          $defaults['bundle'] = $entity_type;
        }
        $route = new Route(
          "$path/fields",
          array(
            '_form' => '\Drupal\field_ui\FieldOverview',
            '_title' => 'Manage fields',
          ) + $defaults,
          array('_permission' => 'administer ' . $entity_type . ' fields')
        );
        $collection->add("field_ui.overview_$entity_type", $route);

        $route = new Route(
          "$path/form-display",
          array(
            '_form' => '\Drupal\field_ui\FormDisplayOverview',
            '_title' => 'Manage form display',
          ) + $defaults,
          array('_field_ui_form_mode_access' => 'administer ' . $entity_type . ' form display')
        );
        $collection->add("field_ui.form_display_overview_$entity_type", $route);

        $route = new Route(
          "$path/form-display/{form_mode_name}",
          array(
            '_form' => '\Drupal\field_ui\FormDisplayOverview',
            'form_mode_name' => NULL,
          ) + $defaults,
          array('_field_ui_form_mode_access' => 'administer ' . $entity_type . ' form display')
        );
        $collection->add("field_ui.form_display_overview_form_mode_$entity_type", $route);

        $route = new Route(
          "$path/display",
          array(
            '_form' => '\Drupal\field_ui\DisplayOverview',
            '_title' => 'Manage display',
          ) + $defaults,
          array('_field_ui_view_mode_access' => 'administer ' . $entity_type . ' display')
        );
        $collection->add("field_ui.display_overview_$entity_type", $route);

        $route = new Route(
          "$path/display/{view_mode_name}",
          array(
            '_form' => '\Drupal\field_ui\DisplayOverview',
            'view_mode_name' => NULL,
          ) + $defaults,
          array('_field_ui_view_mode_access' => 'administer ' . $entity_type . ' display')
        );
        $collection->add("field_ui.display_overview_view_mode_$entity_type", $route);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::DYNAMIC] = array('onDynamicRoutes', -100);
    return $events;
  }

}
