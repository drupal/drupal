<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\ThemeCacheContext.
 */

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Defines the ThemeCacheContext service, for "per theme" caching.
 *
 * Cache context ID: 'theme'.
 */
class ThemeCacheContext implements CacheContextInterface {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs a new ThemeCacheContext service.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(ThemeManagerInterface $theme_manager) {
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Theme');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->themeManager->getActiveTheme()->getName() ?: 'stark';
  }

}
