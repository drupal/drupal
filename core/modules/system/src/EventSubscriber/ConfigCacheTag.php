<?php

/**
 * @file
 * Contains \Drupal\system\EventSubscriber\ConfigCacheTag.
 */

namespace Drupal\system\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber invalidating cache tags when system config objects are saved.
 */
class ConfigCacheTag implements EventSubscriberInterface {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a ConfigCacheTag object.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->themeHandler = $theme_handler;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * Invalidate cache tags when particular system config objects are saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onSave(ConfigCrudEvent $event) {
    // Changing the site settings may mean a different route is selected for the
    // front page. Additionally a change to the site name or similar must
    // invalidate the render cache since this could be used anywhere.
    if ($event->getConfig()->getName() === 'system.site') {
      $this->cacheTagsInvalidator->invalidateTags(['route_match', 'rendered']);
    }

    // Global theme settings.
    if ($event->getConfig()->getName() === 'system.theme.global') {
      $this->cacheTagsInvalidator->invalidateTags(['rendered']);
    }

    // Theme-specific settings, check if this matches a theme settings
    // configuration object, in that case, clear the rendered cache tag.
    foreach (array_keys($this->themeHandler->listInfo()) as $theme_name) {
      if ($theme_name == $event->getConfig()->getName()) {
        $this->cacheTagsInvalidator->invalidateTags(['rendered']);
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onSave'];
    return $events;
  }

}
