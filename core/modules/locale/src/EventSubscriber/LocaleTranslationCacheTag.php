<?php

namespace Drupal\locale\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\locale\LocaleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber invalidating cache tags when translating a string.
 */
class LocaleTranslationCacheTag implements EventSubscriberInterface {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a LocaleTranslationCacheTag object.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * Invalidate cache tags whenever a string is translated.
   */
  public function saveTranslation() {
    $this->cacheTagsInvalidator->invalidateTags(['rendered', 'locale', 'library_info']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[LocaleEvents::SAVE_TRANSLATION][] = ['saveTranslation'];
    return $events;
  }

}
