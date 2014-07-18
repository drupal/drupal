<?php

/**
 * @file
 * Contains \Drupal\field_ui\Routing\RouteSubscriber.
 */

namespace Drupal\field_ui\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
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
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(EntityManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->manager->getDefinitions() as $entity_type_id => $entity_type) {
      $defaults = array();
      if ($entity_type->isFieldable() && $entity_type->hasLinkTemplate('admin-form')) {
        // Try to get the route from the current collection.
        if (!$entity_route = $collection->get($entity_type->getLinkTemplate('admin-form'))) {
          continue;
        }
        $path = $entity_route->getPath();

        $options = array();
        if (($bundle_entity_type = $entity_type->getBundleEntityType()) && $bundle_entity_type !== 'bundle') {
          $options['parameters'][$entity_type->getBundleEntityType()] = array(
            'type' => 'entity:' . $entity_type->getBundleEntityType(),
          );
        }

        $route = new Route(
          "$path/fields/{field_instance_config}",
          array(
            '_form' => '\Drupal\field_ui\Form\FieldInstanceEditForm',
            '_title_callback' => '\Drupal\field_ui\Form\FieldInstanceEditForm::getTitle',
          ),
          array('_entity_access' => 'field_instance_config.update'),
          $options
        );
        $collection->add("field_ui.instance_edit_$entity_type_id", $route);

        $route = new Route(
          "$path/fields/{field_instance_config}/storage",
          array('_form' => '\Drupal\field_ui\Form\FieldStorageEditForm'),
          array('_entity_access' => 'field_instance_config.update'),
          $options
        );
        $collection->add("field_ui.storage_edit_$entity_type_id", $route);

        $route = new Route(
          "$path/fields/{field_instance_config}/delete",
          array('_entity_form' => 'field_instance_config.delete'),
          array('_entity_access' => 'field_instance_config.delete'),
          $options
        );
        $collection->add("field_ui.delete_$entity_type_id", $route);

        // If the entity type has no bundles, use the entity type.
        $defaults['entity_type_id'] = $entity_type_id;
        if (!$entity_type->hasKey('bundle')) {
          $defaults['bundle'] = $entity_type_id;
        }
        $route = new Route(
          "$path/fields",
          array(
            '_form' => '\Drupal\field_ui\FieldOverview',
            '_title' => 'Manage fields',
          ) + $defaults,
          array('_permission' => 'administer ' . $entity_type_id . ' fields'),
          $options
        );
        $collection->add("field_ui.overview_$entity_type_id", $route);

        $route = new Route(
          "$path/form-display",
          array(
            '_form' => '\Drupal\field_ui\FormDisplayOverview',
            '_title' => 'Manage form display',
          ) + $defaults,
          array('_field_ui_form_mode_access' => 'administer ' . $entity_type_id . ' form display'),
          $options
        );
        $collection->add("field_ui.form_display_overview_$entity_type_id", $route);

        $route = new Route(
          "$path/form-display/{form_mode_name}",
          array(
            '_form' => '\Drupal\field_ui\FormDisplayOverview',
            '_title' => 'Manage form display',
          ) + $defaults,
          array('_field_ui_form_mode_access' => 'administer ' . $entity_type_id . ' form display'),
          $options
        );
        $collection->add("field_ui.form_display_overview_form_mode_$entity_type_id", $route);

        $route = new Route(
          "$path/display",
          array(
            '_form' => '\Drupal\field_ui\DisplayOverview',
            '_title' => 'Manage display',
          ) + $defaults,
          array('_field_ui_view_mode_access' => 'administer ' . $entity_type_id . ' display'),
          $options
        );
        $collection->add("field_ui.display_overview_$entity_type_id", $route);

        $route = new Route(
          "$path/display/{view_mode_name}",
          array(
            '_form' => '\Drupal\field_ui\DisplayOverview',
            '_title' => 'Manage display',
          ) + $defaults,
          array('_field_ui_view_mode_access' => 'administer ' . $entity_type_id . ' display'),
          $options
        );
        $collection->add("field_ui.display_overview_view_mode_$entity_type_id", $route);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -100);
    return $events;
  }

}
