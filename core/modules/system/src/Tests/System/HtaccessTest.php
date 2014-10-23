<?php

/**
 * @file
 * Contains Drupal\system\Tests\System\HtaccessTest
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests access restrictions provided by the default .htaccess file.
 *
 * @group system
 */
class HtaccessTest extends WebTestBase {

  /**
   * Tests accessing files with .yml extensions at various locations.
   */
  public function testYamlFileAccess() {
    // Try accessing the core services YAML file.
    $this->assertNoFileAccess('core/core.services.yml');
    // Try accessing a core module YAML file.
    $this->assertNoFileAccess('core/modules/system/system.services.yml');
  }

  /**
   * Asserts that a file exists but not accessible via HTTP.
   *
   * @param $path
   *   Path to file. Without leading slash.
   */
  protected function assertNoFileAccess($path) {
    $this->assertTrue(file_exists(DRUPAL_ROOT . '/' . $path));
    $this->drupalGet($path);
    $this->assertResponse(403);
  }

}
