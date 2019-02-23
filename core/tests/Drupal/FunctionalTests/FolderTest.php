<?php

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;

/**
 * This test will check BrowserTestBase's treatment of hook_install during
 * setUp.
 * Image module is used for test.
 *
 * @group browsertestbase
 */
class FolderTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['image'];

  public function testFolderSetup() {
    $directory = file_default_scheme() . '://styles';
    $this->assertTrue(\Drupal::service('file_system')->prepareDirectory($directory, FALSE), 'Directory created.');
  }

}
