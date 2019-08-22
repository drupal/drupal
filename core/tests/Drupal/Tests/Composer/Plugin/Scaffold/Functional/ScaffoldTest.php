<?php

namespace Drupal\Tests\Composer\Plugin\Scaffold\Functional;

use Composer\Util\Filesystem;
use Drupal\Tests\Composer\Plugin\Scaffold\AssertUtilsTrait;
use Drupal\Tests\Composer\Plugin\Scaffold\Fixtures;
use Drupal\Tests\Composer\Plugin\Scaffold\ScaffoldTestResult;
use PHPUnit\Framework\TestCase;

/**
 * Tests Composer Scaffold.
 *
 * The purpose of this test file is to exercise all of the different kinds of
 * scaffold operations: copy, symlinks, skips and so on.
 *
 * @group Scaffold
 */
class ScaffoldTest extends TestCase {
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
   * @var \Drupal\Tests\Composer\Plugin\Scaffold\Fixtures
   */
  protected $fixtures;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->fileSystem = new Filesystem();
    $this->fixtures = new Fixtures();
    $this->fixtures->createIsolatedComposerCacheDir();
    $this->projectRoot = $this->fixtures->projectRoot();
    // The directory used for creating composer projects to test can be
    // configured using the SCAFFOLD_FIXTURE_DIR environment variable. Otherwise
    // a directory will be created in the system's temporary directory.
    $this->fixturesDir = getenv('SCAFFOLD_FIXTURE_DIR');
    if (!$this->fixturesDir) {
      $this->fixturesDir = $this->fixtures->tmpDir($this->getName());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Remove any temporary directories et. al. that were created.
    $this->fixtures->tearDown();
  }

  /**
   * Creates the System-Under-Test.
   *
   * @param string $fixture_name
   *   The name of the fixture to use from
   *   core/tests/Drupal/Tests/Component/Scaffold/fixtures.
   * @param array $replacements
   *   Key : value mappings for placeholders to replace in composer.json
   *   templates.
   *
   * @return string
   *   The path to the created System-Under-Test.
   */
  protected function createSut($fixture_name, array $replacements = []) {
    $sut = $this->fixturesDir . '/' . $fixture_name;
    // Erase just our sut, to ensure it is clean. Recopy all of the fixtures.
    $this->fileSystem->remove($sut);
    $replacements += ['PROJECT_ROOT' => $this->projectRoot];
    $this->fixtures->cloneFixtureProjects($this->fixturesDir, $replacements);
    return $sut;
  }

  /**
   * Creates the system-under-test and runs a scaffold operation on it.
   *
   * @param string $fixture_name
   *   The name of the fixture to use from
   *   core/tests/Drupal/Tests/Component/Scaffold/fixtures.
   * @param bool $is_link
   *   Whether to use symlinks for 'replace' operations.
   * @param bool $relocated_docroot
   *   Whether the named fixture has a relocated document root.
   */
  public function scaffoldSut($fixture_name, $is_link = FALSE, $relocated_docroot = TRUE) {
    $sut = $this->createSut($fixture_name, ['SYMLINK' => $is_link ? 'true' : 'false']);
    // Run composer install to get the dependencies we need to test.
    $this->fixtures->runComposer("install --no-ansi --no-scripts", $sut);
    // Test composer:scaffold.
    $scaffoldOutput = $this->fixtures->runScaffold($sut);

    // Calculate the docroot directory and assert that our fixture layout
    // matches what was stipulated in $relocated_docroot. Fail fast if
    // the caller provided the wrong value.
    $docroot = $sut;
    if ($relocated_docroot) {
      $docroot .= '/docroot';
      $this->assertFileExists($docroot);
    }
    else {
      $this->assertFileNotExists($sut . '/docroot');
    }

    return new ScaffoldTestResult($docroot, $scaffoldOutput);
  }

  /**
   * Data provider for testScaffoldWithExpectedException.
   */
  public function scaffoldExpectedExceptionTestValues() {
    return [
      [
        'drupal-drupal-missing-scaffold-file',
        'Scaffold file assets/missing-robots-default.txt not found in package fixtures/drupal-drupal-missing-scaffold-file.',
        TRUE,
      ],

      [
        'project-with-empty-scaffold-path',
        'No scaffold file path given for [web-root]/my-error in package fixtures/project-with-empty-scaffold-path',
        FALSE,
      ],

      [
        'project-with-illegal-dir-scaffold',
        'Scaffold file assets in package fixtures/project-with-illegal-dir-scaffold is a directory; only files may be scaffolded',
        FALSE,
      ],
    ];
  }

