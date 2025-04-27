<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel\PathExcluder;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\package_manager\PathExcluder\SiteConfigurationExcluder;
use Drupal\package_manager\PathLocator;
use Drupal\Tests\package_manager\Kernel\PackageManagerKernelTestBase;

/**
 * @covers \Drupal\package_manager\PathExcluder\SiteConfigurationExcluder
 * @group package_manager
 * @internal
 */
class SiteConfigurationExcluderTest extends PackageManagerKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    $container->getDefinition(SiteConfigurationExcluder::class)
      ->setClass(TestSiteConfigurationExcluder::class);
  }

  /**
   * Tests that certain paths are excluded from stage operations.
   */
  public function testExcludedPaths(): void {
    // In this test, we want to perform the actual stage operations so that we
    // can be sure that files are staged as expected.
    $this->setSetting('package_manager_bypass_composer_stager', FALSE);
    // Ensure we have an up-to-date container.
    $this->container = $this->container->get('kernel')->rebuildContainer();

    $active_dir = $this->container->get(PathLocator::class)->getProjectRoot();

    $site_path = 'sites/example.com';

    // Update the event subscribers' dependencies.
    $site_configuration_excluder = $this->container->get(SiteConfigurationExcluder::class);
    $site_configuration_excluder->sitePath = $site_path;

    $stage = $this->createStage();
    $stage->create();
    $stage->require(['ext-json:*']);
    $stage_dir = $stage->getSandboxDirectory();

    $excluded = [
      "$site_path/settings.php",
      "$site_path/settings.local.php",
      "$site_path/services.yml",
      // Default site-specific settings files should be excluded.
      'sites/default/settings.php',
      'sites/default/settings.local.php',
      'sites/default/services.yml',
    ];
    foreach ($excluded as $path) {
      $this->assertFileExists("$active_dir/$path");
      $this->assertFileDoesNotExist("$stage_dir/$path");
    }
    // A non-excluded file in the default site directory should be staged.
    $this->assertFileExists("$stage_dir/sites/default/stage.txt");
    // Regular module files should be staged.
    $this->assertFileExists("$stage_dir/modules/example/example.info.yml");

    // A new file added to the site directory in the stage directory should be
    // copied to the active directory.
    $file = "$stage_dir/sites/default/new.txt";
    touch($file);
    $stage->apply();
    $this->assertFileExists("$active_dir/sites/default/new.txt");

    // The excluded files should still be in the active directory.
    foreach ($excluded as $path) {
      $this->assertFileExists("$active_dir/$path");
    }
  }

  /**
   * Tests that `sites/default` is made writable in the stage directory.
   */
  public function testDefaultSiteDirectoryPermissions(): void {
    $project_root = $this->container->get(PathLocator::class)
      ->getProjectRoot();
    $live_dir = $project_root . '/sites/default';
    chmod($live_dir, 0555);
    $this->assertDirectoryIsNotWritable($live_dir);
    // Record the permissions of the directory now, so we can be sure those
    // permissions are restored after apply.
    $original_permissions = fileperms($live_dir);
    $this->assertIsInt($original_permissions);

    $stage = $this->createStage();
    $stage->create();
    // The staged `sites/default` will be made world-writable, because we want
    // to ensure the scaffold plugin can copy certain files into there.
    $staged_dir = str_replace($project_root, $stage->getSandboxDirectory(), $live_dir);
    $this->assertDirectoryIsWritable($staged_dir);

    $stage->require(['ext-json:*']);
    $stage->apply();
    // After applying, the live directory should NOT inherit the staged
    // directory's world-writable permissions.
    $this->assertSame($original_permissions, fileperms($live_dir));
  }

}

/**
 * A test version of the site configuration excluder, to expose internals.
 */
class TestSiteConfigurationExcluder extends SiteConfigurationExcluder {

  /**
   * The site path.
   */
  public string $sitePath;

}
