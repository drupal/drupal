<?php

namespace Drupal\Tests\Composer\Plugin\Scaffold\Functional;

use Drupal\Tests\Composer\Plugin\Scaffold\AssertUtilsTrait;
use Drupal\Tests\Composer\Plugin\Scaffold\ExecTrait;
use Drupal\Tests\Composer\Plugin\Scaffold\Fixtures;
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
    $stdout = $this->mustExec("composer require drupal/core-composer-scaffold:8.8.0 --no-plugins 2>&1", $sut);
    $this->assertContains("  - Installing drupal/core-composer-scaffold (8.8.0):", $stdout);

    // Disable packagist.org and upgrade back to the Scaffold plugin under test.
    // This puts the `"packagist.org": false` config line back in composer.json
    // so that Packagist will no longer be used.
    $this->mustExec("composer remove drupal/core-composer-scaffold --no-plugins", $sut);
    $this->mustExec("composer config repositories.packagist.org false", $sut);
    $stdout = $this->mustExec("composer require drupal/core-composer-scaffold:* 2>&1", $sut);
    $this->assertRegExp("#Installing drupal/core-composer-scaffold.*Symlinking from#", $stdout);
    // Remove a scaffold file and run the scaffold command again to prove that
    // scaffolding is still working.
    unlink("$sut/index.php");
    $stdout = $this->mustExec("composer scaffold", $sut);
    $this->assertContains("Scaffolding files for", $stdout);
    $this->assertFileExists("$sut/index.php");
  }

}
