<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\UpgradePathTestBase.
 */

namespace Drupal\system\Tests\Upgrade;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Session\UserSession;
use Drupal\simpletest\WebTestBase;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Perform end-to-end tests of the upgrade path.
 */
abstract class UpgradePathTestBase extends WebTestBase {

  /**
   * @var array
   */
  protected $configDirectories;

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
   * Flag to indicate whether there are pending updates or not.
   */
  var $pendingUpdates = TRUE;

  /**
   * Prepares the appropriate session for the release of Drupal being upgraded.
   */
  protected function prepareD8Session() {
    // We need an IP when storing sessions
    // so add a dummy request in the container.
    $request = Request::create('http://example.com/');
    $request->server->set('REMOTE_ADDR', '3.3.3.3');
    $this->container->set('request', $request);

    // Generate and set a D7-compatible session cookie.
    $this->curlInitialize();
    $sid = Crypt::hashBase64(uniqid(mt_rand(), TRUE) . Crypt::randomBytes(55));
    $this->curlCookies[] = rawurlencode(session_name()) . '=' . rawurlencode($sid);

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

    // Load Session API.
    require_once DRUPAL_ROOT . '/core/includes/session.inc';
    drupal_session_initialize();

    // Reset flags.
    $this->upgradedSite = FALSE;
    $this->upgradeErrors = array();

    // Create the database prefix for this test.
    $this->prepareDatabasePrefix();

    // Prepare the environment for running tests.
    $this->prepareEnvironment();
    if (!$this->setupEnvironment) {
      return FALSE;
    }

    // Reset all statics and variables to perform tests in a clean environment.
    $conf = array();
    drupal_static_reset();


    // Build a minimal, partially mocked environment for unit tests.
    $this->containerBuild(drupal_container());
    // Make sure it survives kernel rebuilds.
    $conf['container_service_providers']['TestServiceProvider'] = 'Drupal\simpletest\TestServiceProvider';

    // Change the database prefix.
    // All static variables need to be reset before the database prefix is
    // changed, since Drupal\Core\Utility\CacheArray implementations attempt to
    // write back to persistent caches when they are destructed.
    $this->changeDatabasePrefix();
    if (!$this->setupDatabasePrefix) {
      return FALSE;
    }

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
    // Load values for uid 1.
    $values = db_query('SELECT * FROM {users} WHERE uid = :uid', array(':uid' => 1))->fetchAssoc();
    // Load rolest.
    $values['roles'] = array_merge(array(DRUPAL_AUTHENTICATED_RID), db_query('SELECT rid FROM {users_roles} WHERE uid = :uid', array(':uid' => 1))->fetchCol());
    // Create a new user session object.
    $user = new UserSession($values);

    // Generate and set a D8-compatible session cookie.
    $this->prepareD8Session();

    // Restore necessary variables.
    $this->variable_set('site_mail', 'simpletest@example.com');

    drupal_set_time_limit($this->timeLimit);
    $this->setup = TRUE;
  }

  /**
   * Overrides \Drupal\simpletest\TestBase::prepareConfigDirectories().
   */
  protected function prepareConfigDirectories() {
    // The configuration directories are prepared as part of the first access to
    // update.php.
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
    catch (\Exception $e) {}
  }

