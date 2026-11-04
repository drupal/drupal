<?php

declare(strict_types=1);

namespace Drupal\menu_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Controller routines for menu_test routes.
 */
class MenuTestController extends ControllerBase {

  /**
   * Constructs the MenuTestController object.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\Core\Theme\ThemeNegotiatorInterface $themeNegotiator
   *   The theme negotiator.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(
    protected ThemeManagerInterface $themeManager,
    protected ThemeNegotiatorInterface $themeNegotiator,
    protected RouteMatchInterface $routeMatch,
  ) {
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
  public function titleCallback(array $_title_arguments = [], $_title = '') {
    $_title_arguments += ['case_number' => '2', 'title' => $_title];
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    return $this->t($_title_arguments['title']) . ' - Case ' . $_title_arguments['case_number'];
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
   *   A string that can be used for comparison.
   */
  public function breadcrumbTitleCallback() {
    return '<script>alert(123);</script>';
  }

}
