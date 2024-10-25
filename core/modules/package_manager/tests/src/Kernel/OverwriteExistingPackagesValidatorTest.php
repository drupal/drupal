<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\Event\PostCreateEvent;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\ValidationResult;
use Drupal\package_manager\Validator\SupportedReleaseValidator;
use Drupal\Tests\package_manager\Traits\ComposerInstallersTrait;

/**
 * @covers \Drupal\package_manager\Validator\OverwriteExistingPackagesValidator
 * @group package_manager
 * @internal
 */
class OverwriteExistingPackagesValidatorTest extends PackageManagerKernelTestBase {

  use ComposerInstallersTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // In this test, we don't care whether the updated projects are secure and
    // supported.
    $this->disableValidators[] = SupportedReleaseValidator::class;
    parent::setUp();

    $this->installComposerInstallers($this->container->get(PathLocator::class)->getProjectRoot());
  }

  /**
   * Tests that new installed packages overwrite existing directories.
   *
   * The fixture simulates a scenario where the active directory has four
   * modules installed: module_1, module_2, module_5 and module_6. None of them
   * are managed by Composer. These modules will be moved into the stage
   * directory by the 'package_manager_bypass' module.
   */
  public function testNewPackagesOverwriteExisting(): void {
    (new ActiveFixtureManipulator())
      ->addProjectAtPath('modules/module_1')
      ->addProjectAtPath('modules/module_2')
      ->addProjectAtPath('modules/module_5')
      ->addProjectAtPath('modules/module_6')
      ->commitChanges();
    $stage_manipulator = $this->getStageFixtureManipulator();

    $installer_paths = [];
    // module_1 and module_2 will raise errors because they would overwrite
    // non-Composer managed paths in the active directory.
    $stage_manipulator->addPackage(
      [
        'name' => 'drupal/other_module_1',
        'version' => '1.3.0',
        'type' => 'drupal-module',
      ],
      FALSE,
      TRUE
    );
    $installer_paths['modules/module_1'] = ['drupal/other_module_1'];
    $stage_manipulator->addPackage(
      [
        'name' => 'drupal/other_module_2',
        'version' => '1.3.0',
        'type' => 'drupal-module',
      ],
      FALSE,
      TRUE,
    );
    $installer_paths['modules/module_2'] = ['drupal/other_module_2'];

    // module_3 will cause no problems, since it doesn't exist in the active
    // directory at all.
    $stage_manipulator->addPackage([
      'name' => 'drupal/other_module_3',
      'version' => '1.3.0',
      'type' => 'drupal-module',
    ],
    FALSE,
        TRUE,
    );
    $installer_paths['modules/module_3'] = ['drupal/other_module_3'];

    // module_4 doesn't exist in the active directory but the 'install_path' as
    // known to Composer in the staged directory collides with module_6 in the
    // active directory which will cause an error.
    $stage_manipulator->addPackage(
      [
        'name' => 'drupal/module_4',
        'version' => '1.3.0',
        'type' => 'drupal-module',
      ],
      FALSE,
      TRUE
    );
    $installer_paths['modules/module_6'] = ['drupal/module_4'];

    // module_5_different_path will not cause a problem, even though its package
    // name is drupal/module_5, because its project name and path in the stage
    // directory differ from the active directory.
    $stage_manipulator->addPackage(
      [
        'name' => 'drupal/other_module_5',
        'version' => '1.3.0',
        'type' => 'drupal-module',
      ],
      FALSE,
      TRUE
    );
    $installer_paths['modules/module_5_different_path'] = ['drupal/other_module_5'];

    // Set the installer path config in the active directory this will be
    // copied to the stage directory where we install the packages.
    $this->setInstallerPaths($installer_paths, $this->container->get(PathLocator::class)->getProjectRoot());

    // Add a package without an install_path set which will not raise an error.
    // The most common example of this in the Drupal ecosystem is a submodule.
    $stage_manipulator->addPackage(
      [
        'name' => 'drupal/sub-module',
        'version' => '1.3.0',
        'type' => 'metapackage',
      ],
      FALSE,
      TRUE
    );
    $inspector = $this->container->get(ComposerInspector::class);
    $listener = function (PostCreateEvent $event) use ($inspector) {
      $list = $inspector->getInstalledPackagesList($event->stage->getStageDirectory());
      $this->assertArrayHasKey('drupal/sub-module', $list->getArrayCopy());
      $this->assertArrayHasKey('drupal/other_module_1', $list->getArrayCopy());
      // Confirm that metapackage will have a NULL install path.
      $this->assertNull($list['drupal/sub-module']->path);
      // Confirm another package has specified install path.
      $this->assertSame($list['drupal/other_module_1']->path, $event->stage->getStageDirectory() . '/modules/module_1');
    };
    $this->addEventTestListener($listener, PostCreateEvent::class);

    $expected_results = [
      ValidationResult::createError([
        t('The new package drupal/module_4 will be installed in the directory /modules/module_6, which already exists but is not managed by Composer.'),
      ]),
      ValidationResult::createError([
        t('The new package drupal/other_module_1 will be installed in the directory /modules/module_1, which already exists but is not managed by Composer.'),
      ]),
      ValidationResult::createError([
        t('The new package drupal/other_module_2 will be installed in the directory /modules/module_2, which already exists but is not managed by Composer.'),
      ]),
    ];
    $this->assertResults($expected_results, PreApplyEvent::class);
  }

}
