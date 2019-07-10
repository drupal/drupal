<?php

namespace Drupal\Tests\Component\Scaffold\Functional;

use Composer\Util\Filesystem;
use Drupal\Tests\Component\Scaffold\Fixtures;
use Drupal\Tests\Component\Scaffold\AssertUtilsTrait;
use Drupal\Tests\Component\Scaffold\ExecTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests to see whether .gitignore files are correctly managed.
 *
 * The purpose of this test file is to run a scaffold operation and
 * confirm that the files that were scaffolded are added to the
 * repository's .gitignore file.
 *
 * @group Scaffold
 */
class ManageGitIgnoreTest extends TestCase {
  use ExecTrait;
  use AssertUtilsTrait;

  /**
   * The root of this project.
   *
   * Used to substitute this project's base directory into composer.json files
   * so Composer can find it.
   *
   * @var string
   */
  protected $projectRoot;

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
   * @var \Drupal\Tests\Component\Scaffold\Fixtures
   */
  protected $fixtures;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->fileSystem = new Filesystem();
    $this->fixtures = new Fixtures();
    $this->projectRoot = $this->fixtures->projectRoot();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Remove any temporary directories et. al. that were created.
    $this->fixtures->tearDown();
  }

  /**
   * Creates a system-under-test and initialize a git repository for it.
   *
   * @param string $fixture_name
   *   The name of the fixture to use from
   *   core/tests/Drupal/Tests/Component/Scaffold/fixtures.
   *
   * @return string
   *   The path to the fixture directory.
   */
  protected function createSutWithGit($fixture_name) {
    $this->fixturesDir = $this->fixtures->tmpDir($this->getName());
    $sut = $this->fixturesDir . '/' . $fixture_name;
    $replacements = ['SYMLINK' => 'false', 'PROJECT_ROOT' => $this->projectRoot];
    $this->fixtures->cloneFixtureProjects($this->fixturesDir, $replacements);
    // .gitignore files will not be managed unless there is a git repository.
    $this->mustExec('git init', $sut);
    // Add some user info so git does not complain.
    $this->mustExec('git config user.email "test@example.com"', $sut);
    $this->mustExec('git config user.name "Test User"', $sut);
    $this->mustExec('git add .', $sut);
    $this->mustExec('git commit -m "Initial commit."', $sut);
    // Run composer install, but suppress scaffolding.
    $this->fixtures->runComposer("install --no-ansi --no-scripts", $sut);
    return $sut;
  }

  /**
   * Tests scaffold command correctly manages the .gitignore file.
   */
  public function testManageGitIgnore() {
    // Note that the drupal-composer-drupal-project fixture does not
    // have any configuration settings related to .gitignore management.
    $sut = $this->createSutWithGit('drupal-composer-drupal-project');
    $this->assertFileNotExists($sut . '/docroot/index.php');
    $this->assertFileNotExists($sut . '/docroot/sites/.gitignore');
    // Run the scaffold command.
    $this->fixtures->runScaffold($sut);
    $this->assertFileExists($sut . '/docroot/index.php');
    $expected = <<<EOT
build
.csslintrc
.editorconfig
.eslintignore
.eslintrc.json
.gitattributes
.ht.router.php
autoload.php
index.php
robots.txt
update.php
web.config
EOT;
    // At this point we should have a .gitignore file, because although we did
    // not explicitly ask for .gitignore tracking, the vendor directory is not
    // tracked, so the default in that instance is to manage .gitignore files.
    $this->assertScaffoldedFile($sut . '/docroot/.gitignore', FALSE, $expected);
    $this->assertScaffoldedFile($sut . '/docroot/sites/.gitignore', FALSE, 'example.settings.local.php');
    $this->assertScaffoldedFile($sut . '/docroot/sites/default/.gitignore', FALSE, 'default.services.yml');
    $expected = <<<EOT
M docroot/.gitignore
?? docroot/sites/.gitignore
?? docroot/sites/default/.gitignore
EOT;
    // Check to see whether there are any untracked files. We expect that
    // only the .gitignore files themselves should be untracked.
    $stdout = $this->mustExec('git status --porcelain', $sut);
    $this->assertEquals(trim($expected), trim($stdout));
  }

  /**
   * Tests scaffold command does not manage the .gitignore file when disabled.
   */
  public function testUnmanagedGitIgnoreWhenDisabled() {
    // Note that the drupal-drupal fixture has a configuration setting
    // `"gitignore": false,` which disables .gitignore file handling.
    $sut = $this->createSutWithGit('drupal-drupal');
    $this->assertFileNotExists($sut . '/docroot/index.php');
    // Run the scaffold command.
    $this->fixtures->runScaffold($sut);
    $this->assertFileExists($sut . '/index.php');
    $this->assertFileNotExists($sut . '/.gitignore');
    $this->assertFileNotExists($sut . '/docroot/sites/default/.gitignore');
  }

  /**
   * Tests scaffold command disables .gitignore management when git not present.
   *
   * The scaffold operation should still succeed if there is no 'git'
   * executable.
   */
  public function testUnmanagedGitIgnoreWhenGitNotAvailable() {
    // Note that the drupal-composer-drupal-project fixture does not have any
    // configuration settings related to .gitignore management.
    $sut = $this->createSutWithGit('drupal-composer-drupal-project');
    $this->assertFileNotExists($sut . '/docroot/sites/default/.gitignore');
    $this->assertFileNotExists($sut . '/docroot/index.php');
    $this->assertFileNotExists($sut . '/docroot/sites/.gitignore');
    // Confirm that 'git' is available (n.b. if it were not, createSutWithGit()
    // would fail).
    exec('git --help', $output, $status);
    $this->assertEquals(0, $status);
    // Modify our $PATH so that it begins with a path that contains an
    // executable script named 'git' that always exits with 127, as if git were
    // not found. Note that we run our tests using process isolation, so we do
    // not need to restore the PATH when we are done.
    $unavailableGitPath = $this->fixtures->binFixtureDir('disable-git-bin');
    chmod($unavailableGitPath . '/git', 0755);
    putenv('PATH=' . $unavailableGitPath . ':' . getenv('PATH'));
    // Confirm that 'git' is no longer available.
    exec('git --help', $output, $status);
    $this->assertEquals(127, $status);
    // Run the scaffold command.
    exec('composer composer:scaffold', $output, $status);
    $this->assertFileExists($sut . '/docroot/index.php');
    $this->assertFileNotExists($sut . '/docroot/sites/default/.gitignore');
  }

}
