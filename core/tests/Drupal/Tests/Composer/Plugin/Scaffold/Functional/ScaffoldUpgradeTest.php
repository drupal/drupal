<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Plugin\Scaffold\Functional;

use Composer\Util\Filesystem;
use Drupal\Tests\Composer\Plugin\ExecTrait;
use Drupal\Tests\Composer\Plugin\Scaffold\AssertUtilsTrait;
use Drupal\Tests\Composer\Plugin\Scaffold\Fixtures;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests Upgrading the Composer Scaffold plugin.
 *
 * Upgrading a Composer plugin can be a dangerous operation. If the plugin
 * instantiates any classes during the activate method, and the plugin code
 * is subsequently modified by a `composer update` operation, then any
 * post-update hook (& etc.) may run with inconsistent code, leading to
 * runtime errors. This test ensures that it is possible to upgrade from the
 * last available stable 8.8.x tag to the current Scaffold plugin code (e.g. in
 * the current patch-under-test).
 */
#[Group('Scaffold')]
#[Group('#slow')]
class ScaffoldUpgradeTest extends TestCase {

  use AssertUtilsTrait;
  use ExecTrait;

  /**
   * The Fixtures object.
   *
   * @var \Drupal\Tests\Composer\Plugin\Scaffold\Fixtures
   */
  protected $fixtures;

