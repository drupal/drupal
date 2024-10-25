<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel\PathExcluder;

use Drupal\Core\Serialization\Yaml;
use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\package_manager\PathLocator;
use Drupal\Tests\package_manager\Kernel\PackageManagerKernelTestBase;
use Drupal\Tests\package_manager\Traits\ComposerInstallersTrait;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Drupal\package_manager\PathExcluder\GitExcluder
 * @group package_manager
 * @internal
 */
class GitExcluderTest extends PackageManagerKernelTestBase {

  use ComposerInstallersTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $project_root = $this->container->get(PathLocator::class)->getProjectRoot();
    $this->installComposerInstallers($project_root);
    $active_manipulator = new ActiveFixtureManipulator();
    $active_manipulator
      ->addPackage([
        'name' => 'foo/package_known_to_composer_removed_later',
        'type' => 'drupal-module',
        'version' => '1.0.0',
      ], FALSE, TRUE)
      ->addPackage([
        'name' => 'foo/custom_package_known_to_composer',
        'type' => 'drupal-custom-module',
        'version' => '1.0.0',
      ], FALSE, TRUE)
      ->addPackage([
        'name' => 'foo/package_with_different_installer_path_known_to_composer',
        'type' => 'drupal-module',
        'version' => '1.0.0',
      ], FALSE, TRUE);
    // Set the installer path config in the project root where we install the
    // package.
    $installer_paths['different_installer_path/package_known_to_composer'] = ['foo/package_with_different_installer_path_known_to_composer'];
    $this->setInstallerPaths($installer_paths, $project_root);
    $active_manipulator->addProjectAtPath("modules/module_not_known_to_composer_in_active")
      ->addDotGitFolder($project_root . "/modules/module_not_known_to_composer_in_active")
      ->addDotGitFolder($project_root . "/modules/contrib/package_known_to_composer_removed_later")
      ->addDotGitFolder($project_root . "/modules/custom/custom_package_known_to_composer")
      ->addDotGitFolder($project_root . "/different_installer_path/package_known_to_composer")
      ->commitChanges();
  }

  /**
   * Tests that Git directories are excluded from stage during PreCreate.
   */
  public function testGitDirectoriesExcludedActive(): void {
    // Ensure we have an up-to-date container.
    $this->container = $this->container->get('kernel')->rebuildContainer();

    $stage = $this->createStage();
    $stage->create();
    /** @var \Drupal\package_manager_bypass\LoggingBeginner $beginner */
    $beginner = $this->container->get(BeginnerInterface::class);
    $beginner_args = $beginner->getInvocationArguments();
    $excluded_paths = [
      '.git',
      'modules/module_not_known_to_composer_in_active/.git',
      'modules/example/.git',
    ];
    foreach ($excluded_paths as $excluded_path) {
      $this->assertContains($excluded_path, $beginner_args[0][2]);
    }
    $not_excluded_paths = [
      'modules/contrib/package_known_to_composer_removed_later/.git',
      'modules/custom/custom_package_known_to_composer/.git',
      'different_installer_path/package_known_to_composer/.git',
    ];
    foreach ($not_excluded_paths as $not_excluded_path) {
      $this->assertNotContains($not_excluded_path, $beginner_args[0][2]);
    }
  }

  /**
   * Tests that Git directories are excluded from active during PreApply.
   */
  public function testGitDirectoriesExcludedStage(): void {
    // Ensure we have an up-to-date container.
    $this->container = $this->container->get('kernel')->rebuildContainer();

    $this->getStageFixtureManipulator()
      ->removePackage('foo/package_known_to_composer_removed_later');

    $stage = $this->createStage();
    $stage->create();
    $stage->require(['ext-json:*']);
    $stage_dir = $stage->getStageDirectory();

    // Adding a module with .git in stage which is unknown to composer, we
    // expect it to not be copied to the active directory.
    $path = "$stage_dir/modules/unknown_to_composer_in_stage";
    $fs = new Filesystem();
    $fs->mkdir("$path/.git");
    file_put_contents(
      "$path/unknown_to_composer.info.yml",
      Yaml::encode([
        'name' => 'Unknown to composer in stage',
        'type' => 'module',
        'core_version_requirement' => '^9.7 || ^10',
      ])
    );
    file_put_contents("$path/.git/excluded.txt", 'Phoenix!');

    $stage->apply();
    /** @var \Drupal\package_manager_bypass\LoggingCommitter $committer */
    $committer = $this->container->get(CommitterInterface::class);
    $committer_args = $committer->getInvocationArguments();
    $excluded_paths = [
      '.git',
      'modules/module_not_known_to_composer_in_active/.git',
      'modules/example/.git',
    ];
    // We are missing "modules/unknown_to_composer_in_stage/.git" in excluded
    // paths because there is no validation for it as it is assumed about any
    // new .git folder in stage directory that either composer is aware of it or
    // the developer knows what they are doing.
    foreach ($excluded_paths as $excluded_path) {
      $this->assertContains($excluded_path, $committer_args[0][2]);
    }
    $this->assertNotContains('modules/unknown_to_composer_in_stage/.git', $committer_args[0][2]);
  }

}
