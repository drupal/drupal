<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTestBase.
 */

namespace Drupal\node\Tests;

use Drupal\simpletest\WebTestBase;

class NodeTestBase extends WebTestBase {
  function setUp() {
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    $modules[] = 'node';
    parent::setUp($modules);

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
  }
}
