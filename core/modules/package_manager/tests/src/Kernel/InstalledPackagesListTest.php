<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\package_manager\InstalledPackage;
use Drupal\package_manager\InstalledPackagesList;
use Drupal\package_manager\PathLocator;

/**
 * @coversDefaultClass \Drupal\package_manager\InstalledPackagesList
 *
 * @group package_manager
 */
class InstalledPackagesListTest extends PackageManagerKernelTestBase {

  /**
   * @covers \Drupal\package_manager\InstalledPackage::getProjectName
   * @covers ::getPackageByDrupalProjectName
   */
  public function testPackageByDrupalProjectName(): void {
    // In getPackageByDrupalProjectName(), we don't expect that projects will be
    // in the "correct" places -- for example, we don't assume that modules will
    // be in the `modules` directory, or themes will be the `themes` directory.
    // So, in this test, we ensure that flexibility works by just throwing all
    // the projects into a single `projects` directory.
    $projects_path = $this->container->get(PathLocator::class)
      ->getProjectRoot() . '/projects';

    // The project name does not match the package name, and the project
    // physically exists.
    (new ActiveFixtureManipulator())
      ->addProjectAtPath('projects/theme_project')
      ->commitChanges();
    $list = new InstalledPackagesList([
      'drupal/a_package' => InstalledPackage::createFromArray([
        'name' => 'drupal/a_package',
        'version' => '1.0.0',
        'type' => 'drupal-theme',
        'path' => $projects_path . '/theme_project',
      ]),
    ]);
    $this->assertSame($list['drupal/a_package'], $list->getPackageByDrupalProjectName('theme_project'));

    // The project physically exists, but the package path points to the wrong
    // place.
    (new ActiveFixtureManipulator())
      ->addProjectAtPath('projects/example3')
      ->commitChanges();
    $list = new InstalledPackagesList([
      'drupal/example3' => InstalledPackage::createFromArray([
        'name' => 'drupal/example3',
        'version' => '1.0.0',
        'type' => 'drupal-module',
        // This path exists, but it doesn't contain the `example3` project.
        'path' => $projects_path . '/theme_project',
      ]),
    ]);
    $this->assertNull($list->getPackageByDrupalProjectName('example3'));

    // The project does not physically exist, which means it must be a
    // metapackage.
    $list = new InstalledPackagesList([
      'drupal/missing' => InstalledPackage::createFromArray([
        'name' => 'drupal/missing',
        'version' => '1.0.0',
        'type' => 'metapackage',
        'path' => NULL,
      ]),
    ]);
    $this->assertNull($list->getPackageByDrupalProjectName('missing'));

    // The project physically exists in a subdirectory of the package.
    (new ActiveFixtureManipulator())
      ->addProjectAtPath('projects/grab_bag/modules/module_in_subdirectory')
      ->commitChanges();
    $list = new InstalledPackagesList([
      'drupal/grab_bag' => InstalledPackage::createFromArray([
        'name' => 'drupal/grab_bag',
        'version' => '1.0.0',
        'type' => 'drupal-profile',
        'path' => $projects_path . '/grab_bag',
      ]),
    ]);
    $this->assertSame($list['drupal/grab_bag'], $list->getPackageByDrupalProjectName('module_in_subdirectory'));

    // The package name matches a project that physically exists, but the
    // package vendor is not `drupal`.
    (new ActiveFixtureManipulator())
      ->addProjectAtPath('projects/not_from_drupal')
      ->commitChanges();
    $list = new InstalledPackagesList([
      'vendor/not_from_drupal' => InstalledPackage::createFromArray([
        'name' => 'vendor/not_from_drupal',
        'version' => '1.0.0',
        'type' => 'drupal-module',
        'path' => $projects_path . '/not_from_drupal',
      ]),
    ]);
    $this->assertNull($list->getPackageByDrupalProjectName('not_from_drupal'));

    // These package names match physically existing projects, and they are
    // from the `drupal` vendor, but they're not supported package types.
    (new ActiveFixtureManipulator())
      ->addProjectAtPath('projects/custom_module')
      ->addProjectAtPath('projects/custom_theme')
      ->commitChanges();
    $list = new InstalledPackagesList([
      'drupal/custom_module' => InstalledPackage::createFromArray([
        'name' => 'drupal/custom_module',
        'version' => '1.0.0',
        'type' => 'drupal-custom-module',
        'path' => $projects_path . '/custom_module',
      ]),
      'drupal/custom_theme' => InstalledPackage::createFromArray([
        'name' => 'drupal/custom_theme',
        'version' => '1.0.0',
        'type' => 'drupal-custom-theme',
        'path' => $projects_path . '/custom_theme',
      ]),
    ]);
    $this->assertNull($list->getPackageByDrupalProjectName('custom_module'));
    $this->assertNull($list->getPackageByDrupalProjectName('custom_theme'));

    // The `project` key has been removed from the info file.
    (new ActiveFixtureManipulator())
      ->addProjectAtPath('projects/no_project_key')
      ->commitChanges();
    $list = new InstalledPackagesList([
      'drupal/no_project_key' => InstalledPackage::createFromArray([
        'name' => 'drupal/no_project_key',
        'version' => '1.0.0',
        'type' => 'drupal-module',
        'path' => $projects_path . '/no_project_key',
      ]),
    ]);
    $info_file = $list['drupal/no_project_key']->path . '/no_project_key.info.yml';
    $this->assertFileIsWritable($info_file);
    $info = Yaml::decode(file_get_contents($info_file));
    unset($info['project']);
    file_put_contents($info_file, Yaml::encode($info));
    $this->assertNull($list->getPackageByDrupalProjectName('no_project_key'));

    // The project name is repeated.
    (new ActiveFixtureManipulator())
      ->addProjectAtPath('projects/duplicate_project')
      ->addProjectAtPath('projects/repeat/duplicate_project')
      ->commitChanges();
    $list = new InstalledPackagesList([
      'drupal/test_project1' => InstalledPackage::createFromArray([
        'name' => 'drupal/test_project1',
        'version' => '1.0.0',
        'type' => 'drupal-module',
        'path' => $projects_path . '/duplicate_project',
      ]),
      'drupal/test_project2' => InstalledPackage::createFromArray([
        'name' => 'drupal/test_project2',
        'version' => '1.0.0',
        'type' => 'drupal-module',
        'path' => $projects_path . '/repeat/duplicate_project',
      ]),
    ]);
    $this->expectException(\UnexpectedValueException::class);
    $this->expectExceptionMessage("Project 'duplicate_project' was found in packages 'drupal/test_project1' and 'drupal/test_project2'.");
    $list->getPackageByDrupalProjectName('duplicate_project');
  }

}
