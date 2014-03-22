<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\LanguageCacheContext.
 */

namespace Drupal\Core\Cache;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Defines the ThemeCacheContext service, for "per theme" caching.
 */
class ThemeCacheContext implements CacheContextInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The theme negotiator.
   *
   * @var \Drupal\Core\Theme\ThemeNegotiator
   */
  protected $themeNegotiator;

  /**
   * Constructs a new ThemeCacheContext service.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   * @param \Drupal\Core\Theme\ThemeNegotiatorInterface $theme_negotiator
   *   The theme negotiator.
   */
  public function __construct(Request $request, ThemeNegotiatorInterface $theme_negotiator) {
    $this->request = $request;
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
    return $this->themeNegotiator->determineActiveTheme($this->request) ?: 'stark';
  }

}
