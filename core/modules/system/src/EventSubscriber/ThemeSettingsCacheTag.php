<?php

/**
 * @file
 * Contains \Drupal\system\EventSubscriber\ThemeSettingsCacheTag.
 */

namespace Drupal\system\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber invalidating the 'rendered' cache tag when saving theme settings.
 */
class ThemeSettingsCacheTag implements EventSubscriberInterface {

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
   * Constructs a ThemeSettingsCacheTag object.
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
   * Invalidate the 'rendered' cache tag whenever a theme setting is modified.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onSave(ConfigCrudEvent $event) {
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
