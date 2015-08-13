<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\UpdatePathTestBase.
 */

namespace Drupal\system\Tests\Update;

use Drupal\Component\Utility\Crypt;
use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;
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
 * - In your setUp() method, point the $this->databaseDumpFiles variable to the
 *   database dump files, and then call parent::setUp() to run the base setUp()
 *   method in this class.
 * - In your test method, call $this->runUpdates() to run the necessary updates,
 *   and then use test assertions to verify that the result is what you expect.
 *
 * @ingroup update_api
 *
 * @see hook_update_N()
 */
abstract class UpdatePathTestBase extends WebTestBase {

  use SchemaCheckTestTrait;

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
   * Constructs an UpdatePathTestCase object.
   *
   * @param $test_id
   *   (optional) The ID of the test. Tests with the same id are reported
   *   together.
   */
  function __construct($test_id = NULL) {
    parent::__construct($test_id);
    $this->zlibInstalled = function_exists('gzopen');

    // Set the update url.
    $this->updateUrl = Url::fromRoute('system.db_update');
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
    // We are going to set a missing zlib requirement property for usage
    // during the performUpgrade() and tearDown() methods. Also set that the
    // tests failed.
    if (!$this->zlibInstalled) {
      parent::setUp();
      return;
    }

    // These methods are called from parent::setUp().
    $this->setBatch();
    $this->initUserSession();
    $this->prepareSettings();

    // Load the database(s).
    foreach ($this->databaseDumpFiles as $file) {
      if (substr($file, -3) == '.gz') {
        $file = "compress.zlib://$file";
      }
      require $file;
    }

    $this->initSettings();
    $request = Request::createFromGlobals();
    $container = $this->initKernel($request);
    $this->initConfig($container);

    // Add the config directories to settings.php.
    drupal_install_config_directories();

    // Install any additional modules.
    $this->installModulesFromClassProperty($container);

    // Restore the original Simpletest batch.
    $this->restoreBatch();

    // Rebuild and reset.
    $this->rebuildAll();

    // Replace User 1 with the user created here.
    /** @var \Drupal\user\UserInterface $account */
    $account = User::load(1);
    $account->setPassword($this->rootUser->pass_raw);
    $account->setEmail($this->rootUser->getEmail());
    $account->setUsername($this->rootUser->getUsername());
    $account->save();
  }

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
    drupal_rewrite_settings(['settings' => ['update_free_access' => (object) [
      'value' => TRUE,
      'required' => TRUE,
    ]]]);

    $this->drupalGet($this->updateUrl);
    $this->clickLink(t('Continue'));

    // Run the update hooks.
    $this->clickLink(t('Apply pending updates'));

    // The config schema can be incorrect while the update functions are being
    // executed. But once the update has been completed, it needs to be valid
    // again. Assert the schema of all configuration objects now.
    $names = $this->container->get('config.storage')->listAll();
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config */
    $typed_config = $this->container->get('config.typed');
    foreach ($names as $name) {
      $config = $this->config($name);
      $this->assertConfigSchema($typed_config, $name, $config->get());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function rebuildAll() {
    parent::rebuildAll();

    // Remove the notices we get due to the menu link rebuild prior to running
    // the system updates for the schema change.
    foreach ($this->assertions as $key => $assertion) {
      if ($assertion['message_group'] == 'Notice' && basename($assertion['file']) == 'MenuTreeStorage.php' && strpos($assertion['message'], 'unserialize(): Error at offset 0') !== FALSE) {
        unset($this->assertions[$key]);
        $this->deleteAssert($assertion['message_id']);
        $this->results['#exception']--;
      }
    }
  }

}
