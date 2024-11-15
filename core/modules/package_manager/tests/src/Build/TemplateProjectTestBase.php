<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Build;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Drupal\BuildTests\QuickStart\QuickStartTestBase;
use Drupal\Composer\Composer;
use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager_test_event_logger\EventSubscriber\EventLogSubscriber;
use Drupal\sqlite\Driver\Database\sqlite\Install\Tasks;
use Drupal\Tests\package_manager\Traits\AssertPreconditionsTrait;
use Drupal\Tests\package_manager\Traits\FixtureUtilityTrait;
use Drupal\Tests\RandomGeneratorTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Base class for tests which create a test site from a core project template.
 *
 * The test site will be created from one of the core Composer project templates
 * (drupal/recommended-project or drupal/legacy-project) and contain complete
 * copies of Drupal core and all installed dependencies, completely independent
 * of the currently running code base.
 *
 * @internal
 */
abstract class TemplateProjectTestBase extends QuickStartTestBase {

  use AssertPreconditionsTrait;
  use FixtureUtilityTrait;
  use RandomGeneratorTrait;

  /**
   * The web root of the test site, relative to the workspace directory.
   *
   * @var string
   */
  private string $webRoot;

  /**
   * A secondary server instance, to serve XML metadata about available updates.
   *
   * @var \Symfony\Component\Process\Process
   */
  private Process $metadataServer;

  /**
   * All output that the PHP web server logs to the error buffer.
   *
   * @var string
   */
  private string $serverErrorLog = '';

