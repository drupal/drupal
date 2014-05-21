<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\LanguageCacheContext.
 */

namespace Drupal\Core\Cache;

use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Defines the ThemeCacheContext service, for "per theme" caching.
 */
class ThemeCacheContext implements CacheContextInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The theme negotiator.
   *
   * @var \Drupal\Core\Theme\ThemeNegotiator
   */
  protected $themeNegotiator;

  /**
   * Constructs a new ThemeCacheContext service.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Theme\ThemeNegotiatorInterface $theme_negotiator
   *   The theme negotiator.
   */
  public function __construct(RequestStack $request_stack, ThemeNegotiatorInterface $theme_negotiator) {
    $this->requestStack = $request_stack;
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
    $request = $this->requestStack->getCurrentRequest();
    return $this->themeNegotiator->determineActiveTheme($request) ?: 'stark';
  }

}
