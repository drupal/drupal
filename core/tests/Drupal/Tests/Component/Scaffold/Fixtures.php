<?php

namespace Drupal\Tests\Component\Scaffold;

use Composer\Console\Application;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Util\Filesystem;
use Drupal\Component\Scaffold\Handler;
use Drupal\Component\Scaffold\Interpolator;
use Drupal\Component\Scaffold\Operations\AppendOp;
use Drupal\Component\Scaffold\Operations\ReplaceOp;
use Drupal\Component\Scaffold\ScaffoldFilePath;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Convenience class for creating fixtures.
 */
class Fixtures {

  /**
   * Keep a persistent prefix to help group our tmp directories together.
   *
   * @var string
   */
  protected static $randomPrefix = '';

  /**
   * Directories to delete when we are done.
   *
   * @var string[]
   */
  protected $tmpDirs = [];

  /**
   * A Composer IOInterface to write to.
   *
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * The composer object.
   *
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * Gets an IO fixture.
   *
   * @return \Composer\IO\IOInterface
   *   A Composer IOInterface to write to; output may be retrieved via
   *   Fixtures::getOutput().
   */
  public function io() {
    if (!$this->io) {
      $this->io = new BufferIO();
    }
    return $this->io;
  }

  /**
   * Gets the Composer object.
   *
   * @return \Composer\Composer
   *   The main Composer object, needed by the scaffold Handler, etc.
   */
  public function getComposer() {
    if (!$this->composer) {
      $this->composer = Factory::create($this->io(), NULL, TRUE);
    }
    return $this->composer;
  }

  /**
   * Gets the output from the io() fixture.
   *
   * @return string
   *   Output captured from tests that write to Fixtures::io().
   */
  public function getOutput() {
    return $this->io()->getOutput();
  }

  /**
   * Gets the path to Scaffold component.
   *
   * Used to inject the component into composer.json files.
   *
   * @return string
   *   Path to the root of this project.
   */
  public function projectRoot() {
    return realpath(__DIR__) . '/../../../../../../core/lib/Drupal/Component/Scaffold';
  }

  /**
   * Gets the path to the project fixtures.
   *
   * @return string
   *   Path to project fixtures
   */
  public function allFixturesDir() {
    return realpath(__DIR__ . '/fixtures');
  }

  /**
   * Gets the path to one particular project fixture.
   *
   * @param string $project_name
   *   The project name to get the path for.
   *
   * @return string
   *   Path to project fixture.
   */
  public function projectFixtureDir($project_name) {
    $dir = $this->allFixturesDir() . '/' . $project_name;
    if (!is_dir($dir)) {
      throw new \RuntimeException("Requested fixture project {$project_name} that does not exist.");
    }
    return $dir;
  }

  /**
   * Gets the path to one particular bin path.
   *
   * @param string $bin_name
   *   The bin name to get the path for.
   *
   * @return string
   *   Path to project fixture.
   */
  public function binFixtureDir($bin_name) {
    $dir = $this->allFixturesDir() . '/scripts/' . $bin_name;
    if (!is_dir($dir)) {
      throw new \RuntimeException("Requested fixture bin dir {$bin_name} that does not exist.");
    }
    return $dir;
  }

  /**
   * Gets a path to a source scaffold fixture.
   *
   * Use in place of ScaffoldFilePath::sourcePath().
   *
   * @param string $project_name
   *   The name of the project to fetch; $package_name is
   *   "fixtures/$project_name".
   * @param string $source
   *   The name of the asset; path is "assets/$source".
   *
   * @return \Drupal\Component\Scaffold\ScaffoldFilePath
   *   The full and relative path to the desired asset
   *
   * @see \Drupal\Component\Scaffold\ScaffoldFilePath::sourcePath()
   */
  public function sourcePath($project_name, $source) {
    $package_name = "fixtures/{$project_name}";
    $source_rel_path = "assets/{$source}";
    $package_path = $this->projectFixtureDir($project_name);
    return ScaffoldFilePath::sourcePath($package_name, $package_path, 'unknown', $source_rel_path);
  }

