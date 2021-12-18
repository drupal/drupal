<?php

namespace Drupal\system\EventSubscriber;

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
    $config_name = $event->getConfig()->getName();
    // Changing the site settings may mean a different route is selected for the
    // front page. Additionally a change to the site name or similar must
    // invalidate the render cache since this could be used anywhere.
    if ($config_name === 'system.site') {
      $this->cacheTagsInvalidator->invalidateTags(['route_match', 'rendered']);
    }

    // Theme configuration and global theme settings.
    if (in_array($config_name, ['system.theme', 'system.theme.global'], TRUE)) {
      $this->cacheTagsInvalidator->invalidateTags(['rendered']);
    }

    // Library and template overrides potentially change for the default theme
    // when the admin theme is changed.
    if ($config_name === 'system.theme' && $event->isChanged('admin')) {
      $this->cacheTagsInvalidator->invalidateTags(['library_info', 'theme_registry']);
    }

    // Theme-specific settings, check if this matches a theme settings
    // configuration object (THEME_NAME.settings), in that case, clear the
    // rendered cache tag.
    if (preg_match('/^([^\.]*)\.settings$/', $config_name, $matches)) {
      if ($this->themeHandler->themeExists($matches[1])) {
        $this->cacheTagsInvalidator->invalidateTags(['rendered']);
      }
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
