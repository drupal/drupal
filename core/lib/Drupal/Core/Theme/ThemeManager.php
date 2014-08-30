<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeManager.
 */

namespace Drupal\Core\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\StackedRouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the default implementation of a theme manager.
 */
class ThemeManager implements ThemeManagerInterface {

  /**
   * The theme negotiator.
   *
   * @var \Drupal\Core\Theme\ThemeNegotiatorInterface
   */
  protected $themeNegotiator;

  /**
   * The theme registry used to render an output.
   *
   * @var \Drupal\Core\Theme\Registry
   */
  protected $themeRegistry;

  /**
   * Contains the current active theme.
   *
   * @var \Drupal\Core\Theme\ActiveTheme
   */
  protected $activeTheme;

  /**
   * @var \Drupal\Core\Theme\ThemeInitialization
   */
  protected $themeInitialization;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new ThemeManager object.
   *
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   * @param \Drupal\Core\Theme\ThemeNegotiatorInterface $theme_negotiator
   *   The theme negotiator.
   * @param \Drupal\Core\Theme\ThemeInitialization $theme_initialization
   *   The theme initialization.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(Registry $theme_registry, ThemeNegotiatorInterface $theme_negotiator, ThemeInitialization $theme_initialization, RequestStack $request_stack) {
    $this->themeNegotiator = $theme_negotiator;
    $this->themeRegistry = $theme_registry;
    $this->themeInitialization = $theme_initialization;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function render($hook, array $variables) {
    return _theme($hook, $variables);
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveTheme(RouteMatchInterface $route_match = NULL) {
    if (!isset($this->activeTheme)) {
      $this->initTheme($route_match);
    }
    return $this->activeTheme;
  }

  /**
   * {@inheritdoc}
   */
  public function hasActiveTheme() {
    return isset($this->activeTheme);
  }

  /**
   * {@inheritdoc}
   */
  public function resetActiveTheme() {
    $this->activeTheme = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveTheme(ActiveTheme $active_theme) {
    $this->activeTheme = $active_theme;
    if ($active_theme) {
      $this->themeInitialization->loadActiveTheme($active_theme);
    }
    return $this;
  }

  /**
   * Initializes the active theme for a given route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  protected function initTheme(RouteMatchInterface $route_match = NULL) {
    // Determine the active theme for the theme negotiator service. This includes
    // the default theme as well as really specific ones like the ajax base theme.
    if (!$route_match) {
      $route_match = \Drupal::routeMatch();
    }
    if ($route_match instanceof StackedRouteMatchInterface) {
      $route_match = $route_match->getMasterRouteMatch();
    }
    $theme = $this->themeNegotiator->determineActiveTheme($route_match);
    $this->activeTheme = $this->themeInitialization->initTheme($theme);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Should we cache some of these information?
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    // Most of the time, $type is passed as a string, so for performance,
    // normalize it to that. When passed as an array, usually the first item in
    // the array is a generic type, and additional items in the array are more
    // specific variants of it, as in the case of array('form', 'form_FORM_ID').
    if (is_array($type)) {
      $extra_types = $type;
      $type = array_shift($extra_types);
      // Allow if statements in this function to use the faster isset() rather
      // than !empty() both when $type is passed as a string, or as an array with
      // one item.
      if (empty($extra_types)) {
        unset($extra_types);
      }
    }

    $theme_keys = array();
    $theme = $this->getActiveTheme();
    foreach ($theme->getBaseThemes() as $base) {
      $theme_keys[] = $base->getName();
    }

    $theme_keys[] = $theme->getName();
    $functions = array();
    foreach ($theme_keys as $theme_key) {
      $function = $theme_key . '_' . $type . '_alter';
      if (function_exists($function)) {
        $functions[] = $function;
      }
      if (isset($extra_types)) {
        foreach ($extra_types as $extra_type) {
          $function = $theme_key . '_' . $extra_type . '_alter';
          if (function_exists($function)) {
            $functions[] = $function;
          }
        }
      }
    }

    foreach ($functions as $function) {
      $function($data, $context1, $context2);
    }
  }

}
