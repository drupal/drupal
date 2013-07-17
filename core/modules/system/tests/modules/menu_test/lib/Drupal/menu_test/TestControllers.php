<?php

/**
 * @file
 * Contains \Drupal\menu_test\TestControllers.
 */

namespace Drupal\menu_test;

use Drupal\Core\Entity\EntityInterface;

/**
 * Controllers for testing the menu integration routing system.
 */
class TestControllers {

  /**
   * Returns page to be used as a login path.
   */
  public function testLogin() {
    return 'This is TestControllers::testLogin.';
  }

  /**
   * Prints out test data.
   */
  public function test1() {
    return 'test1';
  }

  /**
   * Prints out test data.
   */
  public function test2() {
    return 'test2';
  }

}
