<?php

namespace Drupal\Tests\Composer\Plugin\Scaffold\Functional;

use Composer\Util\Filesystem;
use Drupal\Tests\Composer\Plugin\Scaffold\AssertUtilsTrait;
use Drupal\Tests\Composer\Plugin\Scaffold\ExecTrait;
use Drupal\Tests\Composer\Plugin\Scaffold\Fixtures;
use Drupal\Tests\PhpunitCompatibilityTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests Upgrading the Composer Scaffold plugin.
 *
 * Upgrading a Composer plugin can be a dangerous operation. If the plugin
 * instantiates any classes during the activate method, and the plugin code
 * is subsequentially modified by a `composer update` operation, then any
 * post-update hook (& etc.) may run with inconsistent code, leading to
 * runtime errors. This test ensures that it is possible to upgrade from the
 * last available stable 8.8.x tag to the current Scaffold plugin code (e.g. in
 * the current patch-under-test).
 *
 * @group Scaffold
 */
class ScaffoldUpgradeTest extends TestCase {

  use AssertUtilsTrait;
  use ExecTrait;
  use PhpunitCompatibilityTrait;

  /**
   * The Fixtures object.
   *
   * @var \Drupal\Tests\Composer\Plugin\Scaffold\Fixtures
   */
  protected $fixtures;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->fixtures = new Fixtures();
    $this->fixtures->createIsolatedComposerCacheDir();
  }

  /**
   * Test upgrading the Composer Scaffold plugin.
   */
  public function testScaffoldUpgrade() {
    $composerVersionLine = exec('composer --version');
    if (strpos($composerVersionLine, 'Composer version 2') !== FALSE) {
      $this->markTestSkipped('We cannot run the scaffold upgrade test with Composer 2 until we have a stable version of drupal/core-composer-scaffold to start from that we can install with Composer 2.x.');
    }
    $this->fixturesDir = $this->fixtures->tmpDir($this->getName());
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
    $stdout = $this->mustExec("composer require --no-ansi drupal/core-composer-scaffold:8.8.0 --no-plugins 2>&1", $sut);
    $this->assertStringContainsString("  - Installing drupal/core-composer-scaffold (8.8.0):", $stdout);

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
   * Copy the provided source directory and create a temporary git repository.
   *
   * @param string $source
   *   Path to directory to copy.
   * @param string $destParent
   *   Path to location to create git repository.
   * @param string $version
   *   Version to tag the repository with.
   * @return string
   *   Path to temporary git repository.
   */
  protected function createTmpRepo($source, $destParent, $version) {
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
