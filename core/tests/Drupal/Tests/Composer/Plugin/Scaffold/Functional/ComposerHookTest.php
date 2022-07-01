<?php

namespace Drupal\Tests\Composer\Plugin\Scaffold\Functional;

use Composer\Util\Filesystem;
use Drupal\BuildTests\Framework\BuildTestBase;
use Drupal\Tests\Composer\Plugin\Scaffold\AssertUtilsTrait;
use Drupal\Tests\Composer\Plugin\Scaffold\ExecTrait;
use Drupal\Tests\Composer\Plugin\Scaffold\Fixtures;

/**
 * Tests Composer Hooks that run scaffold operations.
 *
 * The purpose of this test file is to exercise all of the different Composer
 * commands that invoke scaffold operations, and ensure that files are
 * scaffolded when they should be.
 *
 * Note that this test file uses `exec` to run Composer for a pure functional
 * test. Other functional test files invoke Composer commands directly via the
 * Composer Application object, in order to get more accurate test coverage
 * information.
 *
 * @group Scaffold
 */
class ComposerHookTest extends BuildTestBase {

  use ExecTrait;
  use AssertUtilsTrait;

  /**
   * Directory to perform the tests in.
   *
   * @var string
   */
  protected $fixturesDir;

  /**
   * The Symfony FileSystem component.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fileSystem;

  /**
   * The Fixtures object.
   *
   * @var \Drupal\Tests\Composer\Plugin\Scaffold\Fixtures
   */
  protected $fixtures;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->fileSystem = new Filesystem();
    $this->fixtures = new Fixtures();
    $this->fixtures->createIsolatedComposerCacheDir();
    $this->fixturesDir = $this->fixtures->tmpDir($this->getName());
    $replacements = ['SYMLINK' => 'false', 'PROJECT_ROOT' => $this->fixtures->projectRoot()];
    $this->fixtures->cloneFixtureProjects($this->fixturesDir, $replacements);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Remove any temporary directories et. al. that were created.
    $this->fixtures->tearDown();
  }

  /**
   * Tests to see if scaffold operation runs at the correct times.
   */
  public function testComposerHooks() {
    $topLevelProjectDir = 'composer-hooks-fixture';
    $sut = $this->fixturesDir . '/' . $topLevelProjectDir;
    // First test: run composer install. This is the same as composer update
    // since there is no lock file. Ensure that scaffold operation ran.
    $this->mustExec("composer install --no-ansi", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', FALSE, 'Test version of default.settings.php from drupal/core');
    // Run composer required to add in the scaffold-override-fixture. This
    // project is "allowed" in our main fixture project, but not required.
    // We expect that requiring this library should re-scaffold, resulting
    // in a changed default.settings.php file.
    $stdout = $this->mustExec("composer require --no-ansi --no-interaction fixtures/drupal-assets-fixture:dev-main fixtures/scaffold-override-fixture:dev-main", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', FALSE, 'scaffolded from the scaffold-override-fixture');
    // Make sure that the appropriate notice informing us that scaffolding
    // is allowed was printed.
    $this->assertStringContainsString('Package fixtures/scaffold-override-fixture has scaffold operations, and is already allowed in the root-level composer.json file.', $stdout);
    // Delete one scaffold file, just for test purposes, then run
    // 'composer update' and see if the scaffold file is replaced.
    @unlink($sut . '/sites/default/default.settings.php');
    $this->assertFileDoesNotExist($sut . '/sites/default/default.settings.php');
    $this->mustExec("composer update --no-ansi", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', FALSE, 'scaffolded from the scaffold-override-fixture');
    // Delete the same test scaffold file again, then run
    // 'composer drupal:scaffold' and see if the scaffold file is
    // re-scaffolded.
    @unlink($sut . '/sites/default/default.settings.php');
    $this->assertFileDoesNotExist($sut . '/sites/default/default.settings.php');
    $this->mustExec("composer install --no-ansi", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', FALSE, 'scaffolded from the scaffold-override-fixture');
    // Delete the same test scaffold file yet again, then run
    // 'composer install' and see if the scaffold file is re-scaffolded.
    @unlink($sut . '/sites/default/default.settings.php');
    $this->assertFileDoesNotExist($sut . '/sites/default/default.settings.php');
    $this->mustExec("composer drupal:scaffold --no-ansi", $sut);
    $this->assertScaffoldedFile($sut . '/sites/default/default.settings.php', FALSE, 'scaffolded from the scaffold-override-fixture');
    // Run 'composer create-project' to create a new test project called
    // 'create-project-test', which is a copy of 'fixtures/drupal-drupal'.
    $sut = $this->fixturesDir . '/create-project-test';
    $filesystem = new Filesystem();
    $filesystem->remove($sut);
    $stdout = $this->mustExec("composer create-project --repository=packages.json fixtures/drupal-drupal {$sut}", $this->fixturesDir, ['COMPOSER_MIRROR_PATH_REPOS' => 1]);
    $this->assertDirectoryExists($sut);
    $this->assertStringContainsString('Scaffolding files for fixtures/drupal-drupal', $stdout);
    $this->assertScaffoldedFile($sut . '/index.php', FALSE, 'Test version of index.php from drupal/core');
    $topLevelProjectDir = 'composer-hooks-nothing-allowed-fixture';
    $sut = $this->fixturesDir . '/' . $topLevelProjectDir;
    // Run composer install on an empty project.
    $this->mustExec("composer install --no-ansi", $sut);
    // Require a project that is not allowed to scaffold and confirm that we
    // get a warning, and it does not scaffold.
    $this->executeCommand("composer require --no-ansi --no-interaction fixtures/drupal-assets-fixture:dev-main fixtures/scaffold-override-fixture:dev-main", $sut);
    $this->assertCommandSuccessful();
    $this->assertFileDoesNotExist($sut . '/sites/default/default.settings.php');
    $this->assertErrorOutputContains('See https://getcomposer.org/allow-plugins');
  }

  /**
   * Tests to see if scaffold messages are omitted when running scaffold twice.
   */
  public function testScaffoldMessagesDoNotPrintTwice() {
    $topLevelProjectDir = 'drupal-drupal';
    $sut = $this->fixturesDir . '/' . $topLevelProjectDir;
    // First test: run composer install. This is the same as composer update
    // since there is no lock file. Ensure that scaffold operation ran.
    $stdout = $this->mustExec("composer install --no-ansi", $sut);

    $this->assertStringContainsString('- Copy [web-root]/index.php from assets/index.php', $stdout);
    $this->assertStringContainsString('- Copy [web-root]/update.php from assets/update.php', $stdout);

    // Run scaffold operation again. It should not print anything.
    $stdout = $this->mustExec("composer scaffold --no-ansi", $sut);

    $this->assertEquals('', $stdout);

    // Delete a file and run it again. It should re-scaffold the removed file.
    unlink("$sut/index.php");
    $stdout = $this->mustExec("composer scaffold --no-ansi", $sut);
    $this->assertStringContainsString('- Copy [web-root]/index.php from assets/index.php', $stdout);
    $this->assertStringNotContainsString('- Copy [web-root]/update.php from assets/update.php', $stdout);
  }

}
