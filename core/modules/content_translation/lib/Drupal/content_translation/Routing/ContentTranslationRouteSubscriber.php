<?php

/**
 * @file
 * Contains \Drupal\content_translation\Routing\ContentTranslationRouteSubscriber.
 */

namespace Drupal\content_translation\Routing;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

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
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a ContentTranslationRouteSubscriber object.
   *
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager
   *   The content translation manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(ContentTranslationManagerInterface $content_translation_manager, RouteProviderInterface $route_provider) {
    $this->contentTranslationManager = $content_translation_manager;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  protected function routes(RouteCollection $collection) {
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entity_type => $entity_info) {
      // First try to get the route from the dynamic_routes collection.
      if (!$entity_route = $collection->get($entity_info['links']['canonical'])) {
        // Then try to get the route from the route provider itself, checking
        // all previous collections.
        try {
          $entity_route = $this->routeProvider->getRouteByName($entity_info['links']['canonical']);
        }
        // If the route was not found, skip this entity type.
        catch (RouteNotFoundException $e) {
          continue;
        }
      }
      $path = $entity_route->getPath() . '/translations';

      $route = new Route(
       $path,
        array(
          '_content' => '\Drupal\content_translation\Controller\ContentTranslationController::overview',
          '_title' => 'Translate',
          'account' => 'NULL',
          '_entity_type' => $entity_type,
        ),
        array(
          '_access_content_translation_overview' => $entity_type,
          '_permission' => 'translate any entity',
        ),
        array(
          '_access_mode' => 'ANY',
          'parameters' => array(
            'entity' => array(
              'type' => 'entity:' . $entity_type,
            ),
          ),
        )
      );
      $collection->add($entity_info['links']['drupal:content-translation-overview'], $route);

      $route = new Route(
        $path . '/add/{source}/{target}',
        array(
          '_content' => '\Drupal\content_translation\Controller\ContentTranslationController::add',
          'source' => NULL,
          'target' => NULL,
          '_title' => 'Add',
          '_entity_type' => $entity_type,

        ),
        array(
          '_permission' => 'translate any entity',
          '_access_content_translation_manage' => 'create',
        ),
        array(
          '_access_mode' => 'ANY',
          'parameters' => array(
            'entity' => array(
              'type' => 'entity:' . $entity_type,
            ),
          ),
        )
      );
      $collection->add("content_translation.translation_add_$entity_type", $route);

      $route = new Route(
        $path . '/edit/{language}',
        array(
          '_content' => '\Drupal\content_translation\Controller\ContentTranslationController::edit',
          'language' => NULL,
          '_title' => 'Edit',
          '_entity_type' => $entity_type,
        ),
        array(
          '_permission' => 'translate any entity',
          '_access_content_translation_manage' => 'update',
        ),
        array(
          '_access_mode' => 'ANY',
          'parameters' => array(
            'entity' => array(
              'type' => 'entity:' . $entity_type,
            ),
          ),
        )
      );
      $collection->add("content_translation.translation_edit_$entity_type", $route);

      $route = new Route(
        $path . '/delete/{language}',
        array(
          '_form' => '\Drupal\content_translation\Form\ContentTranslationDeleteForm',
          'language' => NULL,
          '_title' => 'Delete',
          '_entity_type' => $entity_type,
        ),
        array(
          '_permission' => 'translate any entity',
          '_access_content_translation_manage' => 'delete',
        ),
        array(
          'parameters' => array(
            'entity' => array(
              'type' => 'entity:' . $entity_type,
            ),
          ),
          '_access_mode' => 'ANY',
        )
      );
      $collection->add("content_translation.delete_$entity_type", $route);
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
