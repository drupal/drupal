<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\ThemeNegotiator.
 */

namespace Drupal\Core\Theme;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a class which determines the active theme of the page.
 *
 * It therefore uses ThemeNegotiatorInterface objects which are passed in
 * using the 'theme_negotiator' tag.
 *
 * @see \Drupal\Core\Theme\ThemeNegotiatorPass
 * @see \Drupal\Core\Theme\ThemeNegotiatorInterface
 */
class ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * Holds arrays of theme negotiators, keyed by priority.
   *
   * @var array
   */
  protected $negotiators = array();

  /**
   * Holds the array of theme negotiators sorted by priority.
   *
   * Set to NULL if the array needs to be re-calculated.
   *
   * @var array|NULL
   */
  protected $sortedNegotiators;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The access checker for themes.
   *
   * @var \Drupal\Core\Theme\ThemeAccessCheck
   */
  protected $themeAccess;

  /**
   * Constructs a new ThemeNegotiator.
   *
   * @param \Drupal\Core\Theme\ThemeAccessCheck $theme_access
   *   The access checker for themes.
   */
  public function __construct(ThemeAccessCheck $theme_access) {
    $this->themeAccess = $theme_access;
  }

  /**
   * Sets the request object to use.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * Adds a active theme negotiation service.
   *
   * @param \Drupal\Core\Theme\ThemeNegotiatorInterface $negotiator
   *   The theme negotiator to add.
   * @param int $priority
   *   Priority of the theme negotiator.
   */
  public function addNegotiator(ThemeNegotiatorInterface $negotiator, $priority) {
    $this->negotiators[$priority][] = $negotiator;
    // Force the negotiators to be re-sorted.
    $this->sortedNegotiators = NULL;
  }

  /**
   * Returns the sorted array of theme negotiators.
   *
   * @return array|\Drupal\Core\Theme\ThemeNegotiatorInterface[]
   *   An array of theme negotiator objects.
   */
  protected function getSortedNegotiators() {
    if (!isset($this->sortedNegotiators)) {
      // Sort the negotiators according to priority.
      krsort($this->negotiators);
      // Merge nested negotiators from $this->negotiators into
      // $this->sortedNegotiators.
      $this->sortedNegotiators = array();
      foreach ($this->negotiators as $builders) {
        $this->sortedNegotiators = array_merge($this->sortedNegotiators, $builders);
      }
    }
    return $this->sortedNegotiators;
  }

  /**
   * Get the current active theme.
   *
   * @return string
   *   The current active string.
   */
  public function getActiveTheme() {
    if (!$this->request->attributes->has('_theme_active')) {
      $this->determineActiveTheme($this->request);
    }
    return $this->request->attributes->get('_theme_active');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(Request $request) {
    foreach ($this->getSortedNegotiators() as $negotiator) {
      if ($negotiator->applies($request)) {
        $theme = $negotiator->determineActiveTheme($request);
        if ($theme !== NULL && $this->themeAccess->checkAccess($theme)) {
          $request->attributes->set('_theme_active', $theme);
          return $request->attributes->get('_theme_active');
        }
      }
    }
  }

}
