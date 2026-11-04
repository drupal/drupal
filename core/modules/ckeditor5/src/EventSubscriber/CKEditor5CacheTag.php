<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber invalidating cache tags when the default theme changes.
 *
 * @internal
 *   This class may change at any time. It is not for use outside this module.
 */
class CKEditor5CacheTag implements EventSubscriberInterface {

  /**
   * Constructs a CKEditor5CacheTag object.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   */
  public function __construct(
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {
  }

  /**
   * Invalidates cache tags when particular system config objects are saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onSave(ConfigCrudEvent $event) {
    $config_name = $event->getConfig()->getName();

    // Ckeditor5-stylesheets settings may change when the default theme changes.
    if ($config_name === 'system.theme' && $event->isChanged('default')) {
      // @see ckeditor5_library_info_alter()
      $this->cacheTagsInvalidator->invalidateTags(['library_info']);
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
