<?php

declare(strict_types=1);

namespace Drupal\menu_test;

use Drupal\Component\Render\FormattableMarkup;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controllers for testing the menu integration routing system.
 */
class TestControllers {

  /**
   * Returns page to be used as a login path.
   */
  public function testLogin() {
    return ['#markup' => 'This is TestControllers::testLogin.'];
  }

  /**
   * Prints out test data.
   */
  public function test1() {
    return ['#markup' => 'test1'];
  }

  /**
   * Prints out test data.
   */
  public function test2() {
    return ['#markup' => 'test2'];
  }

  /**
   * Prints out test data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   Render array.
   */
  public function testSession(Request $request) {
    $counter = $request->getSession()->get('menu_test', 0);
    $request->getSession()->set('menu_test', ++$counter);
    return ['#markup' => new FormattableMarkup('Session menu_test is @count', ['@count' => $counter])];
  }

  /**
   * Prints out test data.
   */
  public function testDerived() {
    return ['#markup' => 'testDerived'];
  }

  /**
   * Prints out test data.
   *
   * @param string|null $placeholder
   *   A placeholder for the return string.
   *
   * @return string
   *   The string for this route.
   */
  public function testDefaults($placeholder = NULL) {
    if ($placeholder) {
      return ['#markup' => new FormattableMarkup("Sometimes there is a placeholder: '@placeholder'.", ['@placeholder' => $placeholder])];
    }
    else {
      return ['#markup' => 'Sometimes there is no placeholder.'];
    }
  }

  /**
   * Prints out test data with contextual links.
   */
  public function testContextual() {
    return [
      '#markup' => 'testContextual',
      'stuff' => [
        '#type' => 'contextual_links',
        '#contextual_links' => [
          'menu_test_menu' => [
            'route_parameters' => ['bar' => 1],
          ],
        ],
      ],
    ];
  }

}
