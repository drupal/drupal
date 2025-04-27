<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel\PathExcluder;

use Drupal\package_manager\PathLocator;
use Drupal\Tests\package_manager\Kernel\PackageManagerKernelTestBase;

/**
 * @covers \Drupal\package_manager\PathExcluder\NodeModulesExcluder
 * @group package_manager
 * @internal
 */
class NodeModulesExcluderTest extends PackageManagerKernelTestBase {

  /**
   * Tests that node_modules directories are excluded from stage operations.
   */
  public function testExcludedPaths(): void {
    // In this test, we want to perform the actual stage operations so that we
    // can be sure that files are staged as expected.
    $this->setSetting('package_manager_bypass_composer_stager', FALSE);
    // Ensure we have an up-to-date container.
    $this->container = $this->container->get('kernel')->rebuildContainer();

    $active_dir = $this->container->get(PathLocator::class)
      ->getProjectRoot();
    $excluded = [
      "core/node_modules/exclude.txt",
      'modules/example/node_modules/exclude.txt',
    ];
    foreach ($excluded as $path) {
      mkdir(dirname("$active_dir/$path"), 0777, TRUE);
      file_put_contents("$active_dir/$path", "This file should never be staged.");
    }

    $stage = $this->createStage();
    $stage->create();
    $stage->require(['ext-json:*']);
    $stage_dir = $stage->getSandboxDirectory();

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

}
