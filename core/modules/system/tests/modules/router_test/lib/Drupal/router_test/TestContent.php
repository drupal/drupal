<?php

/**
 * @file
 * Contains \Drupal\router_test\TestContent.
 */

namespace Drupal\router_test;

/**
 * Test controllers that are intended to be wrapped in a main controller.
 */
class TestContent {

  /**
   * Provides example content for testing route enhancers.
   */
  public function test1() {
    return 'abcde';
  }

  /**
   * Provides example content for route specific authentication.
   *
   * @returns string
   *   The user name of the current logged in user.
   */
  public function test11() {
    $account  = \Drupal::request()->attributes->get('_account');
    return $account->getUsername();
  }

}