  /**
   * Gets an Interpolator with 'web-root' and 'package-name' set.
   *
   * Use in place of ManageOptions::getLocationReplacements().
   *
   * @return \Drupal\Component\Scaffold\Interpolator
   *   An interpolator with location replacements, including 'web-root'.
   *
   * @see \Drupal\Component\Scaffold\ManageOptions::getLocationReplacements()
   */
  public function getLocationReplacements() {
    $destinationTmpDir = $this->mkTmpDir('location-replacements');
    $interpolator = new Interpolator();
    $interpolator->setData(['web-root' => $destinationTmpDir, 'package-name' => 'fixtures/tmp-destination']);
    return $interpolator;
  }

  /**
   * Creates a ReplaceOp fixture.
   *
   * @param string $project_name
   *   The name of the project to fetch; $package_name is
   *   "fixtures/$project_name".
   * @param string $source
   *   The name of the asset; path is "assets/$source".
   *
   * @return \Drupal\Component\Scaffold\Operations\ReplaceOp
   *   A replace operation object.
   */
  public function replaceOp($project_name, $source) {
    $source_path = $this->sourcePath($project_name, $source);
    return new ReplaceOp($source_path, TRUE);
  }

  /**
   * Creates an AppendOp fixture.
   *
   * @param string $project_name
   *   The name of the project to fetch; $package_name is
   *   "fixtures/$project_name".
   * @param string $source
   *   The name of the asset; path is "assets/$source".
   *
   * @return \Drupal\Component\Scaffold\Operations\AppendOp
   *   An append operation object.
   */
  public function appendOp($project_name, $source) {
    $source_path = $this->sourcePath($project_name, $source);
    return new AppendOp(NULL, $source_path);
  }

  /**
   * Gets a destination path in a tmp dir.
   *
   * Use in place of ScaffoldFilePath::destinationPath().
   *
   * @param string $destination
   *   Destination path; should be in the form '[web-root]/robots.txt', where
   *   '[web-root]' is always literally '[web-root]', with any arbitrarily
   *   desired filename following.
   * @param \Drupal\Component\Scaffold\Interpolator $interpolator
   *   Location replacements. Obtain via Fixtures::getLocationReplacements()
   *   when creating multiple scaffold destinations.
   * @param string $package_name
   *   (optional) The name of the fixture package that this path came from.
   *   Taken from interpolator if not provided.
   *
   * @return \Drupal\Component\Scaffold\ScaffoldFilePath
   *   A destination scaffold file backed by temporary storage.
   *
   * @see \Drupal\Component\Scaffold\ScaffoldFilePath::destinationPath()
   */
  public function destinationPath($destination, Interpolator $interpolator = NULL, $package_name = NULL) {
    $interpolator = $interpolator ?: $this->getLocationReplacements();
    $package_name = $package_name ?: $interpolator->interpolate('[package-name]');
    return ScaffoldFilePath::destinationPath($package_name, $destination, $interpolator);
  }

  /**
   * Generates a path to a temporary location, but do not create the directory.
   *
   * @param string $prefix
   *   A prefix for the temporary directory name.
   *
   * @return string
   *   Path to temporary directory
   */
  public function tmpDir($prefix) {
    $prefix .= static::persistentPrefix();
    $tmpDir = sys_get_temp_dir() . '/scaffold-' . $prefix . uniqid(md5($prefix . microtime()), TRUE);
    $this->tmpDirs[] = $tmpDir;
    return $tmpDir;
  }

  /**
   * Generates a persistent prefix to use with all of our temporary directories.
   *
   * The presumption is that this should reduce collisions in highly-parallel
   * tests. We prepend the process id to play nicely with phpunit process
   * isolation.
   *
   * @return string
   *   A random string that will remain the same for the entire process run.
   */
  protected static function persistentPrefix() {
    if (empty(static::$randomPrefix)) {
      static::$randomPrefix = getmypid() . md5(microtime());
    }
    return static::$randomPrefix;
  }

