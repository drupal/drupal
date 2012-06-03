<?php

/**
 * @file
 * Definition of Drupal\path\Tests\PathTestBase.
 */

namespace Drupal\path\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides a base class for testing the Path module.
 */
class PathTestBase extends WebTestBase {
  function setUp() {
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    $modules[] = 'node';
    $modules[] = 'path';
    parent::setUp($modules);

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
  }
}
