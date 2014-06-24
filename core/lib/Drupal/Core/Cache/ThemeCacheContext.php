<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\LanguageCacheContext.
 */

namespace Drupal\Core\Cache;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Defines the ThemeCacheContext service, for "per theme" caching.
 */
class ThemeCacheContext implements CacheContextInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatch
   */
  protected $routeMatch;

  /**
   * The theme negotiator.
   *
   * @var \Drupal\Core\Theme\ThemeNegotiator
   */
  protected $themeNegotiator;

  /**
   * Constructs a new ThemeCacheContext service.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Theme\ThemeNegotiatorInterface $theme_negotiator
   *   The theme negotiator.
   */
  public function __construct(RouteMatchInterface $route_match, ThemeNegotiatorInterface $theme_negotiator) {
    $this->routeMatch = $route_match;
    $this->themeNegotiator = $theme_negotiator;
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
    return $this->themeNegotiator->determineActiveTheme($this->routeMatch) ?: 'stark';
  }

}
