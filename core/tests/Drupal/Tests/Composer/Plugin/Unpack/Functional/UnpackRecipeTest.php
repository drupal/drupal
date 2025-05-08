<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Plugin\Unpack\Functional;

use Composer\InstalledVersions;
use Composer\Util\Filesystem;
use Drupal\Tests\Composer\Plugin\Unpack\Fixtures;
use Drupal\BuildTests\Framework\BuildTestBase;
use Drupal\Tests\Composer\Plugin\ExecTrait;

/**
 * Tests recipe unpacking.
 *
 * @group Unpack
 */
class UnpackRecipeTest extends BuildTestBase {

  use ExecTrait;

  /**
   * Directory to perform the tests in.
   */
  protected string $fixturesDir;

  /**
   * The Symfony FileSystem component.
   *
   * @var \Composer\Util\Filesystem
   */
  protected Filesystem $fileSystem;

  /**
   * The Fixtures object.
   *
   * @var \Drupal\Tests\Composer\Plugin\Unpack\Fixtures
   */
  protected Fixtures $fixtures;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileSystem = new Filesystem();
    $this->fixtures = new Fixtures();
    $this->fixtures->createIsolatedComposerCacheDir();
    $this->fixturesDir = $this->fixtures->tmpDir($this->name());
    $replacements = [
      'PROJECT_ROOT' => $this->fixtures->projectRoot(),
      'COMPOSER_INSTALLERS' => InstalledVersions::getInstallPath('composer/installers'),
    ];
    $this->fixtures->cloneFixtureProjects($this->fixturesDir, $replacements);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Remove any temporary directories that were created.
    $this->fixtures->tearDown();
    parent::tearDown();
  }

  /**
   * Tests the dependencies unpack on install.
   */
  public function testAutomaticUnpack(): void {
    $root_project_path = $this->fixturesDir . '/composer-root';

    copy($root_project_path . '/composer.json', $root_project_path . '/composer.json.original');

    // Run composer install and confirm the composer.lock was created.
    $this->runComposer('install');

    // Install a module in require-dev that should be moved to require
    // by the unpacker.
    $this->runComposer('require --dev fixtures/module-a:^1.0');
    // Ensure we have added the dependency to require-dev.
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    $this->assertArrayHasKey('fixtures/module-a', $root_composer_json['require-dev']);

    // Install a recipe and unpack it.
    $stdout = $this->runComposer('require fixtures/recipe-a');
    $this->doTestRecipeAUnpacked($root_project_path, $stdout);
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    // The more specific constraint should have been used.
    $this->assertSame("^1.0", $root_composer_json['require']['fixtures/module-a']);

    // Copy old composer.json back over and require recipe again to ensure it
    // is still unpacked. This tests that unpacking does not rely on composer
    // package events.
    unlink($root_project_path . '/composer.json');
    copy($root_project_path . '/composer.json.original', $root_project_path . '/composer.json');
    $stdout = $this->runComposer('require fixtures/recipe-a');
    $this->doTestRecipeAUnpacked($root_project_path, $stdout);
  }

  /**
   * Tests recursive unpacking.
   */
  public function testRecursiveUnpacking(): void {
    $root_project_path = $this->fixturesDir . '/composer-root';

    // Run composer install and confirm the composer.lock was created.
    $this->runComposer('config --merge --json sort-packages true');
    $this->runComposer('install');
    $stdOut = $this->runComposer('require fixtures/recipe-c fixtures/recipe-a');
    $this->assertSame("fixtures/recipe-c unpacked.\nfixtures/recipe-a unpacked.\nfixtures/recipe-b unpacked.\n", $stdOut);
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    $this->assertSame([
      'composer/installers',
      'drupal/core-recipe-unpack',
      'fixtures/module-a',
      'fixtures/module-b',
      'fixtures/theme-a',
    ], array_keys($root_composer_json['require']));
    // Ensure the resulting composer files are valid.
    $this->runComposer('validate');
    // Ensure the recipes exist.
    $this->assertFileExists($root_project_path . '/recipes/recipe-a/recipe.yml');
    $this->assertFileExists($root_project_path . '/recipes/recipe-b/recipe.yml');
    $this->assertFileExists($root_project_path . '/recipes/recipe-c/recipe.yml');

    // Ensure the complex constraint has been written correctly.
    $this->assertSame('>=2.0.1.0-dev, <3.0.0.0-dev', $root_composer_json['require']['fixtures/module-b']);

    // Ensure composer.lock is ordered correctly.
    $root_composer_lock = $this->getFileContents($root_project_path . '/composer.lock');
    $this->assertSame([
      'composer/installers',
      'drupal/core-recipe-unpack',
      'fixtures/module-a',
      'fixtures/module-b',
      'fixtures/theme-a',
    ], array_column($root_composer_lock['packages'], 'name'));
  }

  /**
   * Tests the dev dependencies do not unpack on install.
   */
  public function testNoAutomaticDevUnpack(): void {
    $root_project_path = $this->fixturesDir . '/composer-root';

    // Run composer install and confirm the composer.lock was created.
    $this->runComposer('install');

    // Install a module in require.
    $this->runComposer('require fixtures/module-a');
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    $this->assertArrayHasKey('fixtures/module-a', $root_composer_json['require']);

    // Install a recipe as a dev dependency.
    $stdout = $this->runComposer('require --dev fixtures/recipe-a');
    $this->assertStringContainsString("Recipes required as a development dependency are not automatically unpacked.", $stdout);
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');

    // Assert the state of the root composer.json as no unpacking has occurred.
    $this->assertSame(['fixtures/recipe-a'], array_keys($root_composer_json['require-dev']));
    $this->assertSame(['composer/installers', 'drupal/core-recipe-unpack', 'fixtures/module-a'], array_keys($root_composer_json['require']));

    // Ensure the resulting Composer files are valid.
    $this->runComposer('validate');
  }

  /**
   * Tests dependency unpacking using drupal:recipe-unpack.
   */
  public function testUnpackCommand(): void {
    $root_project_path = $this->fixturesDir . '/composer-root';

    // Run composer install and confirm the composer.lock was created.
    $this->runComposer('install');

    // Disable automatic unpacking as it is the default behavior,
    $this->runComposer('config --merge --json extra.drupal-recipe-unpack.on-require false');

    // Install a module in require-dev.
    $this->runComposer('require --dev fixtures/module-a');
    // Install a module in require.
    $this->runComposer('require fixtures/module-b:*');

    // Ensure we have added the dependencies.
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    $this->assertArrayHasKey('fixtures/module-b', $root_composer_json['require']);
    $this->assertArrayHasKey('fixtures/module-a', $root_composer_json['require-dev']);

    // Install a recipe and check it is not unpacked.
    $stdout = $this->runComposer('require fixtures/recipe-a');
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');

    // When the package is unpacked, the unpacked dependencies should be logged
    // in the stdout.
    $this->assertStringNotContainsString("unpacked.", $stdout);

    $this->assertArrayHasKey('fixtures/recipe-a', $root_composer_json['require']);
    // Ensure the resulting Composer files are valid.
    $this->runComposer('validate');

    // The package dependencies should not be in the root composer.json.
    $this->assertArrayNotHasKey('fixtures/recipe-b', $root_composer_json['require']);

    // Try unpacking a recipe that in not in the root composer.json.
    try {
      $this->runComposer('drupal:recipe-unpack fixtures/recipe-b');
      $this->fail('Unpacking a non-existent dependency should fail');
    }
    catch (\RuntimeException $e) {
      $this->assertStringContainsString('fixtures/recipe-b not found in the root composer.json.', $e->getMessage());
    }

    // The dev dependency has not moved.
    $this->assertArrayHasKey('fixtures/module-a', $root_composer_json['require-dev']);

    $stdout = $this->runComposer('drupal:recipe-unpack fixtures/recipe-a');
    $this->doTestRecipeAUnpacked($root_project_path, $stdout);
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    // The more specific constraints has been used.
    $this->assertSame("^2.0", $root_composer_json['require']['fixtures/module-b']);

    // Try unpacking something that is not a recipe.
    try {
      $this->runComposer('drupal:recipe-unpack fixtures/module-a');
      $this->fail('Unpacking a module should fail');
    }
    catch (\RuntimeException $e) {
      $this->assertStringContainsString('fixtures/module-a is not a recipe.', $e->getMessage());
    }

    // Try unpacking something that in not in the root composer.json.
    try {
      $this->runComposer('drupal:recipe-unpack fixtures/module-c');
      $this->fail('Unpacking a non-existent dependency should fail');
    }
    catch (\RuntimeException $e) {
      $this->assertStringContainsString('fixtures/module-c not found in the root composer.json.', $e->getMessage());
    }
  }

  /**
   * Tests dependency unpacking using drupal:recipe-unpack with multiple args.
   */
  public function testUnpackCommandWithMultipleRecipes(): void {
    $root_project_path = $this->fixturesDir . '/composer-root';
    $this->runComposer('install');

    // Disable automatic unpacking as it is the default behavior,
    $this->runComposer('config --merge --json extra.drupal-recipe-unpack.on-require false');

    // Install a recipe and check it is not unpacked.
    $stdOut = $this->runComposer('require fixtures/recipe-a fixtures/recipe-d');
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');

    // When the package is unpacked, the unpacked dependencies should be logged
    // in the stdout.
    $this->assertStringNotContainsString("unpacked.", $stdOut);

    $this->assertArrayHasKey('fixtures/recipe-a', $root_composer_json['require']);
    $this->assertArrayHasKey('fixtures/recipe-d', $root_composer_json['require']);

    $stdOut = $this->runComposer('drupal:recipe-unpack fixtures/recipe-a fixtures/recipe-d');
    $this->assertStringContainsString("fixtures/recipe-a unpacked.", $stdOut);
    $this->assertStringContainsString("fixtures/recipe-d unpacked.", $stdOut);

    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    $this->assertArrayNotHasKey('fixtures/recipe-a', $root_composer_json['require']);
    $this->assertArrayNotHasKey('fixtures/recipe-d', $root_composer_json['require']);
    // Ensure the resulting Composer files are valid.
    $this->runComposer('validate');
  }

  /**
   * Tests dependency unpacking using drupal:recipe-unpack with no arguments.
   */
  public function testUnpackCommandWithoutRecipesArgument(): void {
    $root_project_path = $this->fixturesDir . '/composer-root';
    $this->runComposer('install');

    // Tests unpack command with no arguments and no recipes in the root
    // composer package.
    $stdOut = $this->runComposer('drupal:recipe-unpack');
    $this->assertSame("No recipes to unpack.\n", $stdOut);

    // Disable automatic unpacking as it is the default behavior,
    $this->runComposer('config --merge --json extra.drupal-recipe-unpack.on-require false');

    // Install a recipe and check it is not unpacked.
    $stdOut = $this->runComposer('require fixtures/recipe-a fixtures/recipe-d');
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');

    // When the package is unpacked, the unpacked dependencies should be logged
    // in the stdout.
    $this->assertStringNotContainsString("unpacked.", $stdOut);

    $this->assertArrayHasKey('fixtures/recipe-a', $root_composer_json['require']);
    $this->assertArrayHasKey('fixtures/recipe-d', $root_composer_json['require']);

    $stdOut = $this->runComposer('drupal:recipe-unpack');
    $this->assertStringContainsString("fixtures/recipe-a unpacked.", $stdOut);
    $this->assertStringContainsString("fixtures/recipe-d unpacked.", $stdOut);

    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    $this->assertArrayNotHasKey('fixtures/recipe-a', $root_composer_json['require']);
    $this->assertArrayNotHasKey('fixtures/recipe-d', $root_composer_json['require']);
    // Ensure the resulting Composer files are valid.
    $this->runComposer('validate');
  }

  /**
   * Tests unpacking a recipe in require-dev using drupal:recipe-unpack.
   */
  public function testUnpackCommandOnDevRecipe(): void {
    $root_project_path = $this->fixturesDir . '/composer-root';

    // Run composer install and confirm the composer.lock was created.
    $this->runComposer('install');

    // Disable automatic unpacking, which is the default behavior.
    $this->runComposer('config --merge --json extra.drupal-recipe-unpack.on-require false');

    $this->runComposer('require fixtures/recipe-b');

    // Install a recipe and check it is not unpacked.
    $this->runComposer('require --dev fixtures/recipe-a');
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');

    $this->assertArrayHasKey('fixtures/recipe-a', $root_composer_json['require-dev']);
    $this->assertArrayHasKey('fixtures/recipe-b', $root_composer_json['require']);

    $error_output = '';
    $stdout = $this->runComposer('drupal:recipe-unpack fixtures/recipe-a', error_output: $error_output);
    $this->assertStringContainsString("fixtures/recipe-a is present in the require-dev key. Unpacking will move the recipe's dependencies to the require key.", $error_output);
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');

    // Ensure recipe A's dependencies are moved to require.
    $this->doTestRecipeAUnpacked($root_project_path, $stdout);

    // Ensure recipe B's dependencies are in require and the recipe has been
    // unpacked.
    $this->assertArrayNotHasKey('fixtures/recipe-b', $root_composer_json['require']);
    $this->assertArrayHasKey('fixtures/module-a', $root_composer_json['require']);
    $this->assertArrayHasKey('fixtures/theme-a', $root_composer_json['require']);

    // Ensure installed.json and installed.php are correct.
    $installed_json = $this->getFileContents($root_project_path . '/vendor/composer/installed.json');
    $installed_packages = array_column($installed_json['packages'], 'name');
    $this->assertContains('fixtures/module-b', $installed_packages);
    $this->assertNotContains('fixtures/recipe-a', $installed_packages);
    $this->assertSame([], $installed_json['dev-package-names']);
    $installed_php = include_once $root_project_path . '/vendor/composer/installed.php';
    $this->assertArrayHasKey('fixtures/module-b', $installed_php['versions']);
    $this->assertFalse($installed_php['versions']['fixtures/module-b']['dev_requirement']);
    $this->assertArrayNotHasKey('fixtures/recipe-a', $installed_php['versions']);
  }

  /**
   * Tests the unpacking a recipe that is an indirect dev dependency.
   */
  public function testUnpackCommandOnIndirectDevDependencyRecipe(): void {
    $root_project_path = $this->fixturesDir . '/composer-root';

    // Run composer install and confirm the composer.lock was created.
    $this->runComposer('install');
    // Disable automatic unpacking as it is the default behavior,
    $this->runComposer('config --merge --json extra.drupal-recipe-unpack.on-require false');

    $this->runComposer('require --dev fixtures/recipe-b');

    // Install a recipe and ensure it is not unpacked.
    $this->runComposer('require fixtures/recipe-a');
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');

    $this->assertArrayHasKey('fixtures/recipe-a', $root_composer_json['require']);
    $this->assertArrayHasKey('fixtures/recipe-b', $root_composer_json['require-dev']);

    // Ensure the resulting Composer files are valid.
    $this->runComposer('validate');

    $this->runComposer('drupal:recipe-unpack fixtures/recipe-a');
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');

    // Ensure recipe A's dependencies are in require.
    $this->assertArrayNotHasKey('fixtures/recipe-a', $root_composer_json['require']);
    $this->assertArrayHasKey('fixtures/module-b', $root_composer_json['require']);
    $this->assertArrayHasKey('fixtures/module-a', $root_composer_json['require']);
    $this->assertArrayHasKey('fixtures/theme-a', $root_composer_json['require']);

    // Ensure recipe B is still in require-dev even though all it's dependencies
    // have been unpacked to require due to unpacking recipe A.
    $this->assertSame(['fixtures/recipe-b'], array_keys($root_composer_json['require-dev']));

    // Ensure recipe B is still list in installed.json.
    $installed_json = $this->getFileContents($root_project_path . '/vendor/composer/installed.json');
    $installed_packages = array_column($installed_json['packages'], 'name');
    $this->assertContains('fixtures/recipe-b', $installed_packages);
    $this->assertContains('fixtures/recipe-b', $installed_json['dev-package-names']);

    // Ensure the resulting Composer files are valid.
    $this->runComposer('validate');
  }

  /**
   * Tests a recipe can be removed and the unpack plugin does not interfere.
   */
  public function testRemoveRecipe(): void {
    $root_project_path = $this->fixturesDir . '/composer-root';

    // Disable automatic unpacking, which is the default behavior,
    $this->runComposer('config --merge --json extra.drupal-recipe-unpack.on-require false');

    $this->runComposer('install');

    // Install a recipe and ensure it is not unpacked.
    $this->runComposer('require fixtures/recipe-a');
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    $this->assertSame([
      'composer/installers',
      'drupal/core-recipe-unpack',
      'fixtures/recipe-a',
    ], array_keys($root_composer_json['require']));

    // Removing the recipe should work as normal.
    $this->runComposer('remove fixtures/recipe-a');
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    $this->assertSame([
      'composer/installers',
      'drupal/core-recipe-unpack',
    ], array_keys($root_composer_json['require']));

    // Ensure the resulting Composer files are valid.
    $this->runComposer('validate');
  }

  /**
   * Tests a recipe can be ignored and not unpacked.
   */
  public function testIgnoreRecipe(): void {
    $root_project_path = $this->fixturesDir . '/composer-root';

    // Disable automatic unpacking as it is the default behavior,
    $this->runComposer('config --merge --json extra.drupal-recipe-unpack.ignore \'["fixtures/recipe-a"]\'');

    $this->runComposer('install');

    // Install a recipe and ensure it does not get unpacked.
    $stdOut = $this->runComposer('require --verbose fixtures/recipe-a');
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    $this->assertSame("fixtures/recipe-a not unpacked because it is ignored.", trim($stdOut));

    $this->assertSame([
      'composer/installers',
      'drupal/core-recipe-unpack',
      'fixtures/recipe-a',
    ], array_keys($root_composer_json['require']));

    // Ensure the resulting Composer files are valid.
    $this->runComposer('validate');

    // Try using the unpack command on an ignored recipe.
    try {
      $this->runComposer('drupal:recipe-unpack fixtures/recipe-a');
      $this->fail('Ignored recipes should not be unpacked.');
    }
    catch (\RuntimeException $e) {
      $this->assertStringContainsString('fixtures/recipe-a is in the extra.drupal-recipe-unpack.ignore list.', $e->getMessage());
    }
  }

  /**
   * Tests a dependent recipe can be ignored and not unpacked.
   */
  public function testIgnoreDependentRecipe(): void {
    $root_project_path = $this->fixturesDir . '/composer-root';

    // Disable automatic unpacking, which is the default behavior,
    $this->runComposer('config --merge --json extra.drupal-recipe-unpack.ignore \'["fixtures/recipe-b"]\'');
    $this->runComposer('config sort-packages true');

    $this->runComposer('install');

    // Install a recipe and check it is not packed but not removed.
    $stdOut = $this->runComposer('require --verbose fixtures/recipe-a');
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    $this->assertStringContainsString("fixtures/recipe-b not unpacked because it is ignored.", $stdOut);
    $this->assertStringContainsString("fixtures/recipe-a unpacked.", $stdOut);

    $this->assertSame([
      'composer/installers',
      'drupal/core-recipe-unpack',
      'fixtures/module-b',
      'fixtures/recipe-b',
    ], array_keys($root_composer_json['require']));

    // Ensure the resulting Composer files are valid.
    $this->runComposer('validate');
  }

  /**
   * Tests that recipes stick around after being unpacked.
   */
  public function testRecipeIsPhysicallyPresentAfterUnpack(): void {
    $root_project_dir = 'composer-root';
    $root_project_path = $this->fixturesDir . '/' . $root_project_dir;

    $this->runComposer('install');

    // Install a recipe, which should unpack it.
    $stdOut = $this->runComposer('require --verbose fixtures/recipe-b');
    $this->assertStringContainsString("fixtures/recipe-b unpacked.", $stdOut);
    $this->assertFileExists($root_project_path . '/recipes/recipe-b/recipe.yml');

    // Require another dependency.
    $this->runComposer('require --verbose fixtures/module-b');

    // The recipe should still be physically installed...
    $this->assertFileExists($root_project_path . '/recipes/recipe-b/recipe.yml');

    // ...but it should NOT be in installed.json or installed.php.
    $installed_json = $this->getFileContents($root_project_path . '/vendor/composer/installed.json');
    $installed_packages = array_column($installed_json['packages'], 'name');
    $this->assertContains('fixtures/module-b', $installed_packages);
    $this->assertNotContains('fixtures/recipe-b', $installed_packages);
    $installed_php = include_once $root_project_path . '/vendor/composer/installed.php';
    $this->assertArrayHasKey('fixtures/module-b', $installed_php['versions']);
    $this->assertArrayNotHasKey('fixtures/recipe-b', $installed_php['versions']);
  }

  /**
   * Tests a recipe can be required using --no-install and installed later.
   */
  public function testRecipeNotUnpackedIfInstallIsDeferred(): void {
    $root_project_path = $this->fixturesDir . '/composer-root';

    $this->runComposer('install');

    // Install a recipe and check it is in `composer.json` but not unpacked or
    // physically installed.
    $stdOut = $this->runComposer('require --verbose --no-install fixtures/recipe-a');
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    $this->assertSame("Recipes are not unpacked when the --no-install option is used.", trim($stdOut));
    $this->assertSame([
      'composer/installers',
      'drupal/core-recipe-unpack',
      'fixtures/recipe-a',
    ], array_keys($root_composer_json['require']));
    $this->assertFileDoesNotExist($root_project_path . '/recipes/recipe-a/recipe.yml');

    // After installing dependencies, the recipe should be installed, but still
    // not unpacked.
    $this->runComposer('install');
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');
    $this->assertSame([
      'composer/installers',
      'drupal/core-recipe-unpack',
      'fixtures/recipe-a',
    ], array_keys($root_composer_json['require']));
    $this->assertFileExists($root_project_path . '/recipes/recipe-a/recipe.yml');

    // Ensure the resulting Composer files are valid.
    $this->runComposer('validate');
  }

  /**
   * Tests that recipes are unpacked when using `composer create-project`.
   */
  public function testComposerCreateProject(): void {
    // Prepare the project to use for create-project.
    $root_project_path = $this->fixturesDir . '/composer-root';
    $this->runComposer('require --verbose --no-install fixtures/recipe-a');

    $stdOut = $this->runComposer('create-project --repository=\'{"type": "path","url": "' . $root_project_path . '","options": {"symlink": false}}\' fixtures/root composer-root2 -s dev', $this->fixturesDir);
    // The recipes depended upon by the project, even indirectly, should all
    // have been unpacked.
    $this->assertSame("fixtures/recipe-b unpacked.\nfixtures/recipe-a unpacked.\n", $stdOut);
    $this->doTestRecipeAUnpacked($this->fixturesDir . '/composer-root2', $stdOut);
  }

  /**
   * Tests Recipe A is unpacked correctly.
   *
   * @param string $root_project_path
   *   Path to the composer project under test.
   * @param string $stdout
   *   The standard out from the composer command unpacks the recipe.
   */
  private function doTestRecipeAUnpacked(string $root_project_path, string $stdout): void {
    $root_composer_json = $this->getFileContents($root_project_path . '/composer.json');

    // @see core/tests/Drupal/Tests/Composer/Plugin/Unpack/fixtures/recipes/composer-recipe-a/composer.json
    // @see core/tests/Drupal/Tests/Composer/Plugin/Unpack/fixtures/recipes/composer-recipe-b/composer.json
    $expected_unpacked = [
      'fixtures/recipe-a' => [
        'fixtures/module-b',
      ],
      'fixtures/recipe-b' => [
        'fixtures/module-a',
        'fixtures/theme-a',
      ],
    ];
    foreach ($expected_unpacked as $package => $dependencies) {
      // When the package is unpacked, the unpacked dependencies should be logged
      // in the stdout.
      $this->assertStringContainsString("$package unpacked.", $stdout);

      // After being unpacked, the package should be removed from the root
      // composer.json and composer.lock.
      $this->assertArrayNotHasKey($package, $root_composer_json['require']);

      foreach ($dependencies as $dependency) {
        // The package dependencies should be in the root composer.json.
        $this->assertArrayHasKey($dependency, $root_composer_json['require']);
      }
    }

    // Ensure the resulting Composer files are valid.
    $this->runComposer('validate', $root_project_path);

    // The dev dependency has moved.
    $this->assertArrayNotHasKey('require-dev', $root_composer_json);

    // Ensure recipe files exist.
    $this->assertFileExists($root_project_path . '/recipes/recipe-a/recipe.yml');
    $this->assertFileExists($root_project_path . '/recipes/recipe-b/recipe.yml');

    // Ensure composer.lock is ordered correctly.
    $root_composer_lock = $this->getFileContents($root_project_path . '/composer.lock');
    $this->assertSame([
      'composer/installers',
      'drupal/core-recipe-unpack',
      'fixtures/module-a',
      'fixtures/module-b',
      'fixtures/theme-a',
    ], array_column($root_composer_lock['packages'], 'name'));
  }

  /**
   * Executes a Composer command with standard options.
   *
   * @param string $command
   *   The composer command to execute.
   * @param string $cwd
   *   The current working directory to run the command from.
   * @param string $error_output
   *   Passed by reference to allow error output to be tested.
   *
   * @return string
   *   Standard output from the command.
   */
  private function runComposer(string $command, ?string $cwd = NULL, string &$error_output = ''): string {
    $cwd ??= $this->fixturesDir . '/composer-root';

    // Always add --no-interaction and --no-ansi to Composer commands.
    $output = $this->mustExec("composer $command --no-interaction --no-ansi", $cwd, [], $error_output);
    if ($command === 'install') {
      $this->assertFileExists($cwd . '/composer.lock');
    }
    return $output;
  }

  /**
   * Gets the contents of a file as an array.
   *
   * @param string $path
   *   The path to the file.
   *
   * @return array
   *   The contents of the file as an array.
   */
  private function getFileContents(string $path): array {
    $file = file_get_contents($path);
    return json_decode($file, TRUE, flags: JSON_THROW_ON_ERROR);
  }

}
