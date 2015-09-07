<?php

/**
 * @file
 * Contains \Drupal\menu_test\TestControllers.
 */

namespace Drupal\menu_test;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityInterface;

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
   */
  public function testSession() {
    if (!isset($_SESSION['menu_test'])) {
      $_SESSION['menu_test'] = 0;
    }
    $_SESSION['menu_test']++;
    return ['#markup' => SafeMarkup::format('Session menu_test is @count', ['@count' => $_SESSION['menu_test']])];
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
      return ['#markup' => SafeMarkup::format("Sometimes there is a placeholder: '@placeholder'.", array('@placeholder' => $placeholder))];
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
          ]
        ]
      ]
    ];
  }
}
