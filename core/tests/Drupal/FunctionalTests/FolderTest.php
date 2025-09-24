<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests BrowserTestBase's treatment of hook_install() during setup.
 *
 * Image module is used for test.
 */
#[Group('browsertestbase')]
#[RunTestsInSeparateProcesses]
class FolderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
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
