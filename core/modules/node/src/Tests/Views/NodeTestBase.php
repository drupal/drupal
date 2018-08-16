<?php

namespace Drupal\node\Tests\Views;

@trigger_error('\Drupal\node\Tests\Views\NodeTestBase is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\node\Functional\Views\NodeTestBase', E_USER_DEPRECATED);

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Base class for all node tests.
 *
 * @deprecated Scheduled for removal before Drupal 9.0.0.
 *   Use \Drupal\Tests\node\Functional\Views\NodeTestBase instead.
 */
abstract class NodeTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node_test_views'];

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    if ($import_test_views) {
      ViewTestData::createTestViews(get_class($this), ['node_test_views']);
    }
  }

}
