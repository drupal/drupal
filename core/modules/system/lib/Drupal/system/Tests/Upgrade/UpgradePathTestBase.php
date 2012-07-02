<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\UpgradePathTestBase.
 */

namespace Drupal\system\Tests\Upgrade;

use Drupal\Core\Database\Database;
use Drupal\simpletest\WebTestBase;

/**
 * Perform end-to-end tests of the upgrade path.
 */
abstract class UpgradePathTestBase extends WebTestBase {

  /**
   * The file path(s) to the dumped database(s) to load into the child site.
   *
   * @var array
   */
  var $databaseDumpFiles = array();

  /**
   * Flag that indicates whether the child site has been upgraded.
   */
  var $upgradedSite = FALSE;

  /**
   * Array of errors triggered during the upgrade process.
   */
  var $upgradeErrors = array();

  /**
   * Array of modules loaded when the test starts.
   */
  var $loadedModules = array();

  /**
   * Flag to indicate whether there are pending updates or not.
   */
  var $pendingUpdates = TRUE;

  /**
   * Prepares the appropriate session for the release of Drupal being upgraded.
   */
  protected function prepareD8Session() {
    // Generate and set a D7-compatible session cookie.
    $this->curlInitialize();
    $sid = drupal_hash_base64(uniqid(mt_rand(), TRUE) . drupal_random_bytes(55));
    curl_setopt($this->curlHandle, CURLOPT_COOKIE, rawurlencode(session_name()) . '=' . rawurlencode($sid));

    // Force our way into the session of the child site.
    drupal_save_session(TRUE);
    _drupal_session_write($sid, '');
    drupal_save_session(FALSE);
  }

  /**
   * Checks that zlib is enabled in order to run the upgrade tests.
   */
  protected function checkRequirements() {
    if (!function_exists('gzopen')) {
      return array(
        'Missing zlib requirement for upgrade tests.',
      );
    }
    return parent::checkRequirements();
  }

  /**
   * Overrides Drupal\simpletest\WebTestBase::setUp() for upgrade testing.
   *
   * @see Drupal\simpletest\WebTestBase::prepareDatabasePrefix()
   * @see Drupal\simpletest\WebTestBase::changeDatabasePrefix()
   * @see Drupal\simpletest\WebTestBase::prepareEnvironment()
   */
  protected function setUp() {
    global $user, $conf;

    // Load the Update API.
    require_once DRUPAL_ROOT . '/core/includes/update.inc';

    // Reset flags.
    $this->upgradedSite = FALSE;
    $this->upgradeErrors = array();

    $this->loadedModules = module_list();

    // Create the database prefix for this test.
    $this->prepareDatabasePrefix();

    // Prepare the environment for running tests.
    $this->prepareEnvironment();

    // Reset all statics and variables to perform tests in a clean environment.
    $conf = array();
    drupal_static_reset();

    // Change the database prefix.
    // All static variables need to be reset before the database prefix is
    // changed, since Drupal\Core\Utility\CacheArray implementations attempt to
    // write back to persistent caches when they are destructed.
    $this->changeDatabasePrefix();

    // Unregister the registry.
    // This is required to make sure that the database layer works properly.
    spl_autoload_unregister('drupal_autoload_class');
    spl_autoload_unregister('drupal_autoload_interface');

    // Load the database from the portable PHP dump.
    // The files may be gzipped.
    foreach ($this->databaseDumpFiles as $file) {
      if (substr($file, -3) == '.gz') {
        $file = "compress.zlib://$file";
      }
      require $file;
    }

    // Set path variables.
    $this->variable_set('file_public_path', $this->public_files_directory);
    $this->variable_set('file_private_path', $this->private_files_directory);
    $this->variable_set('file_temporary_path', $this->temp_files_directory);

    $this->pass('Finished loading the dump.');

    // Ensure that the session is not written to the new environment and replace
    // the global $user session with uid 1 from the new test site.
    drupal_save_session(FALSE);
    // Login as uid 1.
    $user = db_query('SELECT * FROM {users} WHERE uid = :uid', array(':uid' => 1))->fetchObject();

    // Generate and set a D8-compatible session cookie.
    $this->prepareD8Session();

    // Restore necessary variables.
    $this->variable_set('site_mail', 'simpletest@example.com');

    drupal_set_time_limit($this->timeLimit);
    $this->setup = TRUE;
  }

