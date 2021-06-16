<?php

namespace Drupal\content_moderation\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\workflows\Entity\Workflow;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for moderated revisionable entity forms.
 *
 * @internal
 *   There is ongoing discussion about how pending revisions should behave.
 *   The logic enabling pending revision support is likely to change once a
 *   decision is made.
 *
 * @see https://www.drupal.org/node/2940575
 */
class ContentModerationRouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An associative array of moderated entity types keyed by ID.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface[]
   */
  protected $moderatedEntityTypes;

  /**
   * ContentModerationRouteSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($collection as $route) {
      $this->setLatestRevisionFlag($route);
    }
  }

  /**
   * Ensure revisionable entities load the latest revision on entity forms.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   */
  protected function setLatestRevisionFlag(Route $route) {
    if (!$entity_form = $route->getDefault('_entity_form')) {
      return;
    }
    // Only set the flag on entity types which are revisionable.
    list($entity_type) = explode('.', $entity_form, 2);
    if (!isset($this->getModeratedEntityTypes()[$entity_type]) || !$this->getModeratedEntityTypes()[$entity_type]->isRevisionable()) {
      return;
    }
    $parameters = $route->getOption('parameters') ?: [];
    foreach ($parameters as &$parameter) {
      if (isset($parameter['type']) && $parameter['type'] === 'entity:' . $entity_type && !isset($parameter['load_latest_revision'])) {
        $parameter['load_latest_revision'] = TRUE;
      }
    }
    $route->setOption('parameters', $parameters);
  }

  /**
   * Returns the moderated entity types.
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface[]
   *   An associative array of moderated entity types keyed by ID.
   */
  protected function getModeratedEntityTypes() {
    if (!isset($this->moderatedEntityTypes)) {
      $entity_types = $this->entityTypeManager->getDefinitions();
      /** @var \Drupal\workflows\WorkflowInterface $workflow */
      foreach (Workflow::loadMultipleByType('content_moderation') as $workflow) {
        /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModeration $plugin */
        $plugin = $workflow->getTypePlugin();
        foreach ($plugin->getEntityTypes() as $entity_type_id) {
          $this->moderatedEntityTypes[$entity_type_id] = $entity_types[$entity_type_id];
        }
      }
    }
    return $this->moderatedEntityTypes;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    // This needs to run after that EntityResolverManager has set the route
    // entity type.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];
    return $events;
  }

}
