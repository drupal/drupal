<?php

/**
 * @file
 * Contains \Drupal\content_translation\Routing\ContentTranslationRouteSubscriber.
 */

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
  protected function alterRoutes(RouteCollection $collection, $provider) {
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entity_type_id => $entity_type) {
      // Try to get the route from the current collection.
      if (!$entity_route = $collection->get($entity_type->getLinkTemplate('canonical'))) {
        continue;
      }
      $path = $entity_route->getPath() . '/translations';

      $route = new Route(
       $path,
        array(
          '_content' => '\Drupal\content_translation\Controller\ContentTranslationController::overview',
          'account' => 'NULL',
          '_entity_type_id' => $entity_type_id,
        ),
        array(
          '_access_content_translation_overview' => $entity_type_id,
          '_permission' => 'translate any entity',
        ),
        array(
          '_access_mode' => 'ANY',
          'parameters' => array(
            'entity' => array(
              'type' => 'entity:' . $entity_type_id,
            ),
          ),
        )
      );
      $collection->add($entity_type->getLinkTemplate('drupal:content-translation-overview'), $route);

      $route = new Route(
        $path . '/add/{source}/{target}',
        array(
          '_content' => '\Drupal\content_translation\Controller\ContentTranslationController::add',
          'source' => NULL,
          'target' => NULL,
          '_title' => 'Add',
          '_entity_type_id' => $entity_type_id,

        ),
        array(
          '_permission' => 'translate any entity',
          '_access_content_translation_manage' => 'create',
        ),
        array(
          '_access_mode' => 'ANY',
          'parameters' => array(
            'entity' => array(
              'type' => 'entity:' . $entity_type_id,
            ),
          ),
        )
      );
      $collection->add("content_translation.translation_add_$entity_type_id", $route);

      $route = new Route(
        $path . '/edit/{language}',
        array(
          '_content' => '\Drupal\content_translation\Controller\ContentTranslationController::edit',
          'language' => NULL,
          '_title' => 'Edit',
          '_entity_type_id' => $entity_type_id,
        ),
        array(
          '_permission' => 'translate any entity',
          '_access_content_translation_manage' => 'update',
        ),
        array(
          '_access_mode' => 'ANY',
          'parameters' => array(
            'entity' => array(
              'type' => 'entity:' . $entity_type_id,
            ),
          ),
        )
      );
      $collection->add("content_translation.translation_edit_$entity_type_id", $route);

      $route = new Route(
        $path . '/delete/{language}',
        array(
          '_form' => '\Drupal\content_translation\Form\ContentTranslationDeleteForm',
          'language' => NULL,
          '_title' => 'Delete',
          '_entity_type_id' => $entity_type_id,
        ),
        array(
          '_permission' => 'translate any entity',
          '_access_content_translation_manage' => 'delete',
        ),
        array(
          'parameters' => array(
            'entity' => array(
              'type' => 'entity:' . $entity_type_id,
            ),
          ),
          '_access_mode' => 'ANY',
        )
      );
      $collection->add("content_translation.delete_$entity_type_id", $route);
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
