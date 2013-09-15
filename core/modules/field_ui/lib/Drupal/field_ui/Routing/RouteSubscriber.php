<?php

/**
 * @file
 * Contains \Drupal\field_ui\Routing\RouteSubscriber.
 */

namespace Drupal\field_ui\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\EntityManager;

/**
 * Subscriber for Field UI routes.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $manager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManager $manager
   *   The entity type manager.
   */
  public function __construct(EntityManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'routes';
    return $events;
  }

  /**
   * Adds routes for the Field UI.
   */
  public function routes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    foreach ($this->manager->getDefinitions() as $entity_type => $entity_info) {
      $defaults = array();
      if ($entity_info['fieldable'] && isset($entity_info['route_base_path'])) {
        $path = $entity_info['route_base_path'];

        $route = new Route(
          "$path/fields/{field_instance}",
          array('_form' => '\Drupal\field_ui\Form\FieldInstanceEditForm'),
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
          array('_form' => '\Drupal\field_ui\FieldOverview') + $defaults,
          array('_permission' => 'administer ' . $entity_type . ' fields')
        );
        $collection->add("field_ui.overview_$entity_type", $route);

        $route = new Route(
          "$path/form-display",
          array('_form' => '\Drupal\field_ui\FormDisplayOverview') + $defaults,
          array('_permission' => 'administer ' . $entity_type . ' form display')
        );
        $collection->add("field_ui.form_display_overview_$entity_type", $route);

        foreach (entity_get_form_modes($entity_type) as $form_mode => $form_mode_info) {
          $route = new Route(
            "$path/form-display/$form_mode",
            array(
              '_form' => '\Drupal\field_ui\FormDisplayOverview',
              'mode' => $form_mode,
            ) + $defaults,
            array('_field_ui_form_mode_access' => 'administer ' . $entity_type . ' form display'));
          $collection->add("field_ui.form_display_overview_$entity_type" . '_'. $form_mode, $route);
        }

        $route = new Route(
          "$path/display",
          array('_form' => '\Drupal\field_ui\DisplayOverview') + $defaults,
          array('_permission' => 'administer ' . $entity_type . ' display')
        );
        $collection->add("field_ui.display_overview_$entity_type", $route);

        foreach (entity_get_view_modes($entity_type) as $view_mode => $view_mode_info) {
          $route = new Route(
            "$path/display/$view_mode",
            array(
              '_form' => '\Drupal\field_ui\DisplayOverview',
              'mode' => $view_mode,
            ) + $defaults,
            array('_field_ui_view_mode_access' => 'administer ' . $entity_type . ' display'));
          $collection->add("field_ui.display_overview_$entity_type" . '_' . $view_mode, $route);
        }
      }
    }
  }

}