  /**
   * Specialized variable_set() that works even if the child site is not upgraded.
   *
   * @param $name
   *   The name of the variable to set.
   * @param $value
   *   The value to set. This can be any PHP data type; these functions take care
   *   of serialization as necessary.
   *
   * @todo Update for D8 configuration system.
   */
  protected function variable_set($name, $value) {
    db_delete('variable')
      ->condition('name', $name)
      ->execute();
    db_insert('variable')
      ->fields(array(
        'name' => $name,
        'value' => serialize($value),
      ))
      ->execute();

    try {
      cache()->delete('variables');
      cache('bootstrap')->delete('variables');
    }
    // Since cache_bootstrap won't exist in a Drupal 6 site, ignore the
    // exception if the above fails.
    catch (Exception $e) {}
  }

  /**
   * Specialized refreshVariables().
   */
  protected function refreshVariables() {
    // No operation if the child has not been upgraded yet.
    if (!$this->upgradedSite) {
      return parent::refreshVariables();
    }
  }

  /**
   * Perform the upgrade.
   *
   * @param $register_errors
   *   Register the errors during the upgrade process as failures.
   * @return
   *   TRUE if the upgrade succeeded, FALSE otherwise.
   */
  protected function performUpgrade($register_errors = TRUE) {

    // Load the first update screen.
    $update_url = $GLOBALS['base_url'] . '/core/update.php';
    $this->drupalGet($update_url, array('external' => TRUE));
    if (!$this->assertResponse(200)) {
      return FALSE;
    }

    // Continue.
    $this->drupalPost(NULL, array(), t('Continue'));
    if (!$this->assertResponse(200)) {
      return FALSE;
    }

    // The test should pass if there are no pending updates.
    $content = $this->drupalGetContent();
    if (strpos($content, t('No pending updates.')) !== FALSE) {
      $this->pass(t('No pending updates and therefore no upgrade process to test.'));
      $this->pendingUpdates = FALSE;
      return TRUE;
    }

    // Go!
    $this->drupalPost(NULL, array(), t('Apply pending updates'));
    if (!$this->assertResponse(200)) {
      return FALSE;
    }

    // Check for errors during the update process.
    foreach ($this->xpath('//li[@class=:class]', array(':class' => 'failure')) as $element) {
      $message = strip_tags($element->asXML());
      $this->upgradeErrors[] = $message;
      if ($register_errors) {
        $this->fail($message);
      }
    }

    if (!empty($this->upgradeErrors)) {
      // Upgrade failed, the installation might be in an inconsistent state,
      // don't process.
      return FALSE;
    }

    // Check if there still are pending updates.
    $this->drupalGet($update_url, array('external' => TRUE));
    $this->drupalPost(NULL, array(), t('Continue'));
    if (!$this->assertText(t('No pending updates.'), t('No pending updates at the end of the update process.'))) {
      return FALSE;
    }

    // Upgrade succeed, rebuild the environment so that we can call the API
    // of the child site directly from this request.
    $this->upgradedSite = TRUE;

    // Reload module list. For modules that are enabled in the test database,
    // but not on the test client, we need to load the code here.
    $new_modules = array_diff(module_list(TRUE), $this->loadedModules);
    foreach ($new_modules as $module) {
      drupal_load('module', $module);
    }

    // Re-register autoload functions.
    spl_autoload_register('drupal_autoload_class');
    spl_autoload_register('drupal_autoload_interface');

    // Reload hook implementations
    module_implements_reset();

    // Rebuild caches.
    drupal_static_reset();
    drupal_flush_all_caches();

    // Reload global $conf array and permissions.
    $this->refreshVariables();
    $this->checkPermissions(array(), TRUE);

    return TRUE;
  }

  /**
   * Force uninstall all modules from a test database, except those listed.
   *
   * @param $modules
   *   The list of modules to keep installed. Required core modules will
   *   always be kept.
   */
  protected function uninstallModulesExcept(array $modules) {
    $required_modules = array('block', 'dblog', 'filter', 'node', 'system', 'update', 'user');

    $modules = array_merge($required_modules, $modules);

    db_delete('system')
      ->condition('type', 'module')
      ->condition('name', $modules, 'NOT IN')
      ->execute();
  }

}