  /**
   * Specialized refreshVariables().
   */
  protected function refreshVariables() {
    // Refresh the variables only if the site was already upgraded.
    if ($this->upgradedSite) {
      global $conf;
      cache('bootstrap')->delete('variables');
      $conf = variable_initialize();
      $container = drupal_container();
      if ($container->has('config.factory')) {
        $container->get('config.factory')->reset();
      }
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
    $this->getUpdatePhp();
    if (!$this->assertResponse(200)) {
      throw new \Exception('Initial GET to update.php did not return HTTP 200 status.');
    }

    // Ensure that the first update screen appeared correctly.
    if (!$this->assertFieldByXPath('//input[@type="submit"]')) {
      throw new \Exception('An error was encountered during the first access to update.php.');
    }

    // Initialize config directories and rebuild the service container after
    // creating them in the first step.
    parent::prepareConfigDirectories();
    $this->rebuildContainer();

    // Continue.
    $this->drupalPost(NULL, array(), t('Continue'));
    if (!$this->assertResponse(200)) {
      throw new \Exception('POST to continue update.php did not return HTTP 200 status.');
    }

    // The test should pass if there are no pending updates.
    $content = $this->drupalGetContent();
    if (strpos($content, t('No pending updates.')) !== FALSE) {
      $this->pass('No pending updates and therefore no upgrade process to test.');
      $this->pendingUpdates = FALSE;
      return TRUE;
    }

    // Go!
    $this->drupalPost(NULL, array(), t('Apply pending updates'));
    if (!$this->assertResponse(200)) {
      throw new \Exception('POST to update.php to apply pending updates did not return HTTP 200 status.');
    }

    if (!$this->assertNoText(t('An unrecoverable error has occurred.'))) {
      // Error occured during update process.
      throw new \Exception('POST to update.php to apply pending updates detected an unrecoverable error.');
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
      throw new \Exception('Errors during update process.');
    }

    // Allow tests to check the completion page.
    $this->checkCompletionPage();

    // Check if there still are pending updates.
    $this->getUpdatePhp();
    $this->drupalPost(NULL, array(), t('Continue'));
    if (!$this->assertText(t('No pending updates.'), 'No pending updates at the end of the update process.')) {
      throw new \Exception('update.php still shows pending updates after execution.');
    }

    // Upgrade succeed, rebuild the environment so that we can call the API
    // of the child site directly from this request.
    $this->upgradedSite = TRUE;

    // Force a variable refresh as we only just enabled it.
    $this->refreshVariables();

    // Reload module list for modules that are enabled in the test database
    // but not on the test client.
    \Drupal::moduleHandler()->resetImplementations();
    \Drupal::moduleHandler()->reload();

    // Rebuild the container and all caches.
    $this->rebuildContainer();
    $this->resetAll();

    return TRUE;
  }

  /**
   * Overrides some core services for the upgrade tests.
   */
  public function containerBuild(ContainerBuilder $container) {
    // Keep the container object around for tests.
    $this->container = $container;

    $container
      ->register('config.storage', 'Drupal\Core\Config\FileStorage')
      ->addArgument($this->configDirectories[CONFIG_ACTIVE_DIRECTORY]);

    if ($this->container->hasDefinition('path_processor_alias')) {
      // Prevent the alias-based path processor, which requires a url_alias db
      // table, from being registered to the path processor manager. We do this
      // by removing the tags that the compiler pass looks for. This means the
      // url generator can safely be used within upgrade path tests.
      $definition = $this->container->getDefinition('path_processor_alias');
      $definition->clearTag('path_processor_inbound')->clearTag('path_processor_outbound');
    }
  }

  /**
   * Gets update.php without calling url().
   *
   * Required since WebTestBase::drupalGet() calls t(), which calls into
   * system_list(), from the parent site/test runner, before update.php is even
   * executed.
   *
   * @see WebTestBase::drupalGet()
   */
  protected function getUpdatePhp() {
    $this->rebuildContainer();
    $path = $GLOBALS['base_url'] . '/core/update.php';
    $out = $this->curlExec(array(CURLOPT_HTTPGET => TRUE, CURLOPT_URL => $path, CURLOPT_NOBODY => FALSE));
    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    // Replace original page output with new output from redirected page(s).
    if ($new = $this->checkForMetaRefresh()) {
      $out = $new;
    }
    $this->verbose('GET request to: ' . $path .
      '<hr />Ending URL: ' . $this->getUrl() .
      '<hr />' . $out);
    return $out;
  }

  /**
   * Checks the update.php completion page.
   *
   * Invoked by UpgradePathTestBase::performUpgrade() to allow upgrade tests to
   * check messages and other output on the final confirmation page.
   */
  protected function checkCompletionPage() { }
}
