<?php

namespace Drupal\simpletest;

use Drupal\block\Entity\Block;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Test\FunctionalTestSetupTrait;
use Drupal\Core\Url;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\EntityViewTrait;
use Drupal\Tests\block\Traits\BlockCreationTrait as BaseBlockCreationTrait;
use Drupal\Tests\Listeners\DeprecationListenerTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait as BaseContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait as BaseNodeCreationTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait as BaseUserCreationTrait;
use Drupal\Tests\XdebugRequestTrait;
use Zend\Diactoros\Uri;

/**
 * Test case for typical Drupal tests.
 *
 * @ingroup testing
 */
abstract class WebTestBase extends TestBase {

  use FunctionalTestSetupTrait;
  use AssertContentTrait;
  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }
  use AssertPageCacheContextsAndTagsTrait;
  use BaseBlockCreationTrait {
    placeBlock as drupalPlaceBlock;
  }
  use BaseContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }
  use CronRunTrait;
  use AssertMailTrait {
    getMails as drupalGetMails;
  }
  use BaseNodeCreationTrait {
    getNodeByTitle as drupalGetNodeByTitle;
    createNode as drupalCreateNode;
  }
  use BaseUserCreationTrait {
    createUser as drupalCreateUser;
    createRole as drupalCreateRole;
    createAdminRole as drupalCreateAdminRole;
  }

  use XdebugRequestTrait;
  use EntityViewTrait {
    buildEntityView as drupalBuildEntityView;
  }

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * The URL currently loaded in the internal browser.
   *
   * @var string
   */
  protected $url;

  /**
   * The handle of the current cURL connection.
   *
   * @var resource
   */
  protected $curlHandle;

  /**
   * Whether or not to assert the presence of the X-Drupal-Ajax-Token.
   *
   * @var bool
   */
  protected $assertAjaxHeader = TRUE;

  /**
   * The headers of the page currently loaded in the internal browser.
   *
   * @var array
   */
  protected $headers;

  /**
   * The cookies of the page currently loaded in the internal browser.
   *
   * @var array
   */
  protected $cookies = [];

  /**
   * Indicates that headers should be dumped if verbose output is enabled.
   *
   * Headers are dumped to verbose by drupalGet(), drupalHead(), and
   * drupalPostForm().
   *
   * @var bool
   */
  protected $dumpHeaders = FALSE;

  /**
   * The current user logged in using the internal browser.
   *
   * @var \Drupal\Core\Session\AccountInterface|bool
   */
  protected $loggedInUser = FALSE;

  /**
   * The current cookie file used by cURL.
   *
   * We do not reuse the cookies in further runs, so we do not need a file
   * but we still need cookie handling, so we set the jar to NULL.
   */
  protected $cookieFile = NULL;

  /**
   * Additional cURL options.
   *
   * \Drupal\simpletest\WebTestBase itself never sets this but always obeys what
   * is set.
   */
  protected $additionalCurlOptions = [];

  /**
   * The original batch, before it was changed for testing purposes.
   *
   * @var array
   */
  protected $originalBatch;

  /**
   * The original user, before it was changed to a clean uid = 1 for testing.
   *
   * @var object
   */
  protected $originalUser = NULL;

  /**
   * The original shutdown handlers array, before it was cleaned for testing.
   *
   * @var array
   */
  protected $originalShutdownCallbacks = [];

  /**
   * The current session ID, if available.
   */
  protected $sessionId = NULL;

  /**
   * The maximum number of redirects to follow when handling responses.
   *
   * @var int
   */
  protected $maximumRedirects = 5;

  /**
   * The number of redirects followed during the handling of a request.
   */
  protected $redirectCount;


  /**
   * The number of meta refresh redirects to follow, or NULL if unlimited.
   *
   * @var null|int
   */
  protected $maximumMetaRefreshCount = NULL;

  /**
   * The number of meta refresh redirects followed during ::drupalGet().
   *
   * @var int
   */
  protected $metaRefreshCount = 0;

  /**
   * Cookies to set on curl requests.
   *
   * @var array
   */
  protected $curlCookies = [];

  /**
   * An array of custom translations suitable for drupal_rewrite_settings().
   *
   * @var array
   */
  protected $customTranslations;

  /**
   * Constructor for \Drupal\simpletest\WebTestBase.
   */
  public function __construct($test_id = NULL) {
    parent::__construct($test_id);
    $this->skipClasses[__CLASS__] = TRUE;
    $this->classLoader = require DRUPAL_ROOT . '/autoload.php';
  }

  /**
   * Checks to see whether a block appears on the page.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block entity to find on the page.
   */
  protected function assertBlockAppears(Block $block) {
    $result = $this->findBlockInstance($block);
    $this->assertTrue(!empty($result), format_string('Ensure the block @id appears on the page', ['@id' => $block->id()]));
  }

  /**
   * Checks to see whether a block does not appears on the page.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block entity to find on the page.
   */
  protected function assertNoBlockAppears(Block $block) {
    $result = $this->findBlockInstance($block);
    $this->assertFalse(!empty($result), format_string('Ensure the block @id does not appear on the page', ['@id' => $block->id()]));
  }

  /**
   * Find a block instance on the page.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block entity to find on the page.
   *
   * @return array
   *   The result from the xpath query.
   */
  protected function findBlockInstance(Block $block) {
    return $this->xpath('//div[@id = :id]', [':id' => 'block-' . $block->id()]);
  }

  /**
   * Log in a user with the internal browser.
   *
   * If a user is already logged in, then the current user is logged out before
   * logging in the specified user.
   *
   * Please note that neither the current user nor the passed-in user object is
   * populated with data of the logged in user. If you need full access to the
   * user object after logging in, it must be updated manually. If you also need
   * access to the plain-text password of the user (set by drupalCreateUser()),
   * e.g. to log in the same user again, then it must be re-assigned manually.
   * For example:
   * @code
   *   // Create a user.
   *   $account = $this->drupalCreateUser(array());
   *   $this->drupalLogin($account);
   *   // Load real user object.
   *   $pass_raw = $account->pass_raw;
   *   $account = User::load($account->id());
   *   $account->pass_raw = $pass_raw;
   * @endcode
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User object representing the user to log in.
   *
   * @see drupalCreateUser()
   */
  protected function drupalLogin(AccountInterface $account) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    $edit = [
      'name' => $account->getUsername(),
      'pass' => $account->pass_raw,
    ];
    $this->drupalPostForm('user/login', $edit, t('Log in'));

    // @see WebTestBase::drupalUserIsLoggedIn()
    if (isset($this->sessionId)) {
      $account->session_id = $this->sessionId;
    }
    $pass = $this->assert($this->drupalUserIsLoggedIn($account), format_string('User %name successfully logged in.', ['%name' => $account->getUsername()]), 'User login');
    if ($pass) {
      $this->loggedInUser = $account;
      $this->container->get('current_user')->setAccount($account);
    }
  }

  /**
   * Returns whether a given user account is logged in.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account object to check.
   */
  protected function drupalUserIsLoggedIn($account) {
    $logged_in = FALSE;

    if (isset($account->session_id)) {
      $session_handler = $this->container->get('session_handler.storage');
      $logged_in = (bool) $session_handler->read($account->session_id);
    }

    return $logged_in;
  }

  /**
   * Logs a user out of the internal browser and confirms.
   *
   * Confirms logout by checking the login page.
   */
  protected function drupalLogout() {
    // Make a request to the logout page, and redirect to the user page, the
    // idea being if you were properly logged out you should be seeing a login
    // screen.
    $this->drupalGet('user/logout', ['query' => ['destination' => 'user/login']]);
    $this->assertResponse(200, 'User was logged out.');
    $pass = $this->assertField('name', 'Username field found.', 'Logout');
    $pass = $pass && $this->assertField('pass', 'Password field found.', 'Logout');

    if ($pass) {
      // @see WebTestBase::drupalUserIsLoggedIn()
      unset($this->loggedInUser->session_id);
      $this->loggedInUser = FALSE;
      $this->container->get('current_user')->setAccount(new AnonymousUserSession());
    }
  }

  /**
   * Sets up a Drupal site for running functional and integration tests.
   *
   * Installs Drupal with the installation profile specified in
   * \Drupal\simpletest\WebTestBase::$profile into the prefixed database.
   *
   * Afterwards, installs any additional modules specified in the static
   * \Drupal\simpletest\WebTestBase::$modules property of each class in the
   * class hierarchy.
   *
   * After installation all caches are flushed and several configuration values
   * are reset to the values of the parent site executing the test, since the
   * default values may be incompatible with the environment in which tests are
   * being executed.
   */
  protected function setUp() {
    // Set an explicit time zone to not rely on the system one, which may vary
    // from setup to setup. The Australia/Sydney time zone is chosen so all
    // tests are run using an edge case scenario (UTC+10 and DST). This choice
    // is made to prevent time zone related regressions and reduce the
    // fragility of the testing system in general. This is also set in config in
    // \Drupal\simpletest\WebTestBase::initConfig().
    date_default_timezone_set('Australia/Sydney');

    // Preserve original batch for later restoration.
    $this->setBatch();

    // Initialize user 1 and session name.
    $this->initUserSession();

    // Prepare the child site settings.
    $this->prepareSettings();

    // Execute the non-interactive installer.
    $this->doInstall();

    // Import new settings.php written by the installer.
    $this->initSettings();

    // Initialize the request and container post-install.
    $container = $this->initKernel(\Drupal::request());

    // Initialize and override certain configurations.
    $this->initConfig($container);

    // Collect modules to install.
    $this->installModulesFromClassProperty($container);

    // Restore the original batch.
    $this->restoreBatch();

    // Reset/rebuild everything.
    $this->rebuildAll();
  }

  /**
   * Preserve the original batch, and instantiate the test batch.
   */
  protected function setBatch() {
    // When running tests through the Simpletest UI (vs. on the command line),
    // Simpletest's batch conflicts with the installer's batch. Batch API does
    // not support the concept of nested batches (in which the nested is not
    // progressive), so we need to temporarily pretend there was no batch.
    // Backup the currently running Simpletest batch.
    $this->originalBatch = batch_get();

    // Reset the static batch to remove Simpletest's batch operations.
    $batch = &batch_get();
    $batch = [];
  }

  /**
   * Restore the original batch.
   *
   * @see ::setBatch
   */
  protected function restoreBatch() {
    // Restore the original Simpletest batch.
    $batch = &batch_get();
    $batch = $this->originalBatch;
  }

  /**
   * Queues custom translations to be written to settings.php.
   *
   * Use WebTestBase::writeCustomTranslations() to apply and write the queued
   * translations.
   *
   * @param string $langcode
   *   The langcode to add translations for.
   * @param array $values
   *   Array of values containing the untranslated string and its translation.
   *   For example:
   *   @code
   *   array(
   *     '' => array('Sunday' => 'domingo'),
   *     'Long month name' => array('March' => 'marzo'),
   *   );
   *   @endcode
   *   Pass an empty array to remove all existing custom translations for the
   *   given $langcode.
   */
  protected function addCustomTranslations($langcode, array $values) {
    // If $values is empty, then the test expects all custom translations to be
    // cleared.
    if (empty($values)) {
      $this->customTranslations[$langcode] = [];
    }
    // Otherwise, $values are expected to be merged into previously passed
    // values, while retaining keys that are not explicitly set.
    else {
      foreach ($values as $context => $translations) {
        foreach ($translations as $original => $translation) {
          $this->customTranslations[$langcode][$context][$original] = $translation;
        }
      }
    }
  }

  /**
   * Writes custom translations to the test site's settings.php.
   *
   * Use TestBase::addCustomTranslations() to queue custom translations before
   * calling this method.
   */
  protected function writeCustomTranslations() {
    $settings = [];
    foreach ($this->customTranslations as $langcode => $values) {
      $settings_key = 'locale_custom_strings_' . $langcode;

      // Update in-memory settings directly.
      $this->settingsSet($settings_key, $values);

      $settings['settings'][$settings_key] = (object) [
        'value' => $values,
        'required' => TRUE,
      ];
    }
    // Only rewrite settings if there are any translation changes to write.
    if (!empty($settings)) {
      $this->writeSettings($settings);
    }
  }

  /**
   * Cleans up after testing.
   *
   * Deletes created files and temporary files directory, deletes the tables
   * created by setUp(), and resets the database prefix.
   */
  protected function tearDown() {
    // Destroy the testing kernel.
    if (isset($this->kernel)) {
      $this->kernel->shutdown();
    }
    parent::tearDown();

    // Ensure that the maximum meta refresh count is reset.
    $this->maximumMetaRefreshCount = NULL;

    // Ensure that internal logged in variable and cURL options are reset.
    $this->loggedInUser = FALSE;
    $this->additionalCurlOptions = [];

    // Close the CURL handler and reset the cookies array used for upgrade
    // testing so test classes containing multiple tests are not polluted.
    $this->curlClose();
    $this->curlCookies = [];
    $this->cookies = [];
  }

  /**
   * Initializes the cURL connection.
   *
   * If the simpletest_httpauth_credentials variable is set, this function will
   * add HTTP authentication headers. This is necessary for testing sites that
   * are protected by login credentials from public access.
   * See the description of $curl_options for other options.
   */
  protected function curlInitialize() {
    global $base_url;

    if (!isset($this->curlHandle)) {
      $this->curlHandle = curl_init();

      // Some versions/configurations of cURL break on a NULL cookie jar, so
      // supply a real file.
      if (empty($this->cookieFile)) {
        $this->cookieFile = $this->publicFilesDirectory . '/cookie.jar';
      }

      $curl_options = [
        CURLOPT_COOKIEJAR => $this->cookieFile,
        CURLOPT_URL => $base_url,
        CURLOPT_FOLLOWLOCATION => FALSE,
        CURLOPT_RETURNTRANSFER => TRUE,
        // Required to make the tests run on HTTPS.
        CURLOPT_SSL_VERIFYPEER => FALSE,
        // Required to make the tests run on HTTPS.
        CURLOPT_SSL_VERIFYHOST => FALSE,
        CURLOPT_HEADERFUNCTION => [&$this, 'curlHeaderCallback'],
        CURLOPT_USERAGENT => $this->databasePrefix,
        // Disable support for the @ prefix for uploading files.
        CURLOPT_SAFE_UPLOAD => TRUE,
      ];
      if (isset($this->httpAuthCredentials)) {
        $curl_options[CURLOPT_HTTPAUTH] = $this->httpAuthMethod;
        $curl_options[CURLOPT_USERPWD] = $this->httpAuthCredentials;
      }
      // curl_setopt_array() returns FALSE if any of the specified options
      // cannot be set, and stops processing any further options.
      $result = curl_setopt_array($this->curlHandle, $this->additionalCurlOptions + $curl_options);
      if (!$result) {
        throw new \UnexpectedValueException('One or more cURL options could not be set.');
      }
    }
    // We set the user agent header on each request so as to use the current
    // time and a new uniqid.
    curl_setopt($this->curlHandle, CURLOPT_USERAGENT, drupal_generate_test_ua($this->databasePrefix));
  }

  /**
   * Initializes and executes a cURL request.
   *
   * @param $curl_options
   *   An associative array of cURL options to set, where the keys are constants
   *   defined by the cURL library. For a list of valid options, see
   *   http://php.net/manual/function.curl-setopt.php
   * @param $redirect
   *   FALSE if this is an initial request, TRUE if this request is the result
   *   of a redirect.
   *
   * @return
   *   The content returned from the call to curl_exec().
   *
   * @see curlInitialize()
   */
  protected function curlExec($curl_options, $redirect = FALSE) {
    $this->curlInitialize();

    if (!empty($curl_options[CURLOPT_URL])) {
      // cURL incorrectly handles URLs with a fragment by including the
      // fragment in the request to the server, causing some web servers
      // to reject the request citing "400 - Bad Request". To prevent
      // this, we strip the fragment from the request.
      // TODO: Remove this for Drupal 8, since fixed in curl 7.20.0.
      if (strpos($curl_options[CURLOPT_URL], '#')) {
        $original_url = $curl_options[CURLOPT_URL];
        $curl_options[CURLOPT_URL] = strtok($curl_options[CURLOPT_URL], '#');
      }
    }

    $url = empty($curl_options[CURLOPT_URL]) ? curl_getinfo($this->curlHandle, CURLINFO_EFFECTIVE_URL) : $curl_options[CURLOPT_URL];

    if (!empty($curl_options[CURLOPT_POST])) {
      // This is a fix for the Curl library to prevent Expect: 100-continue
      // headers in POST requests, that may cause unexpected HTTP response
      // codes from some webservers (like lighttpd that returns a 417 error
      // code). It is done by setting an empty "Expect" header field that is
      // not overwritten by Curl.
      $curl_options[CURLOPT_HTTPHEADER][] = 'Expect:';
    }

    $cookies = [];
    if (!empty($this->curlCookies)) {
      $cookies = $this->curlCookies;
    }

    foreach ($this->extractCookiesFromRequest(\Drupal::request()) as $cookie_name => $values) {
      foreach ($values as $value) {
        $cookies[] = $cookie_name . '=' . $value;
      }
    }

    // Merge additional cookies in.
    if (!empty($cookies)) {
      $curl_options += [
        CURLOPT_COOKIE => '',
      ];
      // Ensure any existing cookie data string ends with the correct separator.
      if (!empty($curl_options[CURLOPT_COOKIE])) {
        $curl_options[CURLOPT_COOKIE] = rtrim($curl_options[CURLOPT_COOKIE], '; ') . '; ';
      }
      $curl_options[CURLOPT_COOKIE] .= implode('; ', $cookies) . ';';
    }

    curl_setopt_array($this->curlHandle, $this->additionalCurlOptions + $curl_options);

    if (!$redirect) {
      // Reset headers, the session ID and the redirect counter.
      $this->sessionId = NULL;
      $this->headers = [];
      $this->redirectCount = 0;
    }

    $content = curl_exec($this->curlHandle);
    $status = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

    // cURL incorrectly handles URLs with fragments, so instead of
    // letting cURL handle redirects we take of them ourselves to
    // to prevent fragments being sent to the web server as part
    // of the request.
    // TODO: Remove this for Drupal 8, since fixed in curl 7.20.0.
    if (in_array($status, [300, 301, 302, 303, 305, 307]) && $this->redirectCount < $this->maximumRedirects) {
      if ($this->drupalGetHeader('location')) {
        $this->redirectCount++;
        $curl_options = [];
        $curl_options[CURLOPT_URL] = $this->drupalGetHeader('location');
        $curl_options[CURLOPT_HTTPGET] = TRUE;
        return $this->curlExec($curl_options, TRUE);
      }
    }

    $this->setRawContent($content);
    $this->url = isset($original_url) ? $original_url : curl_getinfo($this->curlHandle, CURLINFO_EFFECTIVE_URL);

    $message_vars = [
      '@method' => !empty($curl_options[CURLOPT_NOBODY]) ? 'HEAD' : (empty($curl_options[CURLOPT_POSTFIELDS]) ? 'GET' : 'POST'),
      '@url' => isset($original_url) ? $original_url : $url,
      '@status' => $status,
      '@length' => format_size(strlen($this->getRawContent())),
    ];
    $message = new FormattableMarkup('@method @url returned @status (@length).', $message_vars);
    $this->assertTrue($this->getRawContent() !== FALSE, $message, 'Browser');
    return $this->getRawContent();
  }

  /**
   * Reads headers and registers errors received from the tested site.
   *
   * @param $curlHandler
   *   The cURL handler.
   * @param $header
   *   An header.
   *
   * @see _drupal_log_error()
   */
  protected function curlHeaderCallback($curlHandler, $header) {
    // Header fields can be extended over multiple lines by preceding each
    // extra line with at least one SP or HT. They should be joined on receive.
    // Details are in RFC2616 section 4.
    if ($header[0] == ' ' || $header[0] == "\t") {
      // Normalize whitespace between chucks.
      $this->headers[] = array_pop($this->headers) . ' ' . trim($header);
    }
    else {
      $this->headers[] = $header;
    }

    // Errors are being sent via X-Drupal-Assertion-* headers,
    // generated by _drupal_log_error() in the exact form required
    // by \Drupal\simpletest\WebTestBase::error().
    if (preg_match('/^X-Drupal-Assertion-[0-9]+: (.*)$/', $header, $matches)) {
      $parameters = unserialize(urldecode($matches[1]));
      // Handle deprecation notices triggered by system under test.
      if ($parameters[1] === 'User deprecated function') {
        if (getenv('SYMFONY_DEPRECATIONS_HELPER') !== 'disabled') {
          $message = (string) $parameters[0];
          $test_info = TestDiscovery::getTestInfo(get_called_class());
          if ($test_info['group'] !== 'legacy' && !in_array($message, DeprecationListenerTrait::getSkippedDeprecations())) {
            call_user_func_array([&$this, 'error'], $parameters);
          }
        }
      }
      else {
        // Call \Drupal\simpletest\WebTestBase::error() with the parameters from
        // the header.
        call_user_func_array([&$this, 'error'], $parameters);
      }
    }

    // Save cookies.
    if (preg_match('/^Set-Cookie: ([^=]+)=(.+)/', $header, $matches)) {
      $name = $matches[1];
      $parts = array_map('trim', explode(';', $matches[2]));
      $value = array_shift($parts);
      $this->cookies[$name] = ['value' => $value, 'secure' => in_array('secure', $parts)];
      if ($name === $this->getSessionName()) {
        if ($value != 'deleted') {
          $this->sessionId = $value;
        }
        else {
          $this->sessionId = NULL;
        }
      }
    }

    // This is required by cURL.
    return strlen($header);
  }

  /**
   * Close the cURL handler and unset the handler.
   */
  protected function curlClose() {
    if (isset($this->curlHandle)) {
      curl_close($this->curlHandle);
      unset($this->curlHandle);
    }
  }

  /**
   * Returns whether the test is being executed from within a test site.
   *
   * Mainly used by recursive tests (i.e. to test the testing framework).
   *
   * @return bool
   *   TRUE if this test was instantiated in a request within the test site,
   *   FALSE otherwise.
   *
   * @see \Drupal\Core\DrupalKernel::bootConfiguration()
   */
  protected function isInChildSite() {
    return DRUPAL_TEST_IN_CHILD_SITE;
  }

  /**
   * Retrieves a Drupal path or an absolute path.
   *
   * @param \Drupal\Core\Url|string $path
   *   Drupal path or URL to load into internal browser
   * @param $options
   *   Options to be forwarded to the url generator.
   * @param $headers
   *   An array containing additional HTTP request headers, each formatted as
   *   "name: value".
   *
   * @return string
   *   The retrieved HTML string, also available as $this->getRawContent()
   */
  protected function drupalGet($path, array $options = [], array $headers = []) {
    // We re-using a CURL connection here. If that connection still has certain
    // options set, it might change the GET into a POST. Make sure we clear out
    // previous options.
    $out = $this->curlExec([CURLOPT_HTTPGET => TRUE, CURLOPT_URL => $this->buildUrl($path, $options), CURLOPT_NOBODY => FALSE, CURLOPT_HTTPHEADER => $headers]);
    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    // Replace original page output with new output from redirected page(s).
    if ($new = $this->checkForMetaRefresh()) {
      $out = $new;
      // We are finished with all meta refresh redirects, so reset the counter.
      $this->metaRefreshCount = 0;
    }

    if ($path instanceof Url) {
      $path = $path->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    }

    $verbose = 'GET request to: ' . $path .
               '<hr />Ending URL: ' . $this->getUrl();
    if ($this->dumpHeaders) {
      $verbose .= '<hr />Headers: <pre>' . Html::escape(var_export(array_map('trim', $this->headers), TRUE)) . '</pre>';
    }
    $verbose .= '<hr />' . $out;

    $this->verbose($verbose);
    return $out;
  }

  /**
   * Retrieves a Drupal path or an absolute path and JSON decodes the result.
   *
   * @param \Drupal\Core\Url|string $path
   *   Drupal path or URL to request AJAX from.
   * @param array $options
   *   Array of URL options.
   * @param array $headers
   *   Array of headers. Eg array('Accept: application/vnd.drupal-ajax').
   *
   * @return array
   *   Decoded json.
   */
  protected function drupalGetJSON($path, array $options = [], array $headers = []) {
    return Json::decode($this->drupalGetWithFormat($path, 'json', $options, $headers));
  }

  /**
   * Retrieves a Drupal path or an absolute path for a given format.
   *
   * @param \Drupal\Core\Url|string $path
   *   Drupal path or URL to request given format from.
   * @param string $format
   *   The wanted request format.
   * @param array $options
   *   Array of URL options.
   * @param array $headers
   *   Array of headers.
   *
   * @return mixed
   *   The result of the request.
   */
  protected function drupalGetWithFormat($path, $format, array $options = [], array $headers = []) {
    $options = array_merge_recursive(['query' => ['_format' => $format]], $options);
    return $this->drupalGet($path, $options, $headers);
  }

  /**
   * Requests a path or URL in drupal_ajax format and JSON-decodes the response.
   *
   * @param \Drupal\Core\Url|string $path
   *   Drupal path or URL to request from.
   * @param array $options
   *   Array of URL options.
   * @param array $headers
   *   Array of headers.
   *
   * @return array
   *   Decoded JSON.
   */
  protected function drupalGetAjax($path, array $options = [], array $headers = []) {
    if (!isset($options['query'][MainContentViewSubscriber::WRAPPER_FORMAT])) {
      $options['query'][MainContentViewSubscriber::WRAPPER_FORMAT] = 'drupal_ajax';
    }
    return Json::decode($this->drupalGetXHR($path, $options, $headers));
  }

  /**
   * Requests a Drupal path or an absolute path as if it is a XMLHttpRequest.
   *
   * @param \Drupal\Core\Url|string $path
   *   Drupal path or URL to request from.
   * @param array $options
   *   Array of URL options.
   * @param array $headers
   *   Array of headers.
   *
   * @return string
   *   The retrieved content.
   */
  protected function drupalGetXHR($path, array $options = [], array $headers = []) {
    $headers[] = 'X-Requested-With: XMLHttpRequest';
    return $this->drupalGet($path, $options, $headers);
  }

  /**
   * Executes a form submission.
   *
   * It will be done as usual POST request with SimpleBrowser.
   *
   * @param \Drupal\Core\Url|string $path
   *   Location of the post form. Either a Drupal path or an absolute path or
   *   NULL to post to the current page. For multi-stage forms you can set the
   *   path to NULL and have it post to the last received page. Example:
   *
   *   @code
   *   // First step in form.
   *   $edit = array(...);
   *   $this->drupalPostForm('some_url', $edit, t('Save'));
   *
   *   // Second step in form.
   *   $edit = array(...);
   *   $this->drupalPostForm(NULL, $edit, t('Save'));
   *   @endcode
   * @param $edit
   *   Field data in an associative array. Changes the current input fields
   *   (where possible) to the values indicated.
   *
   *   When working with form tests, the keys for an $edit element should match
   *   the 'name' parameter of the HTML of the form. For example, the 'body'
   *   field for a node has the following HTML:
   *   @code
   *   <textarea id="edit-body-und-0-value" class="text-full form-textarea
   *    resize-vertical" placeholder="" cols="60" rows="9"
   *    name="body[0][value]"></textarea>
   *   @endcode
   *   When testing this field using an $edit parameter, the code becomes:
   *   @code
   *   $edit["body[0][value]"] = 'My test value';
   *   @endcode
   *
   *   A checkbox can be set to TRUE to be checked and should be set to FALSE to
   *   be unchecked. Multiple select fields can be tested using 'name[]' and
   *   setting each of the desired values in an array:
   *   @code
   *   $edit = array();
   *   $edit['name[]'] = array('value1', 'value2');
   *   @endcode
   * @param $submit
   *   Value of the submit button whose click is to be emulated. For example,
   *   t('Save'). The processing of the request depends on this value. For
   *   example, a form may have one button with the value t('Save') and another
   *   button with the value t('Delete'), and execute different code depending
   *   on which one is clicked.
   *
   *   This function can also be called to emulate an Ajax submission. In this
   *   case, this value needs to be an array with the following keys:
   *   - path: A path to submit the form values to for Ajax-specific processing.
   *   - triggering_element: If the value for the 'path' key is a generic Ajax
   *     processing path, this needs to be set to the name of the element. If
   *     the name doesn't identify the element uniquely, then this should
   *     instead be an array with a single key/value pair, corresponding to the
   *     element name and value. The \Drupal\Core\Form\FormAjaxResponseBuilder
   *     uses this to find the #ajax information for the element, including
   *     which specific callback to use for processing the request.
   *
   *   This can also be set to NULL in order to emulate an Internet Explorer
   *   submission of a form with a single text field, and pressing ENTER in that
   *   textfield: under these conditions, no button information is added to the
   *   POST data.
   * @param $options
   *   Options to be forwarded to the url generator.
   * @param $headers
   *   An array containing additional HTTP request headers, each formatted as
   *   "name: value".
   * @param $form_html_id
   *   (optional) HTML ID of the form to be submitted. On some pages
   *   there are many identical forms, so just using the value of the submit
   *   button is not enough. For example: 'trigger-node-presave-assign-form'.
   *   Note that this is not the Drupal $form_id, but rather the HTML ID of the
   *   form, which is typically the same thing but with hyphens replacing the
   *   underscores.
   * @param $extra_post
   *   (optional) A string of additional data to append to the POST submission.
   *   This can be used to add POST data for which there are no HTML fields, as
   *   is done by drupalPostAjaxForm(). This string is literally appended to the
   *   POST data, so it must already be urlencoded and contain a leading "&"
   *   (e.g., "&extra_var1=hello+world&extra_var2=you%26me").
   */
  protected function drupalPostForm($path, $edit, $submit, array $options = [], array $headers = [], $form_html_id = NULL, $extra_post = NULL) {
    if (is_object($submit)) {
      // Cast MarkupInterface objects to string.
      $submit = (string) $submit;
    }
    if (is_array($edit)) {
      $edit = $this->castSafeStrings($edit);
    }

    $submit_matches = FALSE;
    $ajax = is_array($submit);
    if (isset($path)) {
      $this->drupalGet($path, $options);
    }

    if ($this->parse()) {
      $edit_save = $edit;
      // Let's iterate over all the forms.
      $xpath = "//form";
      if (!empty($form_html_id)) {
        $xpath .= "[@id='" . $form_html_id . "']";
      }
      $forms = $this->xpath($xpath);
      foreach ($forms as $form) {
        // We try to set the fields of this form as specified in $edit.
        $edit = $edit_save;
        $post = [];
        $upload = [];
        $submit_matches = $this->handleForm($post, $edit, $upload, $ajax ? NULL : $submit, $form);
        $action = isset($form['action']) ? $this->getAbsoluteUrl((string) $form['action']) : $this->getUrl();
        if ($ajax) {
          if (empty($submit['path'])) {
            throw new \Exception('No #ajax path specified.');
          }
          $action = $this->getAbsoluteUrl($submit['path']);
          // Ajax callbacks verify the triggering element if necessary, so while
          // we may eventually want extra code that verifies it in the
          // handleForm() function, it's not currently a requirement.
          $submit_matches = TRUE;
        }
        // We post only if we managed to handle every field in edit and the
        // submit button matches.
        if (!$edit && ($submit_matches || !isset($submit))) {
          $post_array = $post;
          if ($upload) {
            foreach ($upload as $key => $file) {
              if (is_array($file) && count($file)) {
                // There seems to be no way via php's API to cURL to upload
                // several files with the same post field name. However, Drupal
                // still sees array-index syntax in a similar way.
                for ($i = 0; $i < count($file); $i++) {
                  $postfield = str_replace('[]', '', $key) . '[' . $i . ']';
                  $file_path = $this->container->get('file_system')->realpath($file[$i]);
                  $post[$postfield] = curl_file_create($file_path);
                }
              }
              else {
                $file = $this->container->get('file_system')->realpath($file);
                if ($file && is_file($file)) {
                  $post[$key] = curl_file_create($file);
                }
              }
            }
          }
          else {
            $post = $this->serializePostValues($post) . $extra_post;
          }
          $out = $this->curlExec([CURLOPT_URL => $action, CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_HTTPHEADER => $headers]);
          // Ensure that any changes to variables in the other thread are picked
          // up.
          $this->refreshVariables();

          // Replace original page output with new output from redirected
          // page(s).
          if ($new = $this->checkForMetaRefresh()) {
            $out = $new;
          }

          if ($path instanceof Url) {
            $path = $path->toString();
          }
          $verbose = 'POST request to: ' . $path;
          $verbose .= '<hr />Ending URL: ' . $this->getUrl();
          if ($this->dumpHeaders) {
            $verbose .= '<hr />Headers: <pre>' . Html::escape(var_export(array_map('trim', $this->headers), TRUE)) . '</pre>';
          }
          $verbose .= '<hr />Fields: ' . highlight_string('<?php ' . var_export($post_array, TRUE), TRUE);
          $verbose .= '<hr />' . $out;

          $this->verbose($verbose);
          return $out;
        }
      }
      // We have not found a form which contained all fields of $edit.
      foreach ($edit as $name => $value) {
        $this->fail(new FormattableMarkup('Failed to set field @name to @value', ['@name' => $name, '@value' => $value]));
      }
      if (!$ajax && isset($submit)) {
        $this->assertTrue($submit_matches, format_string('Found the @submit button', ['@submit' => $submit]));
      }
      $this->fail(format_string('Found the requested form fields at @path', ['@path' => ($path instanceof Url) ? $path->toString() : $path]));
    }
  }

  /**
   * Executes an Ajax form submission.
   *
   * This executes a POST as ajax.js does. The returned JSON data is used to
   * update $this->content via drupalProcessAjaxResponse(). It also returns
   * the array of AJAX commands received.
   *
   * @param \Drupal\Core\Url|string $path
   *   Location of the form containing the Ajax enabled element to test. Can be
   *   either a Drupal path or an absolute path or NULL to use the current page.
   * @param $edit
   *   Field data in an associative array. Changes the current input fields
   *   (where possible) to the values indicated.
   * @param $triggering_element
   *   The name of the form element that is responsible for triggering the Ajax
   *   functionality to test. May be a string or, if the triggering element is
   *   a button, an associative array where the key is the name of the button
   *   and the value is the button label. i.e.) array('op' => t('Refresh')).
   * @param $ajax_path
   *   (optional) Override the path set by the Ajax settings of the triggering
   *   element.
   * @param $options
   *   (optional) Options to be forwarded to the url generator.
   * @param $headers
   *   (optional) An array containing additional HTTP request headers, each
   *   formatted as "name: value". Forwarded to drupalPostForm().
   * @param $form_html_id
   *   (optional) HTML ID of the form to be submitted, use when there is more
   *   than one identical form on the same page and the value of the triggering
   *   element is not enough to identify the form. Note this is not the Drupal
   *   ID of the form but rather the HTML ID of the form.
   * @param $ajax_settings
   *   (optional) An array of Ajax settings which if specified will be used in
   *   place of the Ajax settings of the triggering element.
   *
   * @return
   *   An array of Ajax commands.
   *
   * @see drupalPostForm()
   * @see drupalProcessAjaxResponse()
   * @see ajax.js
   */
  protected function drupalPostAjaxForm($path, $edit, $triggering_element, $ajax_path = NULL, array $options = [], array $headers = [], $form_html_id = NULL, $ajax_settings = NULL) {

    // Get the content of the initial page prior to calling drupalPostForm(),
    // since drupalPostForm() replaces $this->content.
    if (isset($path)) {
      // Avoid sending the wrapper query argument to drupalGet so we can fetch
      // the form and populate the internal WebTest values.
      $get_options = $options;
      unset($get_options['query'][MainContentViewSubscriber::WRAPPER_FORMAT]);
      $this->drupalGet($path, $get_options);
    }
    $content = $this->content;
    $drupal_settings = $this->drupalSettings;

    // Provide a default value for the wrapper envelope.
    $options['query'][MainContentViewSubscriber::WRAPPER_FORMAT] =
      isset($options['query'][MainContentViewSubscriber::WRAPPER_FORMAT]) ?
        $options['query'][MainContentViewSubscriber::WRAPPER_FORMAT] :
        'drupal_ajax';

    // Get the Ajax settings bound to the triggering element.
    if (!isset($ajax_settings)) {
      if (is_array($triggering_element)) {
        $xpath = '//*[@name="' . key($triggering_element) . '" and @value="' . current($triggering_element) . '"]';
      }
      else {
        $xpath = '//*[@name="' . $triggering_element . '"]';
      }
      if (isset($form_html_id)) {
        $xpath = '//form[@id="' . $form_html_id . '"]' . $xpath;
      }
      $element = $this->xpath($xpath);
      $element_id = (string) $element[0]['id'];
      $ajax_settings = $drupal_settings['ajax'][$element_id];
    }

    // Add extra information to the POST data as ajax.js does.
    $extra_post = [];
    if (isset($ajax_settings['submit'])) {
      foreach ($ajax_settings['submit'] as $key => $value) {
        $extra_post[$key] = $value;
      }
    }
    $extra_post[AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER] = 1;
    $extra_post += $this->getAjaxPageStatePostData();
    // Now serialize all the $extra_post values, and prepend it with an '&'.
    $extra_post = '&' . $this->serializePostValues($extra_post);

    // Unless a particular path is specified, use the one specified by the
    // Ajax settings.
    if (!isset($ajax_path)) {
      if (isset($ajax_settings['url'])) {
        // In order to allow to set for example the wrapper envelope query
        // parameter we need to get the system path again.
        $parsed_url = UrlHelper::parse($ajax_settings['url']);
        $options['query'] = $parsed_url['query'] + $options['query'];
        $options += ['fragment' => $parsed_url['fragment']];

        // We know that $parsed_url['path'] is already with the base path
        // attached.
        $ajax_path = preg_replace(
          '/^' . preg_quote(base_path(), '/') . '/',
          '',
          $parsed_url['path']
        );
      }
    }

    if (empty($ajax_path)) {
      throw new \Exception('No #ajax path specified.');
    }

    $ajax_path = $this->container->get('unrouted_url_assembler')->assemble('base://' . $ajax_path, $options);

    // Submit the POST request.
    $return = Json::decode($this->drupalPostForm(NULL, $edit, ['path' => $ajax_path, 'triggering_element' => $triggering_element], $options, $headers, $form_html_id, $extra_post));
    if ($this->assertAjaxHeader) {
      $this->assertIdentical($this->drupalGetHeader('X-Drupal-Ajax-Token'), '1', 'Ajax response header found.');
    }

    // Change the page content by applying the returned commands.
    if (!empty($ajax_settings) && !empty($return)) {
      $this->drupalProcessAjaxResponse($content, $return, $ajax_settings, $drupal_settings);
    }

    $verbose = 'AJAX POST request to: ' . $path;
    $verbose .= '<br />AJAX controller path: ' . $ajax_path;
    $verbose .= '<hr />Ending URL: ' . $this->getUrl();
    $verbose .= '<hr />' . $this->content;

    $this->verbose($verbose);

    return $return;
  }

  /**
   * Processes an AJAX response into current content.
   *
   * This processes the AJAX response as ajax.js does. It uses the response's
   * JSON data, an array of commands, to update $this->content using equivalent
   * DOM manipulation as is used by ajax.js.
   * It does not apply custom AJAX commands though, because emulation is only
   * implemented for the AJAX commands that ship with Drupal core.
   *
   * @param string $content
   *   The current HTML content.
   * @param array $ajax_response
   *   An array of AJAX commands.
   * @param array $ajax_settings
   *   An array of AJAX settings which will be used to process the response.
   * @param array $drupal_settings
   *   An array of settings to update the value of drupalSettings for the
   *   currently-loaded page.
   *
   * @see drupalPostAjaxForm()
   * @see ajax.js
   */
  protected function drupalProcessAjaxResponse($content, array $ajax_response, array $ajax_settings, array $drupal_settings) {

    // ajax.js applies some defaults to the settings object, so do the same
    // for what's used by this function.
    $ajax_settings += [
      'method' => 'replaceWith',
    ];
    // DOM can load HTML soup. But, HTML soup can throw warnings, suppress
    // them.
    $dom = new \DOMDocument();
    @$dom->loadHTML($content);
    // XPath allows for finding wrapper nodes better than DOM does.
    $xpath = new \DOMXPath($dom);
    foreach ($ajax_response as $command) {
      // Error messages might be not commands.
      if (!is_array($command)) {
        continue;
      }
      switch ($command['command']) {
        case 'settings':
          $drupal_settings = NestedArray::mergeDeepArray([$drupal_settings, $command['settings']], TRUE);
          break;

        case 'insert':
          $wrapperNode = NULL;
          // When a command doesn't specify a selector, use the
          // #ajax['wrapper'] which is always an HTML ID.
          if (!isset($command['selector'])) {
            $wrapperNode = $xpath->query('//*[@id="' . $ajax_settings['wrapper'] . '"]')->item(0);
          }
          // @todo Ajax commands can target any jQuery selector, but these are
          //   hard to fully emulate with XPath. For now, just handle 'head'
          //   and 'body', since these are used by the Ajax renderer.
          elseif (in_array($command['selector'], ['head', 'body'])) {
            $wrapperNode = $xpath->query('//' . $command['selector'])->item(0);
          }
          if ($wrapperNode) {
            // ajax.js adds an enclosing DIV to work around a Safari bug.
            $newDom = new \DOMDocument();
            // DOM can load HTML soup. But, HTML soup can throw warnings,
            // suppress them.
            @$newDom->loadHTML('<div>' . $command['data'] . '</div>');
            // Suppress warnings thrown when duplicate HTML IDs are encountered.
            // This probably means we are replacing an element with the same ID.
            $newNode = @$dom->importNode($newDom->documentElement->firstChild->firstChild, TRUE);
            $method = isset($command['method']) ? $command['method'] : $ajax_settings['method'];
            // The "method" is a jQuery DOM manipulation function. Emulate
            // each one using PHP's DOMNode API.
            switch ($method) {
              case 'replaceWith':
                $wrapperNode->parentNode->replaceChild($newNode, $wrapperNode);
                break;
              case 'append':
                $wrapperNode->appendChild($newNode);
                break;
              case 'prepend':
                // If no firstChild, insertBefore() falls back to
                // appendChild().
                $wrapperNode->insertBefore($newNode, $wrapperNode->firstChild);
                break;
              case 'before':
                $wrapperNode->parentNode->insertBefore($newNode, $wrapperNode);
                break;
              case 'after':
                // If no nextSibling, insertBefore() falls back to
                // appendChild().
                $wrapperNode->parentNode->insertBefore($newNode, $wrapperNode->nextSibling);
                break;
              case 'html':
                foreach ($wrapperNode->childNodes as $childNode) {
                  $wrapperNode->removeChild($childNode);
                }
                $wrapperNode->appendChild($newNode);
                break;
            }
          }
          break;

        // @todo Add suitable implementations for these commands in order to
        //   have full test coverage of what ajax.js can do.
        case 'remove':
          break;
        case 'changed':
          break;
        case 'css':
          break;
        case 'data':
          break;
        case 'restripe':
          break;
        case 'add_css':
          break;
        case 'update_build_id':
          $buildId = $xpath->query('//input[@name="form_build_id" and @value="' . $command['old'] . '"]')->item(0);
          if ($buildId) {
            $buildId->setAttribute('value', $command['new']);
          }
          break;
      }
    }
    $content = $dom->saveHTML();
    $this->setRawContent($content);
    $this->setDrupalSettings($drupal_settings);
  }

  /**
   * Perform a POST HTTP request.
   *
   * @param string|\Drupal\Core\Url $path
   *   Drupal path or absolute path where the request should be POSTed.
   * @param string $accept
   *   The value for the "Accept" header. Usually either 'application/json' or
   *   'application/vnd.drupal-ajax'.
   * @param array $post
   *   The POST data. When making a 'application/vnd.drupal-ajax' request, the
   *   Ajax page state data should be included. Use getAjaxPageStatePostData()
   *   for that.
   * @param array $options
   *   (optional) Options to be forwarded to the url generator. The 'absolute'
   *   option will automatically be enabled.
   *
   * @return
   *   The content returned from the call to curl_exec().
   *
   * @see WebTestBase::getAjaxPageStatePostData()
   * @see WebTestBase::curlExec()
   */
  protected function drupalPost($path, $accept, array $post, $options = []) {
    return $this->curlExec([
      CURLOPT_URL => $this->buildUrl($path, $options),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $this->serializePostValues($post),
      CURLOPT_HTTPHEADER => [
        'Accept: ' . $accept,
        'Content-Type: application/x-www-form-urlencoded',
      ],
    ]);
  }

  /**
   * Performs a POST HTTP request with a specific format.
   *
   * @param string|\Drupal\Core\Url $path
   *   Drupal path or absolute path where the request should be POSTed.
   * @param string $format
   *   The request format.
   * @param array $post
   *   The POST data. When making a 'application/vnd.drupal-ajax' request, the
   *   Ajax page state data should be included. Use getAjaxPageStatePostData()
   *   for that.
   * @param array $options
   *   (optional) Options to be forwarded to the url generator. The 'absolute'
   *   option will automatically be enabled.
   *
   * @return string
   *   The content returned from the call to curl_exec().
   *
   * @see WebTestBase::drupalPost
   * @see WebTestBase::getAjaxPageStatePostData()
   * @see WebTestBase::curlExec()
   */
  protected function drupalPostWithFormat($path, $format, array $post, $options = []) {
    $options['query']['_format'] = $format;
    return $this->drupalPost($path, '', $post, $options);
  }

  /**
   * Get the Ajax page state from drupalSettings and prepare it for POSTing.
   *
   * @return array
   *   The Ajax page state POST data.
   */
  protected function getAjaxPageStatePostData() {
    $post = [];
    $drupal_settings = $this->drupalSettings;
    if (isset($drupal_settings['ajaxPageState']['theme'])) {
      $post['ajax_page_state[theme]'] = $drupal_settings['ajaxPageState']['theme'];
    }
    if (isset($drupal_settings['ajaxPageState']['theme_token'])) {
      $post['ajax_page_state[theme_token]'] = $drupal_settings['ajaxPageState']['theme_token'];
    }
    if (isset($drupal_settings['ajaxPageState']['libraries'])) {
      $post['ajax_page_state[libraries]'] = $drupal_settings['ajaxPageState']['libraries'];
    }
    return $post;
  }

  /**
   * Serialize POST HTTP request values.
   *
   * Encode according to application/x-www-form-urlencoded. Both names and
   * values needs to be urlencoded, according to
   * http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4.1
   *
   * @param array $post
   *   The array of values to be POSTed.
   *
   * @return string
   *   The serialized result.
   */
  protected function serializePostValues($post = []) {
    foreach ($post as $key => $value) {
      $post[$key] = urlencode($key) . '=' . urlencode($value);
    }
    return implode('&', $post);
  }

  /**
   * Transforms a nested array into a flat array suitable for WebTestBase::drupalPostForm().
   *
   * @param array $values
   *   A multi-dimensional form values array to convert.
   *
   * @return array
   *   The flattened $edit array suitable for WebTestBase::drupalPostForm().
   */
  protected function translatePostValues(array $values) {
    $edit = [];
    // The easiest and most straightforward way to translate values suitable for
    // WebTestBase::drupalPostForm() is to actually build the POST data string
    // and convert the resulting key/value pairs back into a flat array.
    $query = http_build_query($values);
    foreach (explode('&', $query) as $item) {
      list($key, $value) = explode('=', $item);
      $edit[urldecode($key)] = urldecode($value);
    }
    return $edit;
  }

  /**
   * Checks for meta refresh tag and if found call drupalGet() recursively.
   *
   * This function looks for the http-equiv attribute to be set to "Refresh" and
   * is case-sensitive.
   *
   * @return
   *   Either the new page content or FALSE.
   */
  protected function checkForMetaRefresh() {
    if (strpos($this->getRawContent(), '<meta ') && $this->parse() && (!isset($this->maximumMetaRefreshCount) || $this->metaRefreshCount < $this->maximumMetaRefreshCount)) {
      $refresh = $this->xpath('//meta[@http-equiv="Refresh"]');
      if (!empty($refresh)) {
        // Parse the content attribute of the meta tag for the format:
        // "[delay]: URL=[page_to_redirect_to]".
        if (preg_match('/\d+;\s*URL=(?<url>.*)/i', $refresh[0]['content'], $match)) {
          $this->metaRefreshCount++;
          return $this->drupalGet($this->getAbsoluteUrl(Html::decodeEntities($match['url'])));
        }
      }
    }
    return FALSE;
  }

  /**
   * Retrieves only the headers for a Drupal path or an absolute path.
   *
   * @param $path
   *   Drupal path or URL to load into internal browser
   * @param $options
   *   Options to be forwarded to the url generator.
   * @param $headers
   *   An array containing additional HTTP request headers, each formatted as
   *   "name: value".
   *
   * @return
   *   The retrieved headers, also available as $this->getRawContent()
   */
  protected function drupalHead($path, array $options = [], array $headers = []) {
    $options['absolute'] = TRUE;
    $url = $this->buildUrl($path, $options);
    $out = $this->curlExec([CURLOPT_NOBODY => TRUE, CURLOPT_URL => $url, CURLOPT_HTTPHEADER => $headers]);
    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    if ($this->dumpHeaders) {
      $this->verbose('GET request to: ' . $path .
                     '<hr />Ending URL: ' . $this->getUrl() .
                     '<hr />Headers: <pre>' . Html::escape(var_export(array_map('trim', $this->headers), TRUE)) . '</pre>');
    }

    return $out;
  }

  /**
   * Handles form input related to drupalPostForm().
   *
   * Ensure that the specified fields exist and attempt to create POST data in
   * the correct manner for the particular field type.
   *
   * @param $post
   *   Reference to array of post values.
   * @param $edit
   *   Reference to array of edit values to be checked against the form.
   * @param $submit
   *   Form submit button value.
   * @param $form
   *   Array of form elements.
   *
   * @return
   *   Submit value matches a valid submit input in the form.
   */
  protected function handleForm(&$post, &$edit, &$upload, $submit, $form) {
    // Retrieve the form elements.
    $elements = $form->xpath('.//input[not(@disabled)]|.//textarea[not(@disabled)]|.//select[not(@disabled)]');
    $submit_matches = FALSE;
    foreach ($elements as $element) {
      // SimpleXML objects need string casting all the time.
      $name = (string) $element['name'];
      // This can either be the type of <input> or the name of the tag itself
      // for <select> or <textarea>.
      $type = isset($element['type']) ? (string) $element['type'] : $element->getName();
      $value = isset($element['value']) ? (string) $element['value'] : '';
      $done = FALSE;
      if (isset($edit[$name])) {
        switch ($type) {
          case 'text':
          case 'tel':
          case 'textarea':
          case 'url':
          case 'number':
          case 'range':
          case 'color':
          case 'hidden':
          case 'password':
          case 'email':
          case 'search':
          case 'date':
          case 'time':
          case 'datetime':
          case 'datetime-local';
            $post[$name] = $edit[$name];
            unset($edit[$name]);
            break;
          case 'radio':
            if ($edit[$name] == $value) {
              $post[$name] = $edit[$name];
              unset($edit[$name]);
            }
            break;
          case 'checkbox':
            // To prevent checkbox from being checked.pass in a FALSE,
            // otherwise the checkbox will be set to its value regardless
            // of $edit.
            if ($edit[$name] === FALSE) {
              unset($edit[$name]);
              continue 2;
            }
            else {
              unset($edit[$name]);
              $post[$name] = $value;
            }
            break;
          case 'select':
            $new_value = $edit[$name];
            $options = $this->getAllOptions($element);
            if (is_array($new_value)) {
              // Multiple select box.
              if (!empty($new_value)) {
                $index = 0;
                $key = preg_replace('/\[\]$/', '', $name);
                foreach ($options as $option) {
                  $option_value = (string) $option['value'];
                  if (in_array($option_value, $new_value)) {
                    $post[$key . '[' . $index++ . ']'] = $option_value;
                    $done = TRUE;
                    unset($edit[$name]);
                  }
                }
              }
              else {
                // No options selected: do not include any POST data for the
                // element.
                $done = TRUE;
                unset($edit[$name]);
              }
            }
            else {
              // Single select box.
              foreach ($options as $option) {
                if ($new_value == $option['value']) {
                  $post[$name] = $new_value;
                  unset($edit[$name]);
                  $done = TRUE;
                  break;
                }
              }
            }
            break;
          case 'file':
            $upload[$name] = $edit[$name];
            unset($edit[$name]);
            break;
        }
      }
      if (!isset($post[$name]) && !$done) {
        switch ($type) {
          case 'textarea':
            $post[$name] = (string) $element;
            break;
          case 'select':
            $single = empty($element['multiple']);
            $first = TRUE;
            $index = 0;
            $key = preg_replace('/\[\]$/', '', $name);
            $options = $this->getAllOptions($element);
            foreach ($options as $option) {
              // For single select, we load the first option, if there is a
              // selected option that will overwrite it later.
              if ($option['selected'] || ($first && $single)) {
                $first = FALSE;
                if ($single) {
                  $post[$name] = (string) $option['value'];
                }
                else {
                  $post[$key . '[' . $index++ . ']'] = (string) $option['value'];
                }
              }
            }
            break;
          case 'file':
            break;
          case 'submit':
          case 'image':
            if (isset($submit) && $submit == $value) {
              $post[$name] = $value;
              $submit_matches = TRUE;
            }
            break;
          case 'radio':
          case 'checkbox':
            if (!isset($element['checked'])) {
              break;
            }
            // Deliberate no break.
          default:
            $post[$name] = $value;
        }
      }
    }
    // An empty name means the value is not sent.
    unset($post['']);
    return $submit_matches;
  }

  /**
   * Follows a link by complete name.
   *
   * Will click the first link found with this link text by default, or a later
   * one if an index is given. Match is case sensitive with normalized space.
   * The label is translated label.
   *
   * If the link is discovered and clicked, the test passes. Fail otherwise.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags.
   * @param int $index
   *   Link position counting from zero.
   *
   * @return string|bool
   *   Page contents on success, or FALSE on failure.
   */
  protected function clickLink($label, $index = 0) {
    return $this->clickLinkHelper($label, $index, '//a[normalize-space()=:label]');
  }

  /**
   * Follows a link by partial name.
   *
   * If the link is discovered and clicked, the test passes. Fail otherwise.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags, uses starts-with().
   * @param int $index
   *   Link position counting from zero.
   *
   * @return string|bool
   *   Page contents on success, or FALSE on failure.
   *
   * @see ::clickLink()
   */
  protected function clickLinkPartialName($label, $index = 0) {
    return $this->clickLinkHelper($label, $index, '//a[starts-with(normalize-space(), :label)]');
  }

  /**
   * Provides a helper for ::clickLink() and ::clickLinkPartialName().
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags, uses starts-with().
   * @param int $index
   *   Link position counting from zero.
   * @param string $pattern
   *   A pattern to use for the XPath.
   *
   * @return bool|string
   *   Page contents on success, or FALSE on failure.
   */
  protected function clickLinkHelper($label, $index, $pattern) {
    // Cast MarkupInterface objects to string.
    $label = (string) $label;
    $url_before = $this->getUrl();
    $urls = $this->xpath($pattern, [':label' => $label]);
    if (isset($urls[$index])) {
      $url_target = $this->getAbsoluteUrl($urls[$index]['href']);
      $this->pass(new FormattableMarkup('Clicked link %label (@url_target) from @url_before', ['%label' => $label, '@url_target' => $url_target, '@url_before' => $url_before]), 'Browser');
      return $this->drupalGet($url_target);
    }
    $this->fail(new FormattableMarkup('Link %label does not exist on @url_before', ['%label' => $label, '@url_before' => $url_before]), 'Browser');
    return FALSE;
  }

  /**
   * Takes a path and returns an absolute path.
   *
   * This method is implemented in the way that browsers work, see
   * https://url.spec.whatwg.org/#relative-state for more information about the
   * possible cases.
   *
   * @param string $path
   *   A path from the internal browser content.
   *
   * @return string
   *   The $path with $base_url prepended, if necessary.
   */
  protected function getAbsoluteUrl($path) {
    global $base_url, $base_path;

    $parts = parse_url($path);

    // In case the $path has a host, it is already an absolute URL and we are
    // done.
    if (!empty($parts['host'])) {
      return $path;
    }

    // In case the $path contains just a query, we turn it into an absolute URL
    // with the same scheme, host and path, see
    // https://url.spec.whatwg.org/#relative-state.
    if (array_keys($parts) === ['query']) {
      $current_uri = new Uri($this->getUrl());
      return (string) $current_uri->withQuery($parts['query']);
    }

    if (empty($parts['host'])) {
      // Ensure that we have a string (and no xpath object).
      $path = (string) $path;
      // Strip $base_path, if existent.
      $length = strlen($base_path);
      if (substr($path, 0, $length) === $base_path) {
        $path = substr($path, $length);
      }
      // Ensure that we have an absolute path.
      if (empty($path) || $path[0] !== '/') {
        $path = '/' . $path;
      }
      // Finally, prepend the $base_url.
      $path = $base_url . $path;
    }
    return $path;
  }

  /**
   * Gets the HTTP response headers of the requested page.
   *
   * Normally we are only interested in the headers returned by the last
   * request. However, if a page is redirected or HTTP authentication is in use,
   * multiple requests will be required to retrieve the page. Headers from all
   * requests may be requested by passing TRUE to this function.
   *
   * @param $all_requests
   *   Boolean value specifying whether to return headers from all requests
   *   instead of just the last request. Defaults to FALSE.
   *
   * @return
   *   A name/value array if headers from only the last request are requested.
   *   If headers from all requests are requested, an array of name/value
   *   arrays, one for each request.
   *
   *   The pseudonym ":status" is used for the HTTP status line.
   *
   *   Values for duplicate headers are stored as a single comma-separated list.
   */
  protected function drupalGetHeaders($all_requests = FALSE) {
    $request = 0;
    $headers = [$request => []];
    foreach ($this->headers as $header) {
      $header = trim($header);
      if ($header === '') {
        $request++;
      }
      else {
        if (strpos($header, 'HTTP/') === 0) {
          $name = ':status';
          $value = $header;
        }
        else {
          list($name, $value) = explode(':', $header, 2);
          $name = strtolower($name);
        }
        if (isset($headers[$request][$name])) {
          $headers[$request][$name] .= ',' . trim($value);
        }
        else {
          $headers[$request][$name] = trim($value);
        }
      }
    }
    if (!$all_requests) {
      $headers = array_pop($headers);
    }
    return $headers;
  }

  /**
   * Gets the value of an HTTP response header.
   *
   * If multiple requests were required to retrieve the page, only the headers
   * from the last request will be checked by default. However, if TRUE is
   * passed as the second argument, all requests will be processed from last to
   * first until the header is found.
   *
   * @param $name
   *   The name of the header to retrieve. Names are case-insensitive (see RFC
   *   2616 section 4.2).
   * @param $all_requests
   *   Boolean value specifying whether to check all requests if the header is
   *   not found in the last request. Defaults to FALSE.
   *
   * @return
   *   The HTTP header value or FALSE if not found.
   */
  protected function drupalGetHeader($name, $all_requests = FALSE) {
    $name = strtolower($name);
    $header = FALSE;
    if ($all_requests) {
      foreach (array_reverse($this->drupalGetHeaders(TRUE)) as $headers) {
        if (isset($headers[$name])) {
          $header = $headers[$name];
          break;
        }
      }
    }
    else {
      $headers = $this->drupalGetHeaders();
      if (isset($headers[$name])) {
        $header = $headers[$name];
      }
    }
    return $header;
  }

  /**
   * Check if a HTTP response header exists and has the expected value.
   *
   * @param string $header
   *   The header key, example: Content-Type
   * @param string $value
   *   The header value.
   * @param string $message
   *   (optional) A message to display with the assertion.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertHeader($header, $value, $message = '', $group = 'Browser') {
    $header_value = $this->drupalGetHeader($header);
    return $this->assertTrue($header_value == $value, $message ? $message : 'HTTP response header ' . $header . ' with value ' . $value . ' found, actual value: ' . $header_value, $group);
  }

  /**
   * Passes if the internal browser's URL matches the given path.
   *
   * @param \Drupal\Core\Url|string $path
   *   The expected system path or URL.
   * @param $options
   *   (optional) Any additional options to pass for $path to the url generator.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertUrl($path, array $options = [], $message = '', $group = 'Other') {
    if ($path instanceof Url) {
      $url_obj = $path;
    }
    elseif (UrlHelper::isExternal($path)) {
      $url_obj = Url::fromUri($path, $options);
    }
    else {
      $uri = $path === '<front>' ? 'base:/' : 'base:/' . $path;
      // This is needed for language prefixing.
      $options['path_processing'] = TRUE;
      $url_obj = Url::fromUri($uri, $options);
    }
    $url = $url_obj->setAbsolute()->toString();
    if (!$message) {
      $message = new FormattableMarkup('Expected @url matches current URL (@current_url).', [
        '@url' => var_export($url, TRUE),
        '@current_url' => $this->getUrl(),
      ]);
    }
    // Paths in query strings can be encoded or decoded with no functional
    // difference, decode them for comparison purposes.
    $actual_url = urldecode($this->getUrl());
    $expected_url = urldecode($url);
    return $this->assertEqual($actual_url, $expected_url, $message, $group);
  }

  /**
   * Asserts the page responds with the specified response code.
   *
   * @param $code
   *   Response code. For example 200 is a successful page request. For a list
   *   of all codes see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return
   *   Assertion result.
   */
  protected function assertResponse($code, $message = '', $group = 'Browser') {
    $curl_code = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
    $match = is_array($code) ? in_array($curl_code, $code) : $curl_code == $code;
    return $this->assertTrue($match, $message ? $message : new FormattableMarkup('HTTP response expected @code, actual @curl_code', ['@code' => $code, '@curl_code' => $curl_code]), $group);
  }

  /**
   * Asserts the page did not return the specified response code.
   *
   * @param $code
   *   Response code. For example 200 is a successful page request. For a list
   *   of all codes see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return
   *   Assertion result.
   */
  protected function assertNoResponse($code, $message = '', $group = 'Browser') {
    $curl_code = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
    $match = is_array($code) ? in_array($curl_code, $code) : $curl_code == $code;
    return $this->assertFalse($match, $message ? $message : new FormattableMarkup('HTTP response not expected @code, actual @curl_code', ['@code' => $code, '@curl_code' => $curl_code]), $group);
  }

  /**
   * Builds an a absolute URL from a system path or a URL object.
   *
   * @param string|\Drupal\Core\Url $path
   *   A system path or a URL.
   * @param array $options
   *   Options to be passed to Url::fromUri().
   *
   * @return string
   *   An absolute URL string.
   */
  protected function buildUrl($path, array $options = []) {
    if ($path instanceof Url) {
      $url_options = $path->getOptions();
      $options = $url_options + $options;
      $path->setOptions($options);
      return $path->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    }
    // The URL generator service is not necessarily available yet; e.g., in
    // interactive installer tests.
    elseif ($this->container->has('url_generator')) {
      $force_internal = isset($options['external']) && $options['external'] == FALSE;
      if (!$force_internal && UrlHelper::isExternal($path)) {
        return Url::fromUri($path, $options)->toString();
      }
      else {
        $uri = $path === '<front>' ? 'base:/' : 'base:/' . $path;
        // Path processing is needed for language prefixing.  Skip it when a
        // path that may look like an external URL is being used as internal.
        $options['path_processing'] = !$force_internal;
        return Url::fromUri($uri, $options)
          ->setAbsolute()
          ->toString();
      }
    }
    else {
      return $this->getAbsoluteUrl($path);
    }
  }

  /**
   * Asserts whether an expected cache tag was present in the last response.
   *
   * @param string $expected_cache_tag
   *   The expected cache tag.
   */
  protected function assertCacheTag($expected_cache_tag) {
    $cache_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertTrue(in_array($expected_cache_tag, $cache_tags), "'" . $expected_cache_tag . "' is present in the X-Drupal-Cache-Tags header.");
  }

  /**
   * Asserts whether an expected cache tag was absent in the last response.
   *
   * @param string $cache_tag
   *   The cache tag to check.
   */
  protected function assertNoCacheTag($cache_tag) {
    $cache_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertFalse(in_array($cache_tag, $cache_tags), "'" . $cache_tag . "' is absent in the X-Drupal-Cache-Tags header.");
  }

  /**
   * Enables/disables the cacheability headers.
   *
   * Sets the http.response.debug_cacheability_headers container parameter.
   *
   * @param bool $value
   *   (optional) Whether the debugging cacheability headers should be sent.
   */
  protected function setHttpResponseDebugCacheabilityHeaders($value = TRUE) {
    $this->setContainerParameter('http.response.debug_cacheability_headers', $value);
    $this->rebuildContainer();
    $this->resetAll();
  }

}
