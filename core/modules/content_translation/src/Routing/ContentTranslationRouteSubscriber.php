<?php

namespace Drupal\content_translation\Routing;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for entity translation routes.
 */
class ContentTranslationRouteSubscriber extends RouteSubscriberBase {

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * Constructs a ContentTranslationRouteSubscriber object.
   *
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager
   *   The content translation manager.
   */
  public function __construct(ContentTranslationManagerInterface $content_translation_manager) {
    $this->contentTranslationManager = $content_translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entity_type_id => $entity_type) {
      // Try to get the route from the current collection.
      $link_template = $entity_type->getLinkTemplate('canonical');
      if (strpos($link_template, '/') !== FALSE) {
        $base_path = '/' . $link_template;
      }
      else {
        if (!$entity_route = $collection->get("entity.$entity_type_id.canonical")) {
          continue;
        }
        $base_path = $entity_route->getPath();
      }

      // Inherit admin route status from edit route, if exists.
      $is_admin = FALSE;
      $route_name = "entity.$entity_type_id.edit_form";
      if ($edit_route = $collection->get($route_name)) {
        $is_admin = (bool) $edit_route->getOption('_admin_route');
      }

      $path = $base_path . '/translations';

      $route = new Route(
        $path,
        array(
          '_controller' => '\Drupal\content_translation\Controller\ContentTranslationController::overview',
          'entity_type_id' => $entity_type_id,
        ),
        array(
          '_entity_access' =>  $entity_type_id . '.view',
          '_access_content_translation_overview' => $entity_type_id,
        ),
        array(
          'parameters' => array(
            $entity_type_id => array(
              'type' => 'entity:' . $entity_type_id,
            ),
          ),
          '_admin_route' => $is_admin,
        )
      );
      $route_name = "entity.$entity_type_id.content_translation_overview";
      $collection->add($route_name, $route);

      $route = new Route(
        $path . '/add/{source}/{target}',
        array(
          '_controller' => '\Drupal\content_translation\Controller\ContentTranslationController::add',
          'source' => NULL,
          'target' => NULL,
          '_title' => 'Add',
          'entity_type_id' => $entity_type_id,

        ),
        array(
          '_entity_access' =>  $entity_type_id . '.view',
          '_access_content_translation_manage' => 'create',
        ),
        array(
          'parameters' => array(
            'source' => array(
              'type' => 'language',
            ),
            'target' => array(
              'type' => 'language',
            ),
            $entity_type_id => array(
              'type' => 'entity:' . $entity_type_id,
            ),
          ),
          '_admin_route' => $is_admin,
        )
      );
      $collection->add("entity.$entity_type_id.content_translation_add", $route);

      $route = new Route(
        $path . '/edit/{language}',
        array(
          '_controller' => '\Drupal\content_translation\Controller\ContentTranslationController::edit',
          'language' => NULL,
          '_title' => 'Edit',
          'entity_type_id' => $entity_type_id,
        ),
        array(
          '_access_content_translation_manage' => 'update',
        ),
        array(
          'parameters' => array(
            'language' => array(
              'type' => 'language',
            ),
            $entity_type_id => array(
              'type' => 'entity:' . $entity_type_id,
            ),
          ),
          '_admin_route' => $is_admin,
        )
      );
      $collection->add("entity.$entity_type_id.content_translation_edit", $route);

      $route = new Route(
        $path . '/delete/{language}',
        array(
          '_entity_form' => $entity_type_id . '.content_translation_deletion',
          'language' => NULL,
          '_title' => 'Delete',
          'entity_type_id' => $entity_type_id,
        ),
        array(
          '_access_content_translation_manage' => 'delete',
        ),
        array(
          'parameters' => array(
            'language' => array(
              'type' => 'language',
            ),
            $entity_type_id => array(
              'type' => 'entity:' . $entity_type_id,
            ),
          ),
          '_admin_route' => $is_admin,
        )
      );
      $collection->add("entity.$entity_type_id.content_translation_delete", $route);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    // Should run after AdminRouteSubscriber so the routes can inherit admin
    // status of the edit routes on entities. Therefore priority -210.
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -210);
    return $events;
  }

}