  /**
   * Tests that scaffold files throw when they have bad values.
   *
   * @param string $fixture_name
   *   The name of the fixture to use from
   *   core/tests/Drupal/Tests/Component/Scaffold/fixtures.
   * @param string $expected_exception_message
   *   The expected exception message.
   * @param bool $is_link
   *   Whether or not symlinking should be used.
   *
   * @dataProvider scaffoldExpectedExceptionTestValues
   */
  public function testScaffoldWithExpectedException($fixture_name, $expected_exception_message, $is_link) {
    // Test scaffold. Expect an error.
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage($expected_exception_message);
    $this->scaffoldSut($fixture_name, $is_link);
  }

  /**
   * Try to scaffold a project that does not scaffold anything.
   */
  public function testEmptyProject() {
    $fixture_name = 'empty-fixture';

    $result = $this->scaffoldSut($fixture_name, FALSE, FALSE);
    $this->assertContains('Nothing scaffolded because no packages are allowed in the top-level composer.json file', $result->scaffoldOutput());
  }

  /**
   * Try to scaffold a project that allows a project with no scaffold files.
   */
  public function testProjectThatScaffoldsEmptyProject() {
    $fixture_name = 'project-allowing-empty-fixture';
    $is_link = FALSE;
    $result = $this->scaffoldSut($fixture_name, FALSE, FALSE);
    $this->assertContains('The allowed package fixtures/empty-fixture does not provide a file mapping for Composer Scaffold', $result->scaffoldOutput());
    $this->assertCommonDrupalAssetsWereScaffolded($result->docroot(), FALSE);
  }

  public function scaffoldOverridingSettingsExcludingHtaccessValues() {
    return [
      [
        'drupal-composer-drupal-project',
        TRUE,
        TRUE,
      ],

      [
        'drupal-drupal',
        FALSE,
        FALSE,
      ],
    ];
  }

  /**
   * Asserts that the drupal/assets scaffold files correct for sut.
   *
   * @param string $fixture_name
   *   The name of the fixture to use from
   *   core/tests/Drupal/Tests/Component/Scaffold/fixtures.
   * @param bool $is_link
   *   Whether to use symlinks for 'replace' operations.
   * @param bool $relocated_docroot
   *   Whether the named fixture has a relocated document root.
   *
   * @dataProvider scaffoldOverridingSettingsExcludingHtaccessValues
   */
  public function testScaffoldOverridingSettingsExcludingHtaccess($fixture_name, $is_link, $relocated_docroot) {
    $result = $this->scaffoldSut($fixture_name, $is_link, $relocated_docroot);

    $this->assertCommonDrupalAssetsWereScaffolded($result->docroot(), $is_link);
    $this->assertDefaultSettingsFromScaffoldOverride($result->docroot(), $is_link);
    $this->assertHtaccessExcluded($result->docroot());
  }

  /**
   * Asserts that the appropriate file was replaced.
   *
   * Check the drupal/drupal-based project to confirm that the expected file was
   * replaced, and that files that were not supposed to be replaced remain
   * unchanged.
   */
  public function testDrupalDrupalFileWasReplaced() {
    $fixture_name = 'drupal-drupal-test-overwrite';
    $result = $this->scaffoldSut($fixture_name, FALSE, FALSE);

    $this->assertScaffoldedFile($result->docroot() . '/replace-me.txt', FALSE, 'from assets that replaces file');
    $this->assertScaffoldedFile($result->docroot() . '/keep-me.txt', FALSE, 'File in drupal-drupal-test-overwrite that is not replaced');
    $this->assertScaffoldedFile($result->docroot() . '/make-me.txt', FALSE, 'from assets that replaces file');
    $this->assertCommonDrupalAssetsWereScaffolded($result->docroot(), FALSE);
    $this->assertScaffoldedFile($result->docroot() . '/robots.txt', FALSE, $fixture_name);
  }

  /**
   * Test values for testDrupalDrupalFileWasAppended.
   */
  public function scaffoldAppendTestValues() {
    return array_merge(
      $this->scaffoldAppendTestValuesToPermute(FALSE),
      $this->scaffoldAppendTestValuesToPermute(TRUE)
    );
  }

  /**
   * Test values to run both with $is_link FALSE and $is_link TRUE.
   *
   * @param bool $is_link
   *   Whether or not symlinking should be used.
   */
  protected function scaffoldAppendTestValuesToPermute($is_link) {
    return [
      [
        'drupal-drupal-test-append',
        $is_link,
        '# robots.txt fixture scaffolded from "file-mappings" in drupal-drupal-test-append composer.json fixture.
# This content is prepended to the top of the existing robots.txt fixture.
# ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

# Test version of robots.txt from drupal/core.

# ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
# This content is appended to the bottom of the existing robots.txt fixture.
# robots.txt fixture scaffolded from "file-mappings" in drupal-drupal-test-append composer.json fixture.
',
      ],

      [
        'drupal-drupal-append-to-append',
        $is_link,
        '# robots.txt fixture scaffolded from "file-mappings" in drupal-drupal-append-to-append composer.json fixture.
# This content is prepended to the top of the existing robots.txt fixture.
# ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

# Test version of robots.txt from drupal/core.

# ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
# This content is appended to the bottom of the existing robots.txt fixture.
# robots.txt fixture scaffolded from "file-mappings" in profile-with-append composer.json fixture.

# ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
# This content is appended to the bottom of the existing robots.txt fixture.
# robots.txt fixture scaffolded from "file-mappings" in drupal-drupal-append-to-append composer.json fixture.',
      ],
    ];
  }

