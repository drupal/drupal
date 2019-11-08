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

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testFolderSetup() {
    $directory = 'public://styles';
    $this->assertTrue(\Drupal::service('file_system')->prepareDirectory($directory, FALSE), 'Directory created.');
  }

}
