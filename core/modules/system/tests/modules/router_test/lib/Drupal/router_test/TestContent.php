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

}