  /**
   * Tests a fixture where the robots.txt file is prepended / appended to.
   *
   * @param string $fixture_name
   *   The name of the fixture to use from
   *   core/tests/Drupal/Tests/Component/Scaffold/fixtures.
   * @param bool $is_link
   *   Whether or not symlinking should be used.
   * @param string $robots_txt_contents
   *   Regular expression matching expectations for robots.txt.
   *
   * @dataProvider scaffoldAppendTestValues
   */
  public function testDrupalDrupalFileWasAppended($fixture_name, $is_link, $robots_txt_contents) {
    $result = $this->scaffoldSut($fixture_name, $is_link, FALSE);

    $this->assertScaffoldedFile($result->docroot() . '/robots.txt', FALSE, $robots_txt_contents);
    $this->assertCommonDrupalAssetsWereScaffolded($result->docroot(), $is_link);
  }

  /**
   * Asserts that the default settings file was overridden by the test.
   *
   * @param string $docroot
   *   The path to the System-under-Test's docroot.
   * @param bool $is_link
   *   Whether or not symlinking is used.
   */
  protected function assertDefaultSettingsFromScaffoldOverride($docroot, $is_link) {
    $this->assertScaffoldedFile($docroot . '/sites/default/default.settings.php', $is_link, 'scaffolded from the scaffold-override-fixture');
  }

  /**
   * Asserts that the .htaccess file was excluded by the test.
   *
   * @param string $docroot
   *   The path to the System-under-Test's docroot.
   */
  protected function assertHtaccessExcluded($docroot) {
    // Ensure that the .htaccess.txt file was not written, as our
    // top-level composer.json excludes it from the files to scaffold.
    $this->assertFileNotExists($docroot . '/.htaccess');
  }

  /**
   * Asserts that the scaffold files from drupal/assets are placed as expected.
   *
   * This tests that all assets from drupal/assets were scaffolded, save
   * for .htaccess, robots.txt and default.settings.php, which are scaffolded
   * in different ways in different tests.
   *
   * @param string $docroot
   *   The path to the System-under-Test's docroot.
   * @param bool $is_link
   *   Whether or not symlinking is used.
   */
  protected function assertCommonDrupalAssetsWereScaffolded($docroot, $is_link) {
    // Ensure that the autoload.php file was written.
    $this->assertFileExists($docroot . '/autoload.php');
    // Assert other scaffold files are written in the correct locations.
    $this->assertScaffoldedFile($docroot . '/.csslintrc', $is_link, 'Test version of .csslintrc from drupal/core.');
    $this->assertScaffoldedFile($docroot . '/.editorconfig', $is_link, 'Test version of .editorconfig from drupal/core.');
    $this->assertScaffoldedFile($docroot . '/.eslintignore', $is_link, 'Test version of .eslintignore from drupal/core.');
    $this->assertScaffoldedFile($docroot . '/.eslintrc.json', $is_link, 'Test version of .eslintrc.json from drupal/core.');
    $this->assertScaffoldedFile($docroot . '/.gitattributes', $is_link, 'Test version of .gitattributes from drupal/core.');
    $this->assertScaffoldedFile($docroot . '/.ht.router.php', $is_link, 'Test version of .ht.router.php from drupal/core.');
    $this->assertScaffoldedFile($docroot . '/sites/default/default.services.yml', $is_link, 'Test version of default.services.yml from drupal/core.');
    $this->assertScaffoldedFile($docroot . '/sites/example.settings.local.php', $is_link, 'Test version of example.settings.local.php from drupal/core.');
    $this->assertScaffoldedFile($docroot . '/sites/example.sites.php', $is_link, 'Test version of example.sites.php from drupal/core.');
    $this->assertScaffoldedFile($docroot . '/index.php', $is_link, 'Test version of index.php from drupal/core.');
    $this->assertScaffoldedFile($docroot . '/update.php', $is_link, 'Test version of update.php from drupal/core.');
    $this->assertScaffoldedFile($docroot . '/web.config', $is_link, 'Test version of web.config from drupal/core.');
  }

}
