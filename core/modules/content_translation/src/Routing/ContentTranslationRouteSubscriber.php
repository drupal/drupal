<?php

/**
 * @file
 * Contains \Drupal\content_translation\Routing\ContentTranslationRouteSubscriber.
 */

namespace Drupal\content_translation\Routing;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Access\AccessManagerInterface;
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
      if (!$entity_route = $collection->get($entity_type->getLinkTemplate('canonical'))) {
        continue;
      }
      $path = $entity_route->getPath() . '/translations';

      // Inherit admin route status from edit route, if exists.
      $is_admin = FALSE;
      if ($edit_route = $collection->get($entity_type->getLinkTemplate('edit-form'))) {
        $is_admin = (bool) $edit_route->getOption('_admin_route');
      }

      $route = new Route(
        $path,
        array(
          '_controller' => '\Drupal\content_translation\Controller\ContentTranslationController::overview',
          'entity_type_id' => $entity_type_id,
        ),
        array(
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
      $collection->add($entity_type->getLinkTemplate('drupal:content-translation-overview'), $route);

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
          '_permission' => 'translate any entity',
          '_access_content_translation_manage' => 'create',
        ),
        array(
          '_access_mode' => AccessManagerInterface::ACCESS_MODE_ANY,
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
      $collection->add("content_translation.translation_add_$entity_type_id", $route);

      $route = new Route(
        $path . '/edit/{language}',
        array(
          '_controller' => '\Drupal\content_translation\Controller\ContentTranslationController::edit',
          'language' => NULL,
          '_title' => 'Edit',
          'entity_type_id' => $entity_type_id,
        ),
        array(
          '_permission' => 'translate any entity',
          '_access_content_translation_manage' => 'update',
        ),
        array(
          '_access_mode' => AccessManagerInterface::ACCESS_MODE_ANY,
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
      $collection->add("content_translation.translation_edit_$entity_type_id", $route);

      $route = new Route(
        $path . '/delete/{language}',
        array(
          '_form' => '\Drupal\content_translation\Form\ContentTranslationDeleteForm',
          'language' => NULL,
          '_title' => 'Delete',
          'entity_type_id' => $entity_type_id,
        ),
        array(
          '_permission' => 'translate any entity',
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
          '_access_mode' => AccessManagerInterface::ACCESS_MODE_ANY,
          '_admin_route' => $is_admin,
        )
      );
      $collection->add("content_translation.translation_delete_$entity_type_id", $route);
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
