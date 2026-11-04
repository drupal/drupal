<?php

namespace Drupal\media\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the config save event for media.settings.
 */
class MediaConfigSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the MediaConfigSubscriber.
   *
   * @param \Drupal\Core\Routing\RouteBuilderInterface $routeBuilder
   *   The route builder.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected RouteBuilderInterface $routeBuilder,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Updates entity type definitions and ensures routes are rebuilt when needed.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The ConfigCrudEvent to process.
   */
  public function onSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() === 'media.settings' && $event->isChanged('standalone_url')) {
      $this->cacheTagsInvalidator->invalidateTags([
        // The configuration change triggers entity type definition changes,
        // which in turn triggers routes to appear or disappear.
        // @see media_entity_type_alter()
        'entity_types',
        // The 'rendered' cache tag needs to be explicitly invalidated to ensure
        // that all links to Media entities are re-rendered. Ideally, this would
        // not be necessary; invalidating the 'entity_types' cache tag should be
        // sufficient. But that cache tag would then need to be on nearly
        // everything, resulting in excessive complexity. We prefer pragmatism.
        'rendered',
      ]);
      // @todo Remove this when invalidating the 'entity_types' cache tag is
      // respected by the entity type plugin manager. See
      // https://www.drupal.org/project/drupal/issues/3001284 and
      // https://www.drupal.org/project/drupal/issues/3013659.
      $this->entityTypeManager->clearCachedDefinitions();
      $this->routeBuilder->setRebuildNeeded();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::SAVE][] = ['onSave'];
    return $events;
  }

}