  /**
   * The PHP web server's max_execution_time value.
   *
   * @var int
   */
  protected const MAX_EXECUTION_TIME = 20;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Build tests cannot be run if SQLite minimum version is not met.
    $minimum_version = Tasks::SQLITE_MINIMUM_VERSION;
    $actual_version = (new \PDO('sqlite::memory:'))
      ->query('select sqlite_version()')
      ->fetch()[0];
    if (version_compare($actual_version, $minimum_version, '<')) {
      $this->markTestSkipped("SQLite version $minimum_version or later is required, but $actual_version was detected.");
    }

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->metadataServer?->stop();
    parent::tearDown();
  }

  /**
   * Data provider for tests which use all the core project templates.
   *
   * @return string[][]
   *   The test cases.
   */
  public static function providerTemplate(): array {
    return [
      'RecommendedProject' => ['RecommendedProject'],
      'LegacyProject' => ['LegacyProject'],
    ];
  }

  /**
   * Sets the version of Drupal core to which the test site will be updated.
   *
   * @param string $version
   *   The Drupal core version to set.
   */
  protected function setUpstreamCoreVersion(string $version): void {
    $this->createVendorRepository([
      'drupal/core' => $version,
      'drupal/core-dev' => $version,
      'drupal/core-dev-pinned' => $version,
      'drupal/core-recommended' => $version,
      'drupal/core-composer-scaffold' => $version,
      'drupal/core-project-message' => $version,
      'drupal/core-vendor-hardening' => $version,
    ]);

    // Change the \Drupal::VERSION constant and put placeholder text in the
    // README so we can ensure that we really updated to the correct version. We
    // also change the default site configuration files so we can ensure that
    // these are updated as well, despite `sites/default` being write-protected.
    // @see ::assertUpdateSuccessful()
    // @see ::createTestProject()
    $core_dir = $this->getWorkspaceDrupalRoot() . '/core';
    Composer::setDrupalVersion($this->getWorkspaceDrupalRoot(), $version);
    file_put_contents("$core_dir/README.txt", "Placeholder for Drupal core $version.");

    foreach (['default.settings.php', 'default.services.yml'] as $file) {
      file_put_contents("$core_dir/assets/scaffold/files/$file", "# This is part of Drupal $version.\n", FILE_APPEND);
    }
  }

  /**
   * Returns the full path to the test site's document root.
   *
   * @return string
   *   The full path of the test site's document root.
   */
  protected function getWebRoot(): string {
    return $this->getWorkspaceDirectory() . '/' . $this->webRoot;
  }

  /**
   * {@inheritdoc}
   */
  protected function instantiateServer($port, $working_dir = NULL) {
    $working_dir = $working_dir ?: $this->webRoot;
    $finder = new PhpExecutableFinder();
    $working_path = $this->getWorkingPath($working_dir);
    $server = [
      $finder->find(),
      '-S',
      '127.0.0.1:' . $port,
      '-d max_execution_time=' . static::MAX_EXECUTION_TIME,
      '-d disable_functions=set_time_limit',
      '-t',
      $working_path,
    ];
    if (file_exists($working_path . DIRECTORY_SEPARATOR . '.ht.router.php')) {
      $server[] = $working_path . DIRECTORY_SEPARATOR . '.ht.router.php';
    }
    $ps = new Process($server, $working_path);
    $ps->setIdleTimeout(30)
      ->setTimeout(30)
      ->start(function ($output_type, $output): void {
        if ($output_type === Process::ERR) {
          $this->serverErrorLog .= $output;
        }
      });
    // Wait until the web server has started. It is started if the port is no
    // longer available.
    for ($i = 0; $i < 50; $i++) {
      usleep(100000);
      if (!$this->checkPortIsAvailable($port)) {
        return $ps;
      }
    }

    throw new \RuntimeException(sprintf("Unable to start the web server.\nCMD: %s \nCODE: %d\nSTATUS: %s\nOUTPUT:\n%s\n\nERROR OUTPUT:\n%s", $ps->getCommandLine(), $ps->getExitCode(), $ps->getStatus(), $ps->getOutput(), $ps->getErrorOutput()));
  }

  /**
   * {@inheritdoc}
   */
  public function installQuickStart($profile, $working_dir = NULL): void {
    parent::installQuickStart("$profile --no-ansi", $working_dir ?: $this->webRoot);

    // Allow package_manager to be installed, since it is hidden by default.
    // Always allow test modules to be installed in the UI and, for easier
    // debugging, always display errors in their dubious glory.
    $php = <<<END
\$settings['testing_package_manager'] = TRUE;
\$settings['extension_discovery_scan_tests'] = TRUE;
\$config['system.logging']['error_level'] = 'verbose';
END;
    $this->writeSettings($php);
  }

  /**
   * {@inheritdoc}
   */
  public function visit($request_uri = '/', $working_dir = NULL) {
    return parent::visit($request_uri, $working_dir ?: $this->webRoot);
  }

  /**
   * {@inheritdoc}
   */
  public function formLogin($username, $password, $working_dir = NULL): void {
    parent::formLogin($username, $password, $working_dir ?: $this->webRoot);
  }

  /**
   * Adds a path repository to the test site.
   *
   * @param string $name
   *   An arbitrary name for the repository.
   * @param string $path
   *   The path of the repository. Must exist in the file system.
   * @param string $working_directory
   *   (optional) The Composer working directory. Defaults to 'project'.
   */
  protected function addRepository(string $name, string $path, $working_directory = 'project'): void {
    $this->assertDirectoryExists($path);

    $repository = json_encode([
      'type' => 'path',
      'url' => $path,
      'options' => [
        'symlink' => FALSE,
      ],
    ], JSON_UNESCAPED_SLASHES);
    $this->runComposer("composer config repo.$name '$repository'", $working_directory);
  }

  /**
   * Prepares the test site to serve an XML feed of available release metadata.
   *
   * @param array $xml_map
   *   The update XML map, as used by update_test.settings.
   *
   * @see \Drupal\package_manager_test_release_history\TestController::metadata()
   */
  protected function setReleaseMetadata(array $xml_map): void {
    foreach ($xml_map as $metadata_file) {
      $this->assertFileIsReadable($metadata_file);
    }
    $xml_map = var_export($xml_map, TRUE);
    $this->writeSettings("\$config['update_test.settings']['xml_map'] = $xml_map;");
  }

  /**
   * Creates a test project from a given template and installs Drupal.
   *
   * @param string $template
   *   The template to use. Can be 'RecommendedProject' or 'LegacyProject'.
   */
  protected function createTestProject(string $template): void {
    // Create a copy of core (including its Composer plugins, templates, and
    // metapackages) which we can modify.
    $this->copyCodebase();

    $workspace_dir = $this->getWorkspaceDirectory();
    $project_dir = $workspace_dir . '/project';
    mkdir($project_dir);

    $data = file_get_contents("$workspace_dir/composer/Template/$template/composer.json");
    $data = json_decode($data, TRUE, flags: JSON_THROW_ON_ERROR);

    // Allow pre-release versions of dependencies.
    $data['minimum-stability'] = 'dev';

    // Remove all repositories and replace them with a single local one that
    // provides all dependencies.
    $data['repositories'] = [
      'vendor' => [
        'type' => 'composer',
        'url' => 'file://' . $workspace_dir . '/vendor.json',
      ],
      // Disable Packagist entirely so that we don't test the Internet.
      'packagist.org' => FALSE,
    ];

    // Allow any version of the Drupal core packages in the template project.
    self::unboundCoreConstraints($data['require']);
    self::unboundCoreConstraints($data['require-dev']);

    // Do not run development Composer plugin, since it tries to run an
    // executable that might not exist while dependencies are being installed.
    // It adds no value to this test.
    $data['config']['allow-plugins']['dealerdirect/phpcodesniffer-composer-installer'] = FALSE;

    // Always force Composer to mirror path repositories. This is necessary
    // because dependencies are installed from a Composer-type repository, which
    // will normally try to symlink packages which are installed from local
    // directories. This breaks Package Manager, because it does not support
    // symlinks pointing outside the main code base.
    $script = '@putenv COMPOSER_MIRROR_PATH_REPOS=1';
    $data['scripts']['pre-install-cmd'] = $script;
    $data['scripts']['pre-update-cmd'] = $script;

    file_put_contents($project_dir . '/composer.json', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    // Because we set the COMPOSER_MIRROR_PATH_REPOS=1 environment variable when
    // creating the project, none of the dependencies should be symlinked.
    $this->assertStringNotContainsString('Symlinking', $this->runComposer('composer install', 'project'));

    // If using the drupal/recommended-project template, we don't expect there
    // to be an .htaccess file at the project root. One would normally be
    // generated by Composer when Package Manager or other code creates a
    // ComposerInspector object in the active directory, except that Package
    // Manager takes specific steps to prevent that. So, here we're just
    // confirming that, in fact, Composer's .htaccess protection was disabled.
    // We don't do this for the drupal/legacy-project template because its
    // project root, which is also the document root, SHOULD contain a .htaccess
    // generated by Drupal core.
    // We do this check because this test uses PHP's built-in web server, which
    // ignores .htaccess files and everything in them, so a Composer-generated
    // .htaccess file won't cause this test to fail.
    if ($template === 'RecommendedProject') {
      $this->assertFileDoesNotExist("$workspace_dir/project/.htaccess");
    }

    // Now that we know the project was created successfully, we can set the
    // web root with confidence.
    $this->webRoot = 'project/' . $data['extra']['drupal-scaffold']['locations']['web-root'];

    // Install Drupal.
    $this->installQuickStart('standard');
    $this->formLogin($this->adminUsername, $this->adminPassword);

    // When checking for updates, we need to be able to make sub-requests, but
    // the built-in PHP server is single-threaded. Therefore, open a second
    // server instance on another port, which will serve the metadata about
    // available updates.
    $port = $this->findAvailablePort();
    $this->metadataServer = $this->instantiateServer($port);
    $code = <<<END
\$config['update.settings']['fetch']['url'] = 'http://localhost:$port/test-release-history';
END;

    // Ensure Package Manager logs Composer Stager's process output to a file
    // named for the current test.
    $log = $this->getDrupalRoot() . '/sites/simpletest/browser_output';
    @mkdir($log, recursive: TRUE);
    $this->assertDirectoryIsWritable($log);
    $log .= '/' . str_replace('\\', '_', static::class) . '-' . $this->name();
    if ($this->usesDataProvider()) {
      $log .= '-' . preg_replace('/[^a-z0-9]+/i', '_', $this->dataName());
    }
    $code .= <<<END
\$config['package_manager.settings']['log'] = '$log-package_manager.log';
END;

    $this->writeSettings($code);

    // Install helpful modules.
    $this->installModules([
      'package_manager_test_api',
      'package_manager_test_event_logger',
      'package_manager_test_release_history',
    ]);

    // Confirm the server time out settings.
    // @see \Drupal\Tests\package_manager\Build\TemplateProjectTestBase::instantiateServer()
    $this->visit('/package-manager-test-api/check-setup');
    $this->getMink()
      ->assertSession()
      ->pageTextContains("max_execution_time=" . static::MAX_EXECUTION_TIME . ":set_time_limit-exists=no");
  }

  /**
   * Changes constraints for core packages to `*`.
   *
   * @param string[] $constraints
   *   A set of version constraints, like you'd find in the `require` or
   *   `require-dev` sections of `composer.json`. This array is modified by
   *   reference.
   */
  private static function unboundCoreConstraints(array &$constraints): void {
    $names = preg_grep('/^drupal\/core-?/', array_keys($constraints));
    foreach ($names as $name) {
      $constraints[$name] = '*';
    }
  }

  /**
   * Creates a Composer repository for all dependencies of the test project.
   *
   * We always reference third-party dependencies (i.e., any package that isn't
   * part of Drupal core) from the main project which is running this test.
   *
   * Packages that are part of Drupal core -- such as `drupal/core`,
   * `drupal/core-composer-scaffold`, and so on -- are expected to have been
   * copied into the workspace directory, so that we can modify them as needed.
   *
   * The file will be written to WORKSPACE_DIR/vendor.json.
   *
   * @param string[] $versions
   *   (optional) The versions of specific packages, keyed by package name.
   *   Versions of packages not in this array will be determined first by
   *   looking for a `version` key in the package's composer.json, then by
   *   calling \Composer\InstalledVersions::getPrettyVersion(). If none of that
   *   works, `dev-main` will be used as the package's version.
   */
  protected function createVendorRepository(array $versions = []): void {
    $packages = [];

    $class_loaders = ClassLoader::getRegisteredLoaders();

    $workspace_dir = $this->getWorkspaceDirectory();
    $finder = Finder::create()
      ->in([
        $this->getWorkspaceDrupalRoot() . '/core',
        "$workspace_dir/composer/Metapackage",
        "$workspace_dir/composer/Plugin",
        key($class_loaders),
      ])
      ->depth('< 3')
      ->files()
      ->name('composer.json');

    /** @var \Symfony\Component\Finder\SplFileInfo $file */
    foreach ($finder as $file) {
      $package_info = json_decode($file->getContents(), TRUE, flags: JSON_THROW_ON_ERROR);
      $name = $package_info['name'];

      $requirements = $package_info['require'] ?? [];
      // These polyfills are dependencies of some packages, but for reasons we
      // don't understand, they are not installed in code bases built on PHP
      // versions that are newer than the ones being polyfilled, which means we
      // won't be able to build our test project because these polyfills aren't
      // available in the local code base. Since we're guaranteed to be on PHP
      // 8.3 or later, no package should need to polyfill older versions.
      unset(
        $requirements['symfony/polyfill-php72'],
        $requirements['symfony/polyfill-php73'],
        $requirements['symfony/polyfill-php74'],
        $requirements['symfony/polyfill-php80'],
        $requirements['symfony/polyfill-php81'],
        $requirements['symfony/polyfill-php82'],
        $requirements['symfony/polyfill-php83'],
      );
      // If this package requires any Drupal core packages, ensure it allows
      // any version.
      self::unboundCoreConstraints($requirements);
      // In certain situations, like Drupal CI, auto_updates might be
      // required into the code base by Composer. This may cause it to be added to
      // the drupal/core-recommended metapackage, which can prevent the test site
      // from being built correctly, among other deleterious effects. To prevent
      // such shenanigans, always remove drupal/auto_updates from
      // drupal/core-recommended.
      if ($name === 'drupal/core-recommended') {
        unset($requirements['drupal/auto_updates']);
      }

      try {
        $version = $versions[$name] ?? $package_info['version'] ?? InstalledVersions::getPrettyVersion($name);
      }
      catch (\OutOfBoundsException) {
        $version = 'dev-main';
      }

      // Create a pared-down package definition that has just enough information
      // for Composer to install the package from the local copy: the name,
      // version, package type, source path ("dist" in Composer terminology),
      // and the autoload information, so that the classes provided by the
      // package will actually be loadable in the test site we're building.
      $path = $file->getPath();
      $packages[$name][$version] = [
        'name' => $name,
        'version' => $version,
        'type' => $package_info['type'] ?? 'library',
        // Disabling symlinks in the transport options doesn't seem to have an
        // effect, so we use the COMPOSER_MIRROR_PATH_REPOS environment
        // variable to force mirroring in ::createTestProject().
        'dist' => [
          'type' => 'path',
          'url' => $path,
        ],
        'require' => $requirements,
        'autoload' => $package_info['autoload'] ?? [],
        'provide' => $package_info['provide'] ?? [],
        // Composer plugins are loaded and activated as early as possible, and
        // they must have a `class` key defined in their `extra` section, along
        // with a dependency on `composer-plugin-api` (plus any other real
        // runtime dependencies). This is also necessary for packages that ship
        // scaffold files, like Drupal core.
        'extra' => $package_info['extra'] ?? [],
      ];
    }
    $data = json_encode(['packages' => $packages], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    file_put_contents($workspace_dir . '/vendor.json', $data);
  }

  /**
   * Runs a Composer command and returns its output.
   *
   * Always asserts that the command was executed successfully.
   *
   * @param string $command
   *   The command to execute, including the `composer` invocation.
   * @param string|null $working_dir
   *   (optional) A working directory relative to the workspace, within which to
   *   execute the command. Defaults to the workspace directory.
   * @param bool $json
   *   (optional) Whether to parse the command's output as JSON before returning
   *   it. Defaults to FALSE.
   *
   * @return mixed|string|null
   *   The command's output, optionally parsed as JSON.
   */
  protected function runComposer(string $command, ?string $working_dir = NULL, bool $json = FALSE) {
    $process = $this->executeCommand($command, $working_dir);
    $this->assertCommandSuccessful();

    $output = trim($process->getOutput());
    if ($json) {
      $output = json_decode($output, TRUE, flags: JSON_THROW_ON_ERROR);
    }
    return $output;
  }

  /**
   * Appends PHP code to the test site's settings.php.
   *
   * @param string $php
   *   The PHP code to append to the test site's settings.php.
   */
  protected function writeSettings(string $php): void {
    // Ensure settings are writable, since this is the only way we can set
    // configuration values that aren't accessible in the UI.
    $file = $this->getWebRoot() . '/sites/default/settings.php';
    $this->assertFileExists($file);
    chmod(dirname($file), 0744);
    chmod($file, 0744);
    $this->assertFileIsWritable($file);
    $this->assertIsInt(file_put_contents($file, $php, FILE_APPEND));
  }

  /**
   * Installs modules in the UI.
   *
   * Assumes that a user with the appropriate permissions is logged in.
   *
   * @param string[] $modules
   *   The machine names of the modules to install.
   */
  protected function installModules(array $modules): void {
    $mink = $this->getMink();
    $page = $mink->getSession()->getPage();
    $assert_session = $mink->assertSession();

    $this->visit('/admin/modules');
    foreach ($modules as $module) {
      $page->checkField("modules[$module][enable]");
    }
    $page->pressButton('Install');

    // If there is a confirmation form warning about additional dependencies
    // or non-stable modules, submit it.
    $form_id = $assert_session->elementExists('css', 'input[type="hidden"][name="form_id"]')
      ->getValue();
    if (preg_match('/^system_modules_(experimental_|non_stable_)?confirm_form$/', $form_id)) {
      $page->pressButton('Continue');
      $assert_session->statusCodeEquals(200);
    }
  }

  /**
   * Copies a fixture directory to a temporary directory and returns its path.
   *
   * @param string $fixture_directory
   *   The fixture directory.
   *
   * @return string
   *   The temporary directory.
   */
  protected function copyFixtureToTempDirectory(string $fixture_directory): string {
    $temp_directory = $this->getWorkspaceDirectory() . '/fixtures_temp_' . $this->randomMachineName(20);
    static::copyFixtureFilesTo($fixture_directory, $temp_directory);
    return $temp_directory;
  }

  /**
   * Asserts stage events were fired in a specific order.
   *
   * @param string $expected_stage_class
   *   The expected stage class for the events.
   * @param array|null $expected_events
   *   (optional) The expected stage events that should have been fired in the
   *   order in which they should have been fired. Events can be specified more
   *   that once if they will be fired multiple times. If there are no events
   *   specified all life cycle events from PreCreateEvent to PostApplyEvent
   *   will be asserted.
   * @param int $wait
   *   (optional) How many seconds to wait for the events to be fired. Defaults
   *   to 0.
   * @param string $message
   *   (optional) A message to display with the assertion.
   *
   * @see \Drupal\package_manager_test_event_logger\EventSubscriber\EventLogSubscriber::logEventInfo
   */
  protected function assertExpectedStageEventsFired(string $expected_stage_class, ?array $expected_events = NULL, int $wait = 0, string $message = ''): void {
    if ($expected_events === NULL) {
      $expected_events = EventLogSubscriber::getSubscribedEvents();
      // The event subscriber uses this event to ensure the log file is excluded
      // from Package Manager operations, but it's not relevant for our purposes
      // because it's not part of the stage life cycle.
      unset($expected_events[CollectPathsToExcludeEvent::class]);
      $expected_events = array_keys($expected_events);
    }
    $this->assertNotEmpty($expected_events);

    $log_file = $this->getWorkspaceDirectory() . '/project/' . EventLogSubscriber::LOG_FILE_NAME;
    $max_wait = time() + $wait;
    do {
      $this->assertFileIsReadable($log_file);
      $log_data = file_get_contents($log_file);
      $log_data = json_decode($log_data, TRUE, flags: JSON_THROW_ON_ERROR);

      // Filter out events logged by any other stage.
      $log_data = array_filter($log_data, fn (array $event): bool => $event['stage'] === $expected_stage_class);

      // If we've logged at least the expected number of events, stop waiting.
      // Break out of the loop and assert the expected events were logged.
      if (count($log_data) >= count($expected_events)) {
        break;
      }
      // Wait a bit before checking again.
      sleep(5);
    } while ($max_wait > time());

    $this->assertSame($expected_events, array_column($log_data, 'event'), $message);
  }

  /**
   * Visits the 'admin/reports/dblog' and selects Package Manager's change log.
   */
  private function visitPackageManagerChangeLog(): void {
    $mink = $this->getMink();
    $assert_session = $mink->assertSession();
    $page = $mink->getSession()->getPage();

    $this->visit('/admin/reports/dblog');
    $assert_session->statusCodeEquals(200);
    $page->selectFieldOption('Type', 'package_manager_change_log');
    $page->pressButton('Filter');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Asserts changes requested during the stage life cycle were logged.
   *
   * This method specifically asserts changes that were *requested* (i.e.,
   * during the require phase) rather than changes that were actually applied.
   * The requested and applied changes may be exactly the same, or they may
   * differ (for example, if a secondary dependency was added or updated in the
   * stage directory).
   *
   * @param string[] $expected_requested_changes
   *   The expected requested changes.
   *
   * @see ::assertAppliedChangesWereLogged()
   * @see \Drupal\package_manager\EventSubscriber\ChangeLogger
   */
  protected function assertRequestedChangesWereLogged(array $expected_requested_changes): void {
    $this->visitPackageManagerChangeLog();
    $assert_session = $this->getMink()->assertSession();

    $assert_session->elementExists('css', 'a[href*="/admin/reports/dblog/event/"]:contains("Requested changes:")')
      ->click();
    array_walk($expected_requested_changes, $assert_session->pageTextContains(...));
  }

  /**
   * Asserts that changes applied during the stage life cycle were logged.
   *
   * This method specifically asserts changes that were *applied*, rather than
   * the changes that were merely requested. For example, if a package was
   * required into the stage and it added a secondary dependency, that change
   * will be considered one of the applied changes, not a requested change.
   *
   * @param string[] $expected_applied_changes
   *   The expected applied changes.
   *
   * @see ::assertRequestedChangesWereLogged()
   * @see \Drupal\package_manager\EventSubscriber\ChangeLogger
   */
  protected function assertAppliedChangesWereLogged(array $expected_applied_changes): void {
    $this->visitPackageManagerChangeLog();
    $assert_session = $this->getMink()->assertSession();

    $assert_session->elementExists('css', 'a[href*="/admin/reports/dblog/event/"]:contains("Applied changes:")')
      ->click();
    array_walk($expected_applied_changes, $assert_session->pageTextContains(...));
  }

  /**
   * Gets a /package-manager-test-api response.
   *
   * @param string $url
   *   The package manager test API URL to fetch.
   * @param array $query_data
   *   The query data.
   */
  protected function makePackageManagerTestApiRequest(string $url, array $query_data): void {
    $url .= '?' . http_build_query($query_data);
    $this->visit($url);
    $session = $this->getMink()->getSession();

    // Ensure test failures provide helpful debug output when there's a fatal
    // PHP error: don't use \Behat\Mink\WebAssert::statusCodeEquals().
    $message = sprintf(
      "Error response: %s\n\nHeaders: %s\n\nServer error log: %s",
      $session->getPage()->getContent(),
      var_export($session->getResponseHeaders(), TRUE),
      $this->serverErrorLog,
    );
    $this->assertSame(200, $session->getStatusCode(), $message);
  }

  /**
   * {@inheritdoc}
   */
  public function copyCodebase(?\Iterator $iterator = NULL, $working_dir = NULL): void {
    parent::copyCodebase($iterator, $working_dir);

    // Create a local Composer repository for all third-party dependencies and
    // core packages.
    $this->createVendorRepository();
  }

}