  /**
   * The Fixtures directory.
   *
   * @var string
   */
  protected string $fixturesDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->fixtures = new Fixtures();
    $this->fixtures->createIsolatedComposerCacheDir();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->fixtures->tearDown();
  }

  /**
   * Tests upgrading the Composer Scaffold plugin.
   */
  public function testScaffoldUpgrade(): void {
    $this->fixturesDir = $this->fixtures->tmpDir($this->name());
    $replacements = ['SYMLINK' => 'false', 'PROJECT_ROOT' => $this->fixtures->projectRoot()];
    $this->fixtures->cloneFixtureProjects($this->fixturesDir, $replacements);
    $topLevelProjectDir = 'drupal-drupal';
    $sut = $this->fixturesDir . '/' . $topLevelProjectDir;

    // First step: set up the Scaffold plug in. Ensure that scaffold operation
    // ran. This is more of a control than a test.
    $this->mustExec("composer install --no-ansi", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', FALSE, 'A settings.php fixture file scaffolded from the scaffold-override-fixture');

    // Next, bring back packagist.org and install core-composer-scaffold:8.8.0.
    // Packagist is disabled in the fixture; we bring it back by removing the
    // line that disables it.
    $this->mustExec("composer config --unset repositories.packagist.org", $sut);
    $this->mustExec("composer config --unset repositories.composer-scaffold", $sut);
    $stdout = $this->mustExec("composer require --no-ansi drupal/core-composer-scaffold:9.5.0 --no-plugins 2>&1", $sut);
    $this->assertStringContainsString("  - Installing drupal/core-composer-scaffold (9.5.0):", $stdout);

    // We can't force the path repo to re-install over the stable version
    // without removing it, and removing it masks the bugs we are testing for.
    // We will therefore make a git repo so that we can tag an explicit version
    // to require.
    $testVersion = '99.99.99';
    $scaffoldPluginTmpRepo = $this->createTmpRepo($this->fixtures->projectRoot(), $this->fixturesDir, $testVersion);

    // Disable packagist.org and upgrade back to the Scaffold plugin under test.
    // This puts the `"packagist.org": false` config line back in composer.json
    // so that Packagist will no longer be used.
    $this->mustExec("composer config repositories.packagist.org false", $sut);
    $this->mustExec("composer config repositories.composer-scaffold vcs 'file:///$scaffoldPluginTmpRepo'", $sut);

    // Using 'mustExec' was giving a strange binary string here.
    $output = $this->mustExec("composer require --no-ansi drupal/core-composer-scaffold:$testVersion 2>&1", $sut);
    $this->assertStringContainsString("Installing drupal/core-composer-scaffold ($testVersion)", $output);

    // Remove a scaffold file and run the scaffold command again to prove that
    // scaffolding is still working.
    unlink("$sut/index.php");
    $stdout = $this->mustExec("composer scaffold", $sut);
    $this->assertStringContainsString("Scaffolding files for", $stdout);
    $this->assertFileExists("$sut/index.php");
  }

  /**
   * Tests upgrading the plugin when the Handler class is stale.
   *
   * @see \Drupal\Composer\Plugin\Scaffold\Plugin::ensureAutoloadRuntimeFile()
   */
  public function testUpgradeWithStaleHandler(): void {
    $this->fixturesDir = $this->fixtures->tmpDir($this->name());
    $pluginSource = $this->fixturesDir . '/composer-scaffold-plugin';
    $replacements = ['SYMLINK' => 'false', 'PROJECT_ROOT' => $pluginSource];
    $this->fixtures->cloneFixtureProjects($this->fixturesDir, $replacements);
    $sut = $this->fixturesDir . '/drupal-drupal';
    // The Scaffold plugin must be copied rather than symlinked into the
    // vendor directory, so that the installed plugin keeps the old code
    // after the plugin source is updated below.
    $composerJson = json_decode(file_get_contents("$sut/composer.json"), TRUE);
    $composerJson['repositories']['composer-scaffold']['options']['symlink'] = FALSE;
    file_put_contents("$sut/composer.json", json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    // Create an "old" copy of the Scaffold plugin that behaves like the
    // plugin from before Drupal 11.4.0: it knows nothing about
    // autoload_runtime.php.
    $this->copyPluginVersion($pluginSource, '100.0.0', TRUE);
    $this->mustExec('composer install --no-ansi', $sut);
    $this->assertFileExists("$sut/autoload.php");
    $this->assertFileDoesNotExist("$sut/autoload_runtime.php");

    // Replace the plugin source with the current code, then require a
    // package containing a Composer plugin flagged with
    // 'plugin-modifies-downloads'. Composer will install that package before
    // the Scaffold plugin's update operation, so its post-package-install
    // event fires while the old Scaffold plugin code is still installed,
    // pinning the old Handler class for the remainder of the process.
    $this->copyPluginVersion($pluginSource, '100.0.1', FALSE);
    $composerJson['repositories']['downloads-modifier'] = [
      'type' => 'path',
      'url' => '../composer-plugin-downloads-modifier',
    ];
    $composerJson['require']['fixtures/composer-plugin-downloads-modifier'] = '*';
    $composerJson['config']['allow-plugins']['fixtures/composer-plugin-downloads-modifier'] = TRUE;
    file_put_contents("$sut/composer.json", json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $stdout = $this->mustExec('composer update --no-ansi --no-interaction 2>&1', $sut);

    // Verify that this test executed the stale Handler class: the
    // downloads-modifier plugin must have been installed before the Scaffold
    // plugin was updated, and the marker written by the stale Handler code
    // must appear in the output.
    $installPos = strpos($stdout, 'Installing fixtures/composer-plugin-downloads-modifier');
    $upgradePos = strpos($stdout, 'Upgrading drupal/core-composer-scaffold (100.0.0 => 100.0.1):');
    $this->assertNotFalse($installPos, $stdout);
    $this->assertNotFalse($upgradePos, $stdout);
    $this->assertLessThan($upgradePos, $installPos, $stdout);
    $this->assertStringContainsString('SCAFFOLD_TEST_STALE_HANDLER', $stdout);

    // Even though the stale Handler did not generate autoload_runtime.php,
    // the refreshed Plugin class must have generated it.
    $this->assertFileExists("$sut/autoload_runtime.php");
    $this->assertStringContainsString("require __DIR__ . '/vendor/autoload_runtime.php'", file_get_contents("$sut/autoload_runtime.php"));
  }

  /**
   * Copies the Scaffold plugin source to a target directory with a version.
   *
   * @param string $target
   *   Directory to place the plugin copy in.
   * @param string $version
   *   Version to set in the composer.json of the copy, so that the path
   *   repository provides an update when the version is bumped.
   * @param bool $stale
   *   When TRUE, remove all knowledge of autoload_runtime.php from the copy
   *   to simulate the plugin from before Drupal 11.4.0. A marker is written
   *   to output by the modified Handler so that tests can verify that the
   *   stale Handler code ran.
   */
  protected function copyPluginVersion(string $target, string $version, bool $stale): void {
    $filesystem = new Filesystem();
    $filesystem->remove($target);
    $filesystem->copy($this->fixtures->projectRoot(), $target);
    $composerJson = json_decode(file_get_contents("$target/composer.json"), TRUE);
    $composerJson['version'] = $version;
    file_put_contents("$target/composer.json", json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($stale) {
      $generateRuntime = <<<'PHP'
    // The same is done for the autoload_runtime file that loads the Symfony
    // runtime.
    if (!GenerateAutoloadRuntimeReferenceFile::autoloadRuntimeFileCommitted($this->io, $this->rootPackageName(), $web_root)) {
      $scaffold_results[] = GenerateAutoloadRuntimeReferenceFile::generateAutoloadRuntime($this->io, $this->rootPackageName(), $web_root, $this->getVendorPath());
    }
PHP;
      $this->replaceCodeInFile("$target/Handler.php", $generateRuntime, "    \$this->io->write('SCAFFOLD_TEST_STALE_HANDLER');");
      $this->replaceCodeInFile("$target/Plugin.php", "    \$this->ensureAutoloadRuntimeFile();\n", '');
      unlink("$target/GenerateAutoloadRuntimeReferenceFile.php");
    }
  }

  /**
   * Replaces a code fragment in a file.
   *
   * @param string $path
   *   Path of the file to modify.
   * @param string $search
   *   The code fragment to replace.
   * @param string $replace
   *   The replacement code fragment.
   */
  protected function replaceCodeInFile(string $path, string $search, string $replace): void {
    $contents = file_get_contents($path);
    $this->assertStringContainsString($search, $contents, "Expected code not found in $path; update this test to match the current code.");
    file_put_contents($path, str_replace($search, $replace, $contents));
  }

  /**
   * Copy the provided source directory and create a temporary git repository.
   *
   * @param string $source
   *   Path to directory to copy.
   * @param string $destParent
   *   Path to location to create git repository.
   * @param string $version
   *   Version to tag the repository with.
   *
   * @return string
   *   Path to temporary git repository.
   */
  protected function createTmpRepo($source, $destParent, $version): string {
    $target = $destParent . '/' . basename($source);
    $filesystem = new Filesystem();
    $filesystem->copy($source, $target);
    $this->mustExec("git init", $target);
    $this->mustExec('git config user.email "scaffoldtest@example.com"', $target);
    $this->mustExec('git config user.name "Scaffold Test"', $target);
    $this->mustExec("git add .", $target);
    $this->mustExec("git commit -m 'Initial commit'", $target);
    $this->mustExec("git tag $version", $target);
    return $target;
  }

}
