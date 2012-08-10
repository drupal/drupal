<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTestBase.
 */

namespace Drupal\node\Tests;

use Drupal\simpletest\WebTestBase;

abstract class NodeTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
  }
}
