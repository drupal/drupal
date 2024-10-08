<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Update;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Core\Site\Settings;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Drupal\Tests\UpdatePathTestTrait;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a base class for writing an update test.
 *
 * To write an update test:
 * - Write the hook_update_N() implementations that you are testing.
 * - Create one or more database dump files, which will set the database to the
 *   "before updates" state. Normally, these will add some configuration data to
 *   the database, set up some tables/fields, etc.
 * - Create a class that extends this class.
 * - Ensure that the test is in the legacy group. Tests can have multiple
 *   groups.
 * - In your setUp() method, point the $this->databaseDumpFiles variable to the
 *   database dump files, and then call parent::setUp() to run the base setUp()
 *   method in this class.
 * - In your test method, call $this->runUpdates() to run the necessary updates,
 *   and then use test assertions to verify that the result is what you expect.
 * - In order to test both with a "bare" database dump as well as with a
 *   database dump filled with content, extend your update path test class with
 *   a new test class that overrides the bare database dump. Refer to
 *   UpdatePathTestBaseFilledTest for an example.
 *
 * @ingroup update_api
 *
 * @see hook_update_N()
 */
abstract class UpdatePathTestBase extends BrowserTestBase {
  use UpdatePathTestTrait {
    runUpdates as doRunUpdates;
  }

  /**
   * Modules to install after the database is loaded.
   *
   * @var string[]
   */
  protected static $modules = [];

  /**
   * The file path(s) to the dumped database(s) to load into the child site.
   *
   * The file system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz is
   * normally included first -- this sets up the base database from a bare
   * standard Drupal installation.
   *
   * The file system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz
   * can also be used in case we want to test with a database filled with
   * content, and with all core modules enabled.
   *
   * @var array
   */
  protected $databaseDumpFiles = [];

  /**
   * The update URL.
   *
   * @var string
   */
  protected $updateUrl;