  /**
   * Creates a temporary directory.
   *
   * @param string $prefix
   *   A prefix for the temporary directory name.
   *
   * @return string
   *   Path to temporary directory
   */
  public function mkTmpDir($prefix) {
    $tmpDir = $this->tmpDir($prefix);
    $filesystem = new Filesystem();
    $filesystem->ensureDirectoryExists($tmpDir);
    return $tmpDir;
  }

  /**
   * Calls 'tearDown' in any test that copies fixtures to transient locations.
   */
  public function tearDown() {
    // Remove any temporary directories that were created.
    $filesystem = new Filesystem();
    foreach ($this->tmpDirs as $dir) {
      $filesystem->remove($dir);
    }
    // Clear out variables from the previous pass.
    $this->tmpDirs = [];
    $this->io = NULL;
  }

  /**
   * Creates a temporary copy of all of the fixtures projects into a temp dir.
   *
   * The fixtures remain dirty if they already exist. Individual tests should
   * first delete any fixture directory that needs to remain pristine. Since all
   * temporary directories are removed in tearDown, this is only an issue when
   * a) the FIXTURE_DIR environment variable has been set, or b) tests are
   * calling cloneFixtureProjects more than once per test method.
   *
   * @param string $fixturesDir
   *   The directory to place fixtures in.
   * @param array $replacements
   *   Key : value mappings for placeholders to replace in composer.json
   *   templates.
   */
  public function cloneFixtureProjects($fixturesDir, array $replacements = []) {
    $filesystem = new Filesystem();
    // We will replace 'SYMLINK' with the string 'true' in our composer.json
    // fixture.
    $replacements += ['SYMLINK' => 'true'];
    $interpolator = new Interpolator('__', '__');
    $interpolator->setData($replacements);
    $filesystem->copy($this->allFixturesDir(), $fixturesDir);
    $composer_json_templates = glob($fixturesDir . "/*/composer.json.tmpl");
    foreach ($composer_json_templates as $composer_json_tmpl) {
      // Inject replacements into composer.json.
      if (file_exists($composer_json_tmpl)) {
        $composer_json_contents = file_get_contents($composer_json_tmpl);
        $composer_json_contents = $interpolator->interpolate($composer_json_contents, [], FALSE);
        file_put_contents(dirname($composer_json_tmpl) . "/composer.json", $composer_json_contents);
        @unlink($composer_json_tmpl);
      }
    }
  }

  /**
   * Runs the scaffold operation.
   *
   * This is equivalent to running `composer composer-scaffold`, but we do the
   * equivalent operation by instantiating a Handler object in order to continue
   * running in the same process, so that coverage may be calculated for the
   * code executed by these tests.
   *
   * @param string $cwd
   *   The working directory to run the scaffold command in.
   *
   * @return string
   *   Output captured from tests that write to Fixtures::io().
   */
  public function runScaffold($cwd) {
    chdir($cwd);
    $handler = new Handler($this->getComposer(), $this->io());
    $handler->scaffold();
    return $this->getOutput();
  }

  /**
   * Runs a `composer` command.
   *
   * @param string $cmd
   *   The Composer command to execute (escaped as required)
   * @param string $cwd
   *   The current working directory to run the command from.
   *
   * @return string
   *   Standard output and standard error from the command.
   */
  public function runComposer($cmd, $cwd) {
    chdir($cwd);
    $input = new StringInput($cmd);
    $output = new BufferedOutput();
    $application = new Application();
    $application->setAutoExit(FALSE);
    $exitCode = $application->run($input, $output);
    $output = $output->fetch();
    if ($exitCode != 0) {
      throw new \Exception("Fixtures::runComposer failed to set up fixtures.\n\nCommand: '{$cmd}'\nExit code: {$exitCode}\nOutput: \n\n$output");
    }
    return $output;
  }

}
