<?php

namespace Drupal\system\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\Registry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber invalidating cache tags when system config objects are saved.
 */
class ConfigCacheTag implements EventSubscriberInterface {

  /**
   * Constructs a ConfigCacheTag object.
   */
  public function __construct(
    protected ThemeHandlerInterface $themeHandler,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected Registry $themeRegistry,
  ) {
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
      $this->themeRegistry->reset();
      $this->cacheTagsInvalidator->invalidateTags(['library_info']);
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
