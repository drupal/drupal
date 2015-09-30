<?php

/**
 * @file
 * Contains \Drupal\menu_test\Controller\MenuTestController.
 */

namespace Drupal\menu_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for menu_test routes.
 */
class MenuTestController extends ControllerBase {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme negotiator.
   *
   * @var \Drupal\Core\Theme\ThemeNegotiatorInterface
   */
  protected $themeNegotiator;

  /**
   * The active route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs the MenuTestController object.
   *
   * @param \Drupal\menu_test\Controller\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\menu_test\Controller\ThemeNegotiatorInterface $theme_negotiator
   *   The theme negotiator.
   * @param \Drupal\menu_test\Controller\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(ThemeManagerInterface $theme_manager, ThemeNegotiatorInterface $theme_negotiator, RouteMatchInterface $route_match) {
    $this->themeManager = $theme_manager;
    $this->themeNegotiator = $theme_negotiator;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme.manager'),
      $container->get('theme.negotiator'),
      $container->get('current_route_match')
    );
  }

  /**
   * Some known placeholder content which can be used for testing.
   *
   * @return string
   *   A string that can be used for comparison.
   */
  public function menuTestCallback() {
    return ['#markup' => 'This is the menuTestCallback content.'];
  }


  /**
   * A title callback method for test routes.
   *
   * @param array $_title_arguments
   *   Optional array from the route defaults.
   * @param string $_title
   *   Optional _title string from the route defaults.
   *
   * @return string
   *   The route title.
   */
  public function titleCallback(array $_title_arguments = array(), $_title = '') {
    $_title_arguments += array('case_number' => '2', 'title' => $_title);
    return t($_title_arguments['title']) . ' - Case ' . $_title_arguments['case_number'];
  }

  /**
   * Page callback: Tests the theme negotiation functionality.
   *
   * @param bool $inherited
   *   TRUE when the requested page is intended to inherit
   *   the theme of its parent.
   *
   * @return string
   *   A string describing the requested custom theme and actual
   *   theme being used
   *   for the current page request.
   */
  public function themePage($inherited) {
    $theme_key = $this->themeManager->getActiveTheme()->getName();
    // Now we check what the theme negotiator service returns.
    $active_theme = $this->themeNegotiator
      ->determineActiveTheme($this->routeMatch);
    $output = "Active theme: $active_theme. Actual theme: $theme_key.";
    if ($inherited) {
      $output .= ' Theme negotiation inheritance is being tested.';
    }
    return ['#markup' => $output];
  }

  /**
   * A title callback for XSS breadcrumb check.
   *
   * @return string
   */
  public function breadcrumbTitleCallback() {
    return '<script>alert(123);</script>';
  }

}
