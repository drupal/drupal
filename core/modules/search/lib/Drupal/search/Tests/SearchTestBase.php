<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchTestBase.
 */

namespace Drupal\search\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Defines the common search test code.
 */
abstract class SearchTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'search', 'dblog');

  function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
  }
}
