<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\fixture_manipulator\FixtureManipulator;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\InstalledPackagesList;
use Drupal\Tests\package_manager\Traits\InstalledPackagesListTrait;
use Drupal\package_manager\PathLocator;

/**
 * @coversDefaultClass \Drupal\fixture_manipulator\FixtureManipulator
 *
 * @group package_manager
 */
class FixtureManipulatorTest extends PackageManagerKernelTestBase {

  use InstalledPackagesListTrait;

  /**
   * The root directory of the test project.
   *
   * @var string
   */
  private string $dir;

  /**
   * The exception expected in ::tearDown() of this test.
   *
   * @var \Exception
   */
  private \Exception $expectedTearDownException;

  /**
   * The Composer inspector service.
   *
   * @var \Drupal\package_manager\ComposerInspector
   */
  private ComposerInspector $inspector;

  /**
   * The original fixture package list at the start of the test.
   *
   * @var \Drupal\package_manager\InstalledPackagesList
   */
  private InstalledPackagesList $originalFixturePackages;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->dir = $this->container->get(PathLocator::class)->getProjectRoot();

    $this->inspector = $this->container->get(ComposerInspector::class);

    $manipulator = new ActiveFixtureManipulator();
    $manipulator
      ->addPackage([
        'name' => 'my/package',
        'type' => 'library',
        'version' => '1.2.3',
      ])
      ->addPackage(
        [
          'name' => 'my/dev-package',
          'version' => '2.1.0',
          'type' => 'library',
        ],
        TRUE
      )
      ->commitChanges();
    $this->originalFixturePackages = $this->inspector->getInstalledPackagesList($this->dir);
  }

  /**
   * @covers ::addPackage
   */
  public function testAddPackage(): void {
    // Packages cannot be added without a name.
    foreach (['name', 'type'] as $require_key) {
      // Make a package that is missing the required key.
      $package = array_diff_key(
        [
          'name' => 'Any old name',
          'type' => 'Any old type',
        ],
        [$require_key => '']
      );
      try {
        $manipulator = new ActiveFixtureManipulator();
        $manipulator->addPackage($package)
          ->commitChanges();
        $this->fail("Adding a package without the '$require_key' should raise an error.");
      }
      catch (\UnexpectedValueException $e) {
        $this->assertSame("The '$require_key' is required when calling ::addPackage().", $e->getMessage());
      }
    }

    // We should get a helpful error if the name is not a valid package name.
    try {
      $manipulator = new ActiveFixtureManipulator();
      $manipulator->addPackage([
        'name' => 'my_drupal_module',
        'type' => 'drupal-module',
      ])
        ->commitChanges();
      $this->fail('Trying to add a package with an invalid name should raise an error.');
    }
    catch (\UnexpectedValueException $e) {
      $this->assertSame("'my_drupal_module' is not a valid package name.", $e->getMessage());
    }

    // We should not be able to add an existing package.
    try {
      $manipulator = new ActiveFixtureManipulator();
      $manipulator->addPackage([
        'name' => 'my/package',
        'type' => 'library',
      ])
        ->commitChanges();
      $this->fail('Trying to add an existing package should raise an error.');
    }
    catch (\LogicException $e) {
      $this->assertStringContainsString("Expected package 'my/package' to not be installed, but it was.", $e->getMessage());
    }
    // Ensure that none of the failed calls to ::addPackage() changed the
    // installed packages.
    $this->assertPackageListsEqual($this->originalFixturePackages, $this->inspector->getInstalledPackagesList($this->dir));
    $root_info = $this->inspector->getRootPackageInfo($this->dir);
    $this->assertSame(
      ['drupal/core-dev', 'my/dev-package'],
      array_keys($root_info['devRequires'])
    );
  }

  /**
   * @covers ::modifyPackageConfig
   */
  public function testModifyPackageConfig(): void {
    // Assert ::modifyPackage() works with a package in an existing fixture not
    // created by ::addPackage().
    $decode_packages_json = function (): array {
      return json_decode(file_get_contents($this->dir . "/packages.json"), TRUE, flags: JSON_THROW_ON_ERROR);
    };
    $original_packages_json = $decode_packages_json();
    (new ActiveFixtureManipulator())
      // @see ::setUp()
      ->modifyPackageConfig('my/dev-package', '2.1.0', ['description' => 'something else'], TRUE)
      ->commitChanges();
    // Verify that the package is indeed properly installed.
    $this->assertSame('2.1.0', $this->inspector->getInstalledPackagesList($this->dir)['my/dev-package']?->version);
    // Verify that the original exists, but has no description.
    $this->assertSame('my/dev-package', $original_packages_json['packages']['my/dev-package']['2.1.0']['name']);
    $this->assertArrayNotHasKey('description', $original_packages_json);
    // Verify that the description was updated.
    $this->assertSame('something else', $decode_packages_json()['packages']['my/dev-package']['2.1.0']['description']);

    (new ActiveFixtureManipulator())
      // Add a key to an existing package.
      ->modifyPackageConfig('my/package', '1.2.3', ['extra' => ['foo' => 'bar']])
      // Change a key in an existing package.
      ->setVersion('my/dev-package', '3.2.1', TRUE)
      ->commitChanges();
    $this->assertSame(['foo' => 'bar'], $decode_packages_json()['packages']['my/package']['1.2.3']['extra']);
    $this->assertSame('3.2.1', $this->inspector->getInstalledPackagesList($this->dir)['my/dev-package']?->version);
  }

  /**
   * @covers ::removePackage
   */
  public function testRemovePackage(): void {
    // We should not be able to remove a package that's not installed.
    try {
      (new ActiveFixtureManipulator())
        ->removePackage('junk/drawer')
        ->commitChanges();
      $this->fail('Removing a non-existent package should raise an error.');
    }
    catch (\LogicException $e) {
      $this->assertStringContainsString('junk/drawer is not required in your composer.json and has not been remove', $e->getMessage());
    }

    // Remove the 2 packages that were added in ::setUp().
    (new ActiveFixtureManipulator())
      ->removePackage('my/package')
      ->removePackage('my/dev-package', TRUE)
      ->commitChanges();
    $expected_packages = $this->originalFixturePackages->getArrayCopy();
    unset($expected_packages['my/package'], $expected_packages['my/dev-package']);
    $expected_list = new InstalledPackagesList($expected_packages);
    $this->assertPackageListsEqual($expected_list, $this->inspector->getInstalledPackagesList($this->dir));
    $root_info = $this->inspector->getRootPackageInfo($this->dir);
    $this->assertSame(
      ['drupal/core-dev'],
      array_keys($root_info['devRequires'])
    );
  }

  /**
   * Test that an exception is thrown if ::commitChanges() is not called.
   */
  public function testActiveManipulatorNoCommitError(): void {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('commitChanges() must be called.');
    (new ActiveFixtureManipulator())
      ->setVersion('drupal/core', '1.2.3');
  }

  /**
   * @covers ::addDotGitFolder
   */
  public function testAddDotGitFolder(): void {
    $path_locator = $this->container->get(PathLocator::class);
    $project_root = $path_locator->getProjectRoot();
    $this->assertFalse(is_dir($project_root . "/relative/path/.git"));
    // We should not be able to add a git folder to a non-existing directory.
    try {
      (new FixtureManipulator())
        ->addDotGitFolder($project_root . "/relative/path")
        ->commitChanges($project_root);
      $this->fail('Trying to create a .git directory that already exists should raise an error.');
    }
    catch (\LogicException $e) {
      $this->assertSame('No directory exists at ' . $project_root . '/relative/path.', $e->getMessage());
    }
    mkdir($project_root . "/relative/path", 0777, TRUE);
    $fixture_manipulator = (new FixtureManipulator())
      ->addPackage([
        'name' => 'relative/project_path',
        'type' => 'drupal-module',
      ])
      ->addDotGitFolder($path_locator->getVendorDirectory() . "/relative/project_path")
      ->addDotGitFolder($project_root . "/relative/path");
    $this->assertTrue(!is_dir($project_root . "/relative/project_path/.git"));
    $fixture_manipulator->commitChanges($project_root);
    $this->assertTrue(is_dir($project_root . "/relative/path/.git"));
    // We should not be able to create already existing directory.
    try {
      (new FixtureManipulator())
        ->addDotGitFolder($project_root . "/relative/path")
        ->commitChanges($project_root);
      $this->fail('Trying to create a .git directory that already exists should raise an error.');
    }
    catch (\LogicException $e) {
      $this->assertStringContainsString("A .git directory already exists at " . $project_root, $e->getMessage());
    }
  }

  /**
   * Tests that the stage manipulator throws an exception if not committed.
   */
  public function testStagedFixtureNotCommitted(): void {
    $this->expectedTearDownException = new \LogicException('The StageFixtureManipulator has arguments that were not cleared. This likely means that the PostCreateEvent was never fired.');
    $this->getStageFixtureManipulator()->setVersion('any-org/any-package', '3.2.1');
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove the line below when https://github.com/phpstan/phpstan-phpunit/issues/187 is fixed.
   * @phpstan-ignore-next-line
   */
  protected function tearDown(): void {
    try {
      parent::tearDown();
    }
    catch (\Exception $exception) {
      if (!(get_class($exception) === get_class($this->expectedTearDownException) && $exception->getMessage() === $this->expectedTearDownException->getMessage())) {
        throw $exception;
      }
    }
  }

}
