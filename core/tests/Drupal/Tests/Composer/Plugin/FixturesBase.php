<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Plugin;

use Composer\Composer;
use Composer\Console\Application;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Drupal\Composer\Plugin\Scaffold\Interpolator;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Base class for fixtures to test composer plugins.
 */
abstract class FixturesBase {

  /**
   * Keep a persistent prefix to help group our tmp directories together.
   *
   * @var string
   */
  protected static string $randomPrefix = '';

  /**
   * Directories to delete when we are done.
   *
   * @var string[]
   */
  protected array $tmpDirs = [];

  /**
   * A Composer IOInterface to write to.
   *
   * @var \Composer\IO\IOInterface|null
   */
  protected ?IOInterface $io;

  /**
   * The composer object.
   *
   * @var \Composer\Composer
   */
  protected Composer $composer;

  /**
   * Gets an IO fixture.
   *
   * @return \Composer\IO\IOInterface
   *   A Composer IOInterface to write to; output may be retrieved via
   *   Fixtures::getOutput().
   */
  public function io(): IOInterface {
    if (!isset($this->io)) {
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
  public function getComposer(): Composer {
    if (!isset($this->composer)) {
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
  public function getOutput(): string {
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
  abstract public function projectRoot(): string;

  /**
   * Gets the path to the project fixtures.
   *
   * @return string
   *   Path to project fixtures
   */
  abstract public function allFixturesDir(): string;

  /**
   * Gets the path to one particular project fixture.
   *
   * @param string $project_name
   *   The project name to get the path for.
   *
   * @return string
   *   Path to project fixture.
   */
  public function projectFixtureDir(string $project_name): string {
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
  public function binFixtureDir(string $bin_name): string {
    $dir = $this->allFixturesDir() . '/scripts/' . $bin_name;
    if (!is_dir($dir)) {
      throw new \RuntimeException("Requested fixture bin dir {$bin_name} that does not exist.");
    }
    return $dir;
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
  public function tmpDir(string $prefix): string {
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
  protected static function persistentPrefix(): string {
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
  public function mkTmpDir(string $prefix): string {
    $tmpDir = $this->tmpDir($prefix);
    $filesystem = new Filesystem();
    $filesystem->ensureDirectoryExists($tmpDir);
    return $tmpDir;
  }

  /**
   * Creates an isolated cache directory for Composer.
   */
  public function createIsolatedComposerCacheDir(): void {
    $cacheDir = $this->mkTmpDir('composer-cache');
    putenv("COMPOSER_CACHE_DIR=$cacheDir");
  }

  /**
   * Calls 'tearDown' in any test that copies fixtures to transient locations.
   */
  public function tearDown(): void {
    // Remove any temporary directories that were created.
    $filesystem = new Filesystem();
    foreach ($this->tmpDirs as $dir) {
      $filesystem->remove($dir);
    }
    // Clear out variables from the previous pass.
    $this->tmpDirs = [];
    $this->io = NULL;
    // Clear the composer cache dir, if it was set
    putenv('COMPOSER_CACHE_DIR=');
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
  public function cloneFixtureProjects(string $fixturesDir, array $replacements = []): void {
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
  public function runComposer(string $cmd, string $cwd): string {
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
