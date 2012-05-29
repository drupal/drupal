<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchTestBase.
 */

namespace Drupal\search\Tests;

use Drupal\simpletest\WebTestBase;

class SearchTestBase extends WebTestBase {
  function setUp() {
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    $modules[] = 'node';
    $modules[] = 'search';
    $modules[] = 'dblog';
    parent::setUp($modules);

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
  }
}
