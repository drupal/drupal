<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel\PathExcluder;

use Drupal\package_manager\PathLocator;
use Drupal\Tests\package_manager\Kernel\PackageManagerKernelTestBase;

/**
 * @covers \Drupal\package_manager\PathExcluder\SiteFilesExcluder
 * @group package_manager
 * @internal
 */
class SiteFilesExcluderTest extends PackageManagerKernelTestBase {

  /**
   * Tests that public and private files are excluded from stage operations.
   */
  public function testSiteFilesExcluded(): void {
    // The private stream wrapper is only registered if this setting is set.
    // @see \Drupal\Core\CoreServiceProvider::register()
    $this->setSetting('file_private_path', 'private');
    // In this test, we want to perform the actual stage operations so that we
    // can be sure that files are staged as expected. This will also rebuild
    // the container, enabling the private stream wrapper.
    $this->setSetting('package_manager_bypass_composer_stager', FALSE);
    // Ensure we have an up-to-date container.
    $this->container = $this->container->get('kernel')->rebuildContainer();

    $active_dir = $this->container->get(PathLocator::class)->getProjectRoot();

    // Ensure that we are using directories within the fake site fixture for
    // public and private files.
    $this->setSetting('file_public_path', "sites/example.com/files");

    $stage = $this->createStage();
    $stage->create();
    $stage->require(['ext-json:*']);
    $stage_dir = $stage->getSandboxDirectory();

    $excluded = [
      "sites/example.com/files/exclude.txt",
      'private/exclude.txt',
    ];
    foreach ($excluded as $path) {
      $this->assertFileExists("$active_dir/$path");
      $this->assertFileDoesNotExist("$stage_dir/$path");
    }

    $stage->apply();
    // The excluded files should still be in the active directory.
    foreach ($excluded as $path) {
      $this->assertFileExists("$active_dir/$path");
    }
  }

  /**
   * Tests that invalid file settings do not cause errors.
   */
  public function testInvalidFileSettings(): void {
    $invalid_path = '/path/does/not/exist';
    $this->assertFileDoesNotExist($invalid_path);
    $this->setSetting('file_public_path', $invalid_path);
    $this->setSetting('file_private_path', $invalid_path);
    // Ensure we have an up-to-date container.
    $this->container = $this->container->get('kernel')->rebuildContainer();
    $this->assertStatusCheckResults([]);
    $this->assertResults([]);
  }

}
