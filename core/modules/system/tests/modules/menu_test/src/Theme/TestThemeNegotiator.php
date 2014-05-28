<?php

/**
 * @file
 * Contains \Drupal\menu_test\Theme\TestThemeNegotiator.
 */

namespace Drupal\menu_test\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the theme negotiation functionality.
 *
 * Retrieves the theme key of the theme to use for the current request based on
 * the theme name provided in the URL.
 */
class TestThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $request->attributes->has('inherited');
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(Request $request) {
    $argument = $request->attributes->get('inherited');
    // Test using the variable administrative theme.
    if ($argument == 'use-admin-theme') {
      return \Drupal::config('system.theme')->get('admin');
    }
    // Test using a theme that exists, but may or may not be enabled.
    elseif ($argument == 'use-stark-theme') {
      return 'stark';
    }
    // Test using a theme that does not exist.
    elseif ($argument == 'use-fake-theme') {
      return 'fake_theme';
    }
    // For any other value of the URL argument, do not return anything. This
    // allows us to test that returning nothing from a theme negotiation
    // causes the page to correctly fall back on using the main site theme.
  }

}