  /**
   * Disable strict config schema checking.
   *
   * The schema is verified at the end of running the update.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    if (!extension_loaded('zlib')) {
      $this->markTestSkipped('The zlib extension is not available.');
    }

    parent::setUp();
  }

  /**
   * Overrides BrowserTestBase::installDrupal() for update testing.
   *
   * The main difference in this method is that rather than performing the
   * installation via the installer, a database is loaded. Additional work is
   * then needed to set various things such as the config directories and the
   * container that would normally be done via the installer.
   */
  public function installDrupal() {
    // Set the update URL. This must be set here rather than in
    // self::__construct() or the old URL generator will leak additional test
    // sites. Additionally, we need to prevent the path alias processor from
    // running because we might not have a working alias system before running
    // the updates.
    $this->updateUrl = Url::fromRoute('system.db_update', [], ['path_processing' => FALSE]);

    $this->initUserSession();
    $this->prepareSettings();
    $this->doInstall();
    $this->initSettings();

    $request = Request::createFromGlobals();
    $container = $this->initKernel($request);
    $this->initConfig($container);

    // Add the config directories to settings.php.
    $sync_directory = Settings::get('config_sync_directory');
    \Drupal::service('file_system')->prepareDirectory($sync_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    // Ensure the default temp directory exist and is writable. The configured
    // temp directory may be removed during update.
    \Drupal::service('file_system')->prepareDirectory($this->tempFilesDirectory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    // Set the container. parent::rebuildAll() would normally do this, but this
    // not safe to do here, because the database has not been updated yet.
    $this->container = \Drupal::getContainer();

    $this->replaceUser1();

    require_once $this->root . '/core/includes/update.inc';
  }

  /**
   * {@inheritdoc}
   */
  protected function doInstall() {
    $this->runDbTasks();
    // Allow classes to set database dump files.
    $this->setDatabaseDumpFiles();

    // Load the database(s).
    foreach ($this->databaseDumpFiles as $file) {
      if (str_ends_with($file, '.gz')) {
        $file = "compress.zlib://$file";
      }
      require $file;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function initFrontPage() {
    // Do nothing as Drupal is not installed yet.
  }

  /**
   * Set database dump files to be used.
   */
  abstract protected function setDatabaseDumpFiles();

  /**
   * Add settings that are missed since the installer isn't run.
   */
  protected function prepareSettings() {
    parent::prepareSettings();

    // Generate a hash salt.
    $settings['settings']['hash_salt'] = (object) [
      'value'    => Crypt::randomBytesBase64(55),
      'required' => TRUE,
    ];

    // Since the installer isn't run, add the database settings here too.
    $settings['databases']['default'] = (object) [
      'value' => Database::getConnectionInfo(),
      'required' => TRUE,
    ];

    // Force every update hook to only run one entity per batch.
    $settings['settings']['entity_update_batch_size'] = (object) [
      'value' => 1,
      'required' => TRUE,
    ];

    // Set up sync directory.
    $settings['settings']['config_sync_directory'] = (object) [
      'value' => $this->publicFilesDirectory . '/config_sync',
      'required' => TRUE,
    ];

    $this->writeSettings($settings);
  }

  /**
   * Helper function to run pending database updates.
   */
  protected function runUpdates() {
    $this->doRunUpdates($this->updateUrl);
  }

  /**
   * Runs the install database tasks for the driver used by the test runner.
   */
  protected function runDbTasks() {
    // Create a minimal container so that t() works.
    // @see install_begin_request()
    $container = new ContainerBuilder();
    $container->setParameter('language.default_values', Language::$defaultValues);
    $container
      ->register('language.default', 'Drupal\Core\Language\LanguageDefault')
      ->addArgument('%language.default_values%');
    $container
      ->register('string_translation', 'Drupal\Core\StringTranslation\TranslationManager')
      ->addArgument(new Reference('language.default'));
    \Drupal::setContainer($container);

    // Run database tasks and check for errors.
    $installer_class = Database::getConnectionInfo()['default']['namespace'] . "\\Install\\Tasks";
    $errors = (new $installer_class())->runTasks();
    if (!empty($errors)) {
      $this->fail('Failed to run installer database tasks: ' . implode(', ', $errors));
    }
  }

  /**
   * Replace User 1 with the user created here.
   */
  protected function replaceUser1() {
    // We try not to save content entities in hook_update_N() because the schema
    // might be out of sync, or hook invocations might rely on other schemas
    // that also aren't updated yet. Hence we are directly updating the database
    // tables with the values.
    Database::getConnection()->update('users_field_data')
      ->fields([
        'name' => $this->rootUser->getAccountName(),
        'pass' => \Drupal::service('password')->hash($this->rootUser->pass_raw),
        'mail' => $this->rootUser->getEmail(),
      ])
      ->condition('uid', 1)
      ->execute();
  }

  /**
   * Tests that the database was properly loaded.
   */
  protected function testDatabaseLoaded() {
    // Set a value in the cache to prove caches are cleared.
    \Drupal::service('cache.default')->set(__CLASS__, 'Test');

    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    foreach (['user' => 9301, 'node' => 8700, 'system' => 8901, 'update_test_schema' => 8000] as $module => $schema) {
      $this->assertEquals($schema, $update_registry->getInstalledVersion($module), "Module $module schema is $schema");
    }

    // Ensure that all {router} entries can be unserialized. If they cannot be
    // unserialized a notice will be thrown by PHP.

    $result = \Drupal::database()->select('router', 'r')
      ->fields('r', ['name', 'route'])
      ->execute()
      ->fetchAllKeyed(0, 1);
    // For the purpose of fetching the notices and displaying more helpful error
    // messages, let's override the error handler temporarily.
    set_error_handler(function ($severity, $message, $filename, $lineno) {
      throw new \ErrorException($message, 0, $severity, $filename, $lineno);
    });
    foreach ($result as $route_name => $route) {
      try {
        unserialize($route);
      }
      catch (\Exception $e) {
        $this->fail(sprintf('Error "%s" while unserializing route %s', $e->getMessage(), Html::escape($route_name)));
      }
    }
    restore_error_handler();

    // Before accessing the site we need to run updates first or the site might
    // be broken.
    $this->runUpdates();
    $this->assertEquals('standard', \Drupal::config('core.extension')->get('profile'));
    $this->assertEquals('Site-Install', \Drupal::config('system.site')->get('name'));
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('Site-Install');

    // Ensure that the database tasks have been run during set up. Neither MySQL
    // nor SQLite make changes that are testable.
    $database = $this->container->get('database');
    if ($database->driver() == 'pgsql') {
      $this->assertEquals('on', $database->query("SHOW standard_conforming_strings")->fetchField());
      $this->assertEquals('escape', $database->query("SHOW bytea_output")->fetchField());
    }
    // Ensure the test runners cache has been cleared.
    $this->assertFalse(\Drupal::service('cache.default')->get(__CLASS__));
  }

}
