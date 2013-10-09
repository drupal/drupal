<?php

/**
 * @file
 * Contains \Drupal\content_translation\Routing\ContentTranslationRouteSubscriber.
 */

namespace Drupal\content_translation\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Subscriber for entity translation routes.
 */
class ContentTranslationRouteSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a ContentTranslationRouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entityManager
   *   The entity type manager.
   */
  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'routes';
    return $events;
  }

  /**
   * Adds routes for entity translations.
   */
  public function routes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    foreach ($this->entityManager->getDefinitions() as $entity_type => $entity_info) {
      if ($entity_info['translatable'] && isset($entity_info['translation'])) {
        $path = '/' . str_replace($entity_info['menu_path_wildcard'], '{entity}', $entity_info['menu_base_path']) . '/translations';
        $route = new Route(
         $path,
          array(
            '_content' => '\Drupal\content_translation\Controller\ContentTranslationController::overview',
            '_title' => 'Translate',
            'account' => 'NULL',
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
        $collection->add("content_translation.translation_overview_$entity_type", $route);

        $route = new Route(
          $path . '/add/{source}/{target}',
          array(
            '_content' => '\Drupal\content_translation\Controller\ContentTranslationController::add',
            'source' => NULL,
            'target' => NULL,
            '_title' => 'Add',

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
            '_content' => '\Drupal\content_translation\Form\ContentTranslationForm::deleteTranslation',
            'language' => NULL,
            '_title' => 'Delete',
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
  }

}
