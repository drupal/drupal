<?php

namespace Drupal\FunctionalTests\Update;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Test\TestRunnerKernel;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Drupal\Tests\RequirementsPageTrait;
use Drupal\user\Entity\User;
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

  use SchemaCheckTestTrait;
  use RequirementsPageTrait;

  /**
   * Modules to enable after the database is loaded.
   */
  protected static $modules = [];

  /**
   * The file path(s) to the dumped database(s) to load into the child site.
   *
   * The file system/tests/fixtures/update/drupal-8.bare.standard.php.gz is
   * normally included first -- this sets up the base database from a bare
   * standard Drupal installation.
   *
   * The file system/tests/fixtures/update/drupal-8.filled.standard.php.gz
   * can also be used in case we want to test with a database filled with
   * content, and with all core modules enabled.
   *
   * @var array
   */
  protected $databaseDumpFiles = [];

  /**
   * The install profile used in the database dump file.
   *
   * @var string
   */
  protected $installProfile = 'standard';

  /**
   * Flag that indicates whether the child site has been updated.
   *
   * @var bool
   */
  protected $upgradedSite = FALSE;

  /**
   * Array of errors triggered during the update process.
   *
   * @var array
   */
  protected $upgradeErrors = [];

  /**
   * Array of modules loaded when the test starts.
   *
   * @var array
   */
  protected $loadedModules = [];

  /**
   * Flag to indicate whether zlib is installed or not.
   *
   * @var bool
   */
  protected $zlibInstalled = TRUE;

  /**
   * Flag to indicate whether there are pending updates or not.
   *
   * @var bool
   */
  protected $pendingUpdates = TRUE;

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
   * Fail the test if there are failed updates.
   *
   * @var bool
   */
  protected $checkFailedUpdates = TRUE;

  /**
   * Constructs an UpdatePathTestCase object.
   *
   * @param $test_id
   *   (optional) The ID of the test. Tests with the same id are reported
   *   together.
   */
  public function __construct($test_id = NULL) {
    parent::__construct($test_id);
    $this->zlibInstalled = function_exists('gzopen');
  }

  /**
   * Overrides WebTestBase::setUp() for update testing.
   *
   * The main difference in this method is that rather than performing the
   * installation via the installer, a database is loaded. Additional work is
   * then needed to set various things such as the config directories and the
   * container that would normally be done via the installer.
   */
  protected function setUp() {
    $request = Request::createFromGlobals();

    // Boot up Drupal into a state where calling the database API is possible.
    // This is used to initialize the database system, so we can load the dump
    // files.
    $autoloader = require $this->root . '/autoload.php';
    $kernel = TestRunnerKernel::createFromRequest($request, $autoloader);
    $kernel->loadLegacyIncludes();

    // Set the update url. This must be set here rather than in
    // self::__construct() or the old URL generator will leak additional test
    // sites. Additionally, we need to prevent the path alias processor from
    // running because we might not have a working alias system before running
    // the updates.
    $this->updateUrl = Url::fromRoute('system.db_update', [], ['path_processing' => FALSE]);

    $this->setupBaseUrl();

    // Install Drupal test site.
    $this->prepareEnvironment();
    $this->runDbTasks();
    // Allow classes to set database dump files.
    $this->setDatabaseDumpFiles();

    // We are going to set a missing zlib requirement property for usage
    // during the performUpgrade() and tearDown() methods. Also set that the
    // tests failed.
    if (!$this->zlibInstalled) {
      parent::setUp();
      return;
    }
    $this->installDrupal();

    // Add the config directories to settings.php.
    drupal_install_config_directories();

    // Set the container. parent::rebuildAll() would normally do this, but this
    // not safe to do here, because the database has not been updated yet.
    $this->container = \Drupal::getContainer();

    $this->replaceUser1();

    require_once $this->root . '/core/includes/update.inc';

    // Setup Mink.
    $this->initMink();

    // Set up the browser test output file.
    $this->initBrowserOutputFile();
  }

  /**
   * {@inheritdoc}
   */
  public function installDrupal() {
    $this->initUserSession();
    $this->prepareSettings();
    $this->doInstall();
    $this->initSettings();

    $request = Request::createFromGlobals();
    $container = $this->initKernel($request);
    $this->initConfig($container);
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
      if (substr($file, -3) == '.gz') {
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

    // Remember the profile which was used.
    $settings['settings']['install_profile'] = (object) [
      'value' => $this->installProfile,
      'required' => TRUE,
    ];
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
    $settings['entity_update_batch_size'] = (object) [
      'value' => 1,
      'required' => TRUE,
    ];

    $this->writeSettings($settings);
  }

  /**
   * Helper function to run pending database updates.
   */
  protected function runUpdates() {
    if (!$this->zlibInstalled) {
      $this->fail('Missing zlib requirement for update tests.');
      return FALSE;
    }
    // The site might be broken at the time so logging in using the UI might
    // not work, so we use the API itself.
    drupal_rewrite_settings([
      'settings' => [
        'update_free_access' => (object) [
          'value' => TRUE,
          'required' => TRUE,
        ],
      ],
    ]);

    $this->drupalGet($this->updateUrl);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));

    $this->doSelectionTest();
    // Run the update hooks.
    $this->clickLink(t('Apply pending updates'));
    $this->checkForMetaRefresh();

    // Ensure there are no failed updates.
    if ($this->checkFailedUpdates) {
      $failure = $this->cssSelect('.failure');
      if ($failure) {
        $this->fail('The update failed with the following message: "' . reset($failure)->getText() . '"');
      }

      // Ensure that there are no pending updates.
      foreach (['update', 'post_update'] as $update_type) {
        switch ($update_type) {
          case 'update':
            $all_updates = update_get_update_list();
            break;
          case 'post_update':
            $all_updates = \Drupal::service('update.post_update_registry')->getPendingUpdateInformation();
            break;
        }
        foreach ($all_updates as $module => $updates) {
          if (!empty($updates['pending'])) {
            foreach (array_keys($updates['pending']) as $update_name) {
              $this->fail("The $update_name() update function from the $module module did not run.");
            }
          }
        }
      }

      // Ensure that the container is updated if any modules are installed or
      // uninstalled during the update.
      /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
      $module_handler = $this->container->get('module_handler');
      $config_module_list = $this->config('core.extension')->get('module');
      $module_handler_list = $module_handler->getModuleList();
      $modules_installed = FALSE;
      // Modules that are in configuration but not the module handler have been
      // installed.
      foreach (array_keys(array_diff_key($config_module_list, $module_handler_list)) as $module) {
        $module_handler->addModule($module, drupal_get_path('module', $module));
        $modules_installed = TRUE;
      }
      $modules_uninstalled = FALSE;
      $module_handler_list = $module_handler->getModuleList();
      // Modules that are in the module handler but not configuration have been
      // uninstalled.
      foreach (array_keys(array_diff_key($module_handler_list, $config_module_list)) as $module) {
        $modules_uninstalled = TRUE;
        unset($module_handler_list[$module]);
      }
      if ($modules_installed || $modules_uninstalled) {
        // Note that resetAll() does not reset the kernel module list so we
        // have to do that manually.
        $this->kernel->updateModules($module_handler_list, $module_handler_list);
      }

      // If we have successfully clicked 'Apply pending updates' then we need to
      // clear the caches in the update test runner as this has occurred as part
      // of the updates.
      $this->resetAll();

      // The config schema can be incorrect while the update functions are being
      // executed. But once the update has been completed, it needs to be valid
      // again. Assert the schema of all configuration objects now.
      $names = $this->container->get('config.storage')->listAll();

      // Allow tests to opt out of checking specific configuration.
      $exclude = $this->getConfigSchemaExclusions();
      /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config */
      $typed_config = $this->container->get('config.typed');
      foreach ($names as $name) {
        if (in_array($name, $exclude, TRUE)) {
          // Skip checking schema if the config is listed in the
          // $configSchemaCheckerExclusions property.
          continue;
        }
        $config = $this->config($name);
        $this->assertConfigSchema($typed_config, $name, $config->get());
      }

      // Ensure that the update hooks updated all entity schema.
      $needs_updates = \Drupal::entityDefinitionUpdateManager()->needsUpdates();
      if ($needs_updates) {
        foreach (\Drupal::entityDefinitionUpdateManager()->getChangeSummary() as $entity_type_id => $summary) {
          $entity_type_label = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getLabel();
          foreach ($summary as $message) {
            $this->fail("$entity_type_label: $message");
          }
        }
        // The above calls to `fail()` should prevent this from ever being
        // called, but it is here in case something goes really wrong.
        $this->assertFalse($needs_updates, 'After all updates ran, entity schema is up to date.');
      }
    }
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

    require_once __DIR__ . '/../../../../includes/install.inc';
    $connection = Database::getConnection();
    $errors = db_installer_object($connection->driver())->runTasks();
    if (!empty($errors)) {
      $this->fail('Failed to run installer database tasks: ' . implode(', ', $errors));
    }
  }

  /**
   * Replace User 1 with the user created here.
   */
  protected function replaceUser1() {
    /** @var \Drupal\user\UserInterface $account */
    // @todo: Saving the account before the update is problematic.
    //   https://www.drupal.org/node/2560237
    $account = User::load(1);
    $account->setPassword($this->rootUser->pass_raw);
    $account->setEmail($this->rootUser->getEmail());
    $account->setUsername($this->rootUser->getAccountName());
    $account->save();
  }

  /**
   * Tests the selection page.
   */
  protected function doSelectionTest() {
    // No-op. Tests wishing to do test the selection page or the general
    // update.php environment before running update.php can override this method
    // and implement their required tests.
  }

}
