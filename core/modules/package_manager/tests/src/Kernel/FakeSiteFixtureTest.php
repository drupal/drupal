<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\PathLocator;
use Symfony\Component\Process\Process;

/**
 * Test that the 'fake-site' fixture is a valid starting point.
 *
 * @group package_manager
 * @internal
 */
class FakeSiteFixtureTest extends PackageManagerKernelTestBase {

  /**
   * Tests the complete stage life cycle using the 'fake-site' fixture.
   */
  public function testLifeCycle(): void {
    $this->assertStatusCheckResults([]);
    $this->assertResults([]);
    // Ensure there are no validation errors after the stage lifecycle has been
    // completed.
    $this->assertStatusCheckResults([]);
  }

  /**
   * Tests calls to ComposerInspector class methods.
   */
  public function testCallToComposerInspectorMethods(): void {
    $active_dir = $this->container->get(PathLocator::class)->getProjectRoot();
    /** @var \Drupal\package_manager\ComposerInspector $inspector */
    $inspector = $this->container->get(ComposerInspector::class);
    $list = $inspector->getInstalledPackagesList($active_dir);
    $this->assertNull($list->getPackageByDrupalProjectName('any_random_name'));
    $this->assertFalse(isset($list['drupal/any_random_name']));
  }

  /**
   * Tests if `setVersion` can be called on all packages in the fixture.
   *
   * @see \Drupal\fixture_manipulator\FixtureManipulator::setVersion()
   */
  public function testCallToSetVersion(): void {
    /** @var \Drupal\package_manager\ComposerInspector $inspector */
    $inspector = $this->container->get(ComposerInspector::class);
    $active_dir = $this->container->get(PathLocator::class)->getProjectRoot();
    $installed_packages = $inspector->getInstalledPackagesList($active_dir);
    foreach (self::getExpectedFakeSitePackages() as $package_name) {
      $this->assertArrayHasKey($package_name, $installed_packages);
      $this->assertSame($installed_packages[$package_name]->version, '9.8.0');
      (new ActiveFixtureManipulator())
        ->setVersion($package_name, '11.1.0')
        ->commitChanges();
      $list = $inspector->getInstalledPackagesList($active_dir);
      $this->assertSame($list[$package_name]?->version, '11.1.0');
    }
  }

  /**
   * Tests if `removePackage` can be called on all packages in the fixture.
   *
   * @covers \Drupal\fixture_manipulator\FixtureManipulator::removePackage
   */
  public function testCallToRemovePackage(): void {
    /** @var \Drupal\package_manager\ComposerInspector $inspector */
    $inspector = $this->container->get(ComposerInspector::class);
    $active_dir = $this->container->get(PathLocator::class)->getProjectRoot();
    $expected_packages = self::getExpectedFakeSitePackages();
    $actual_packages = array_keys($inspector->getInstalledPackagesList($active_dir)->getArrayCopy());
    sort($actual_packages);
    $this->assertSame($expected_packages, $actual_packages);
    foreach (self::getExpectedFakeSitePackages() as $package_name) {
      (new ActiveFixtureManipulator())
        ->removePackage($package_name, $package_name === 'drupal/core-dev')
        ->commitChanges();
      array_shift($expected_packages);
      $actual_package_names = array_keys($inspector->getInstalledPackagesList($active_dir)->getArrayCopy());
      sort($actual_package_names);
      $this->assertSame($expected_packages, $actual_package_names);
    }

  }

  /**
   * Checks that the expected packages are installed in the fake site fixture.
   */
  public function testExpectedPackages(): void {
    $project_root = $this->container->get(PathLocator::class)
      ->getProjectRoot();
    $installed_packages = $this->container->get(ComposerInspector::class)
      ->getInstalledPackagesList($project_root)
      ->getArrayCopy();
    ksort($installed_packages);
    $this->assertSame($this->getExpectedFakeSitePackages(), array_keys($installed_packages));
  }

  /**
   * Gets the expected packages in the `fake_site` fixture.
   *
   * @return string[]
   *   The package names.
   */
  private static function getExpectedFakeSitePackages(): array {
    $packages = [
      'drupal/core',
      'drupal/core-recommended',
      'drupal/core-dev',
    ];
    sort($packages);
    return $packages;
  }

  /**
   * Tests that Composer show command can be used on the fixture.
   */
  public function testComposerShow(): void {
    $active_dir = $this->container->get(PathLocator::class)->getProjectRoot();
    (new ActiveFixtureManipulator())
      ->addPackage([
        'type' => 'package',
        'version' => '1.2.3',
        'name' => 'any-org/any-package',
      ])
      ->commitChanges();
    $process = new Process(['composer', 'show', '--format=json'], $active_dir);
    $process->run();
    if ($error = $process->getErrorOutput()) {
      $this->fail('Process error: ' . $error);
    }
    $output = json_decode($process->getOutput(), TRUE);
    $package_names = array_map(fn (array $package) => $package['name'], $output['installed']);
    $this->assertTrue(asort($package_names));
    $this->assertSame(['any-org/any-package', 'drupal/core', 'drupal/core-dev', 'drupal/core-recommended'], $package_names);
    $list = $this->container->get(ComposerInspector::class)->getInstalledPackagesList($active_dir);
    $list_packages_names = array_keys($list->getArrayCopy());
    $this->assertSame(['any-org/any-package', 'drupal/core', 'drupal/core-dev', 'drupal/core-recommended'], $list_packages_names);
  }

  /**
   * Tests that the fixture passes `composer validate`.
   */
  public function testComposerValidate(): void {
    $active_dir = $this->container->get(PathLocator::class)->getProjectRoot();
    $process = new Process([
      'composer',
      'validate',
      '--check-lock',
      '--with-dependencies',
      '--no-interaction',
      '--ansi',
      '--no-cache',
    ], $active_dir);
    $process->mustRun();
  }

}
