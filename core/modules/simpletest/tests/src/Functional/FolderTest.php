<?php

namespace Drupal\Tests\simpletest\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * This test will check SimpleTest's treatment of hook_install during setUp.
 * Image module is used for test.
 *
 * @group simpletest
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
    $this->assertTrue(file_prepare_directory($directory, FALSE), 'Directory created.');
  }

}
