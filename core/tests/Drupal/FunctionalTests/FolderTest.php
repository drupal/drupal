<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests BrowserTestBase's treatment of hook_install() during setup.
 *
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
  protected static $modules = ['image'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testFolderSetup(): void {
    $directory = 'public://styles';
    $this->assertTrue(\Drupal::service('file_system')->prepareDirectory($directory, FALSE), 'Directory created.');
  }

}
