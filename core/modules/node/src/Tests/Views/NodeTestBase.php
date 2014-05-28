<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Views\NodeTestBase.
 */

namespace Drupal\node\Tests\Views;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Base class for all node tests.
 */
abstract class NodeTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node_test_views');

  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), array('node_test_views'));
  }

}
