<?php

namespace Drupal\Tests;

use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Element\Element;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Testing\ConfigSchemaChecker;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Test\TestRunnerKernel;
use Drupal\Core\Url;
use Drupal\Core\Test\TestDatabase;
use Drupal\FunctionalTests\AssertLegacyTrait;
use Drupal\simpletest\AssertHelperTrait;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\BlockCreationTrait;
use Drupal\simpletest\NodeCreationTrait;
use Drupal\simpletest\UserCreationTrait;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a test case for functional Drupal tests.
 *
 * Tests extending BrowserTestBase must exist in the
 * Drupal\Tests\yourmodule\Functional namespace and live in the
 * modules/yourmodule/tests/src/Functional directory.
 *
 * @ingroup testing
 */
abstract class BrowserTestBase extends \PHPUnit_Framework_TestCase {
  use AssertHelperTrait;
  use BlockCreationTrait {
    placeBlock as drupalPlaceBlock;
  }
  use AssertLegacyTrait;
  use RandomGeneratorTrait;
  use SessionTestTrait;
  use NodeCreationTrait {
    getNodeByTitle as drupalGetNodeByTitle;
    createNode as drupalCreateNode;
  }
  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }
  use ConfigTestTrait;
  use UserCreationTrait {
    createRole as drupalCreateRole;
    createUser as drupalCreateUser;
  }

  /**
   * Class loader.
   *
   * @var object
   */
  protected $classLoader;

  /**
   * The site directory of this test run.
   *
   * @var string
   */
  protected $siteDirectory;

  /**
   * The database prefix of this test run.
   *
   * @var string
   */
  protected $databasePrefix;

  /**
   * The site directory of the original parent site.
   *
   * @var string
   */
  protected $originalSiteDirectory;

  /**
   * Time limit in seconds for the test.
   *
   * @var int
   */
  protected $timeLimit = 500;

  /**
   * The public file directory for the test environment.
   *
   * This is set in BrowserTestBase::prepareEnvironment().
   *
   * @var string
   */
  protected $publicFilesDirectory;

  /**
   * The private file directory for the test environment.
   *
   * This is set in BrowserTestBase::prepareEnvironment().
   *
   * @var string
   */
  protected $privateFilesDirectory;

  /**
   * The temp file directory for the test environment.
   *
   * This is set in BrowserTestBase::prepareEnvironment().
   *
   * @var string
   */
  protected $tempFilesDirectory;

  /**
   * The translation file directory for the test environment.
   *
   * This is set in BrowserTestBase::prepareEnvironment().
   *
   * @var string
   */
  protected $translationFilesDirectory;

  /**
   * The DrupalKernel instance used in the test.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected $kernel;

  /**
   * The dependency injection container used in the test.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The config importer that can be used in a test.
   *
   * @var \Drupal\Core\Config\ConfigImporter
   */
  protected $configImporter;

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = TRUE;

  /**
   * Modules to enable.
   *
   * The test runner will merge the $modules lists from this class, the class
   * it extends, and so on up the class hierarchy. It is not necessary to
   * include modules in your list that a parent class has already declared.
   *
   * @var string[]
   *
   * @see \Drupal\Tests\BrowserTestBase::installDrupal()
   */
  public static $modules = [];

  /**
   * An array of config object names that are excluded from schema checking.
   *
   * @var string[]
   */
  protected static $configSchemaCheckerExclusions = array(
    // Following are used to test lack of or partial schema. Where partial
    // schema is provided, that is explicitly tested in specific tests.
    'config_schema_test.noschema',
    'config_schema_test.someschema',
    'config_schema_test.schema_data_types',
    'config_schema_test.no_schema_data_types',
    // Used to test application of schema to filtering of configuration.
    'config_test.dynamic.system',
  );

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * The current user logged in using the Mink controlled browser.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $loggedInUser = FALSE;

  /**
   * The root user.
   *
   * @var \Drupal\Core\Session\UserSession
   */
  protected $rootUser;

  /**
   * The config directories used in this test.
   *
   * @var array
   */
  protected $configDirectories = array();

  /**
   * An array of custom translations suitable for drupal_rewrite_settings().
   *
   * @var array
   */
  protected $customTranslations;

  /*
   * Mink class for the default driver to use.
   *
   * Shoud be a fully qualified class name that implements
   * Behat\Mink\Driver\DriverInterface.
   *
   * Value can be overridden using the environment variable MINK_DRIVER_CLASS.
   *
   * @var string.
   */
  protected $minkDefaultDriverClass = GoutteDriver::class;

  /*
   * Mink default driver params.
   *
   * If it's an array its contents are used as constructor params when default
   * Mink driver class is instantiated.
   *
   * Can be overridden using the environment variable MINK_DRIVER_ARGS. In this
   * case that variable should be a JSON array, for example:
   * '["firefox", null, "http://localhost:4444/wd/hub"]'.
   *
   *
   * @var array
   */
  protected $minkDefaultDriverArgs;

  /**
   * Mink session manager.
   *
   * This will not be initialized if there was an error during the test setup.
   *
   * @var \Behat\Mink\Mink|null
   */
  protected $mink;

  /**
   * {@inheritdoc}
   *
   * Browser tests are run in separate processes to prevent collisions between
   * code that may be loaded by tests.
   */
  protected $runTestInSeparateProcess = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $preserveGlobalState = FALSE;

  /**
   * Class name for HTML output logging.
   *
   * @var string
   */
  protected $htmlOutputClassName;

  /**
   * Directory name for HTML output logging.
   *
   * @var string
   */
  protected $htmlOutputDirectory;

  /**
   * Counter storage for HTML output logging.
   *
   * @var string
   */
  protected $htmlOutputCounterStorage;

  /**
   * Counter for HTML output logging.
   *
   * @var int
   */
  protected $htmlOutputCounter = 1;

  /**
   * HTML output output enabled.
   *
   * @var bool
   */
  protected $htmlOutputEnabled = FALSE;

  /**
   * The file name to write the list of URLs to.
   *
   * This file is read by the PHPUnit result printer.
   *
   * @var string
   *
   * @see \Drupal\Tests\Listeners\HtmlOutputPrinter
   */
  protected $htmlOutputFile;

  /**
   * HTML output test ID.
   *
   * @var int
   */
  protected $htmlOutputTestId;

  /**
   * The base URL.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * The original array of shutdown function callbacks.
   *
   * @var array
   */
  protected $originalShutdownCallbacks = [];

  /**
   * Initializes Mink sessions.
   */
  protected function initMink() {
    $driver = $this->getDefaultDriverInstance();

    if ($driver instanceof GoutteDriver) {
      $driver->getClient()->setClient(\Drupal::httpClient());
    }

    $session = new Session($driver);
    $this->mink = new Mink();
    $this->mink->registerSession('default', $session);
    $this->mink->setDefaultSessionName('default');
    $this->registerSessions();

    // According to the W3C WebDriver specification a cookie can only be set if
    // the cookie domain is equal to the domain of the active document. When the
    // browser starts up the active document is not our domain but 'about:blank'
    // or similar. To be able to set our User-Agent and Xdebug cookies at the
    // start of the test we now do a request to the front page so the active
    // document matches the domain.
    // @see https://w3c.github.io/webdriver/webdriver-spec.html#add-cookie
    // @see https://www.w3.org/Bugs/Public/show_bug.cgi?id=20975
    $session = $this->getSession();
    $session->visit($this->baseUrl);

    return $session;
  }

  /**
   * Gets an instance of the default Mink driver.
   *
   * @return Behat\Mink\Driver\DriverInterface
   *   Instance of default Mink driver.
   *
   * @throws \InvalidArgumentException
   *   When provided default Mink driver class can't be instantiated.
   */
  protected function getDefaultDriverInstance() {
    // Get default driver params from environment if availables.
    if ($arg_json = getenv('MINK_DRIVER_ARGS')) {
      $this->minkDefaultDriverArgs = json_decode($arg_json);
    }

    // Get and check default driver class from environment if availables.
    if ($minkDriverClass = getenv('MINK_DRIVER_CLASS')) {
      if (class_exists($minkDriverClass)) {
        $this->minkDefaultDriverClass = $minkDriverClass;
      }
      else {
        throw new \InvalidArgumentException("Can't instantiate provided $minkDriverClass class by environment as default driver class.");
      }
    }

    if (is_array($this->minkDefaultDriverArgs)) {
      // Use ReflectionClass to instantiate class with received params.
      $reflector = new \ReflectionClass($this->minkDefaultDriverClass);
      $driver = $reflector->newInstanceArgs($this->minkDefaultDriverArgs);
    }
    else {
      $driver = new $this->minkDefaultDriverClass();
    }
    return $driver;
  }

  /**
   * Registers additional Mink sessions.
   *
   * Tests wishing to use a different driver or change the default driver should
   * override this method.
   *
   * @code
   *   // Register a new session that uses the MinkPonyDriver.
   *   $pony = new MinkPonyDriver();
   *   $session = new Session($pony);
   *   $this->mink->registerSession('pony', $session);
   * @endcode
   */
  protected function registerSessions() {}

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    global $base_url;
    parent::setUp();

    // Get and set the domain of the environment we are running our test
    // coverage against.
    $base_url = getenv('SIMPLETEST_BASE_URL');
    if (!$base_url) {
      throw new \Exception(
        'You must provide a SIMPLETEST_BASE_URL environment variable to run some PHPUnit based functional tests.'
      );
    }

    // Setup $_SERVER variable.
    $parsed_url = parse_url($base_url);
    $host = $parsed_url['host'] . (isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '');
    $path = isset($parsed_url['path']) ? rtrim(rtrim($parsed_url['path']), '/') : '';
    $port = isset($parsed_url['port']) ? $parsed_url['port'] : 80;

    $this->baseUrl = $base_url;

    // If the passed URL schema is 'https' then setup the $_SERVER variables
    // properly so that testing will run under HTTPS.
    if ($parsed_url['scheme'] === 'https') {
      $_SERVER['HTTPS'] = 'on';
    }
    $_SERVER['HTTP_HOST'] = $host;
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['SERVER_ADDR'] = '127.0.0.1';
    $_SERVER['SERVER_PORT'] = $port;
    $_SERVER['SERVER_SOFTWARE'] = NULL;
    $_SERVER['SERVER_NAME'] = 'localhost';
    $_SERVER['REQUEST_URI'] = $path . '/';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['SCRIPT_NAME'] = $path . '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $path . '/index.php';
    $_SERVER['PHP_SELF'] = $path . '/index.php';
    $_SERVER['HTTP_USER_AGENT'] = 'Drupal command line';

    // Install Drupal test site.
    $this->prepareEnvironment();
    $this->installDrupal();

    // Setup Mink.
    $session = $this->initMink();

    // In order to debug web tests you need to either set a cookie, have the
    // Xdebug session in the URL or set an environment variable in case of CLI
    // requests. If the developer listens to connection when running tests, by
    // default the cookie is not forwarded to the client side, so you cannot
    // debug the code running on the test site. In order to make debuggers work
    // this bit of information is forwarded. Make sure that the debugger listens
    // to at least three external connections.
    $request = \Drupal::request();
    $cookie_params = $request->cookies;
    if ($cookie_params->has('XDEBUG_SESSION')) {
      $session->setCookie('XDEBUG_SESSION', $cookie_params->get('XDEBUG_SESSION'));
    }
    // For CLI requests, the information is stored in $_SERVER.
    $server = $request->server;
    if ($server->has('XDEBUG_CONFIG')) {
      // $_SERVER['XDEBUG_CONFIG'] has the form "key1=value1 key2=value2 ...".
      $pairs = explode(' ', $server->get('XDEBUG_CONFIG'));
      foreach ($pairs as $pair) {
        list($key, $value) = explode('=', $pair);
        // Account for key-value pairs being separated by multiple spaces.
        if (trim($key) == 'idekey') {
          $session->setCookie('XDEBUG_SESSION', trim($value));
        }
      }
    }

    // Creates the directory to store browser output in if a file to write
    // URLs to has been created by \Drupal\Tests\Listeners\HtmlOutputPrinter.
    $browser_output_file = getenv('BROWSERTEST_OUTPUT_FILE');
    $this->htmlOutputEnabled = is_file($browser_output_file);
    if ($this->htmlOutputEnabled) {
      $this->htmlOutputFile = $browser_output_file;
      $this->htmlOutputClassName = str_replace("\\", "_", get_called_class());
      $this->htmlOutputDirectory = DRUPAL_ROOT . '/sites/simpletest/browser_output';
      if (file_prepare_directory($this->htmlOutputDirectory, FILE_CREATE_DIRECTORY) && !file_exists($this->htmlOutputDirectory . '/.htaccess')) {
        file_put_contents($this->htmlOutputDirectory . '/.htaccess', "<IfModule mod_expires.c>\nExpiresActive Off\n</IfModule>\n");
      }
      $this->htmlOutputCounterStorage = $this->htmlOutputDirectory . '/' . $this->htmlOutputClassName . '.counter';
      $this->htmlOutputTestId = str_replace('sites/simpletest/', '', $this->siteDirectory);
      if (is_file($this->htmlOutputCounterStorage)) {
        $this->htmlOutputCounter = max(1, (int) file_get_contents($this->htmlOutputCounterStorage)) + 1;
      }
    }
  }

  /**
   * Ensures test files are deletable within file_unmanaged_delete_recursive().
   *
   * Some tests chmod generated files to be read only. During
   * BrowserTestBase::cleanupEnvironment() and other cleanup operations,
   * these files need to get deleted too.
   *
   * @param string $path
   *   The file path.
   */
  public static function filePreDeleteCallback($path) {
    // When the webserver runs with the same system user as phpunit, we can
    // make read-only files writable again. If not, chmod will fail while the
    // file deletion still works if file permissions have been configured
    // correctly. Thus, we ignore any problems while running chmod.
    @chmod($path, 0700);
  }

  /**
   * Clean up the Simpletest environment.
   */
  protected function cleanupEnvironment() {
    // Remove all prefixed tables.
    $original_connection_info = Database::getConnectionInfo('simpletest_original_default');
    $original_prefix = $original_connection_info['default']['prefix']['default'];
    $test_connection_info = Database::getConnectionInfo('default');
    $test_prefix = $test_connection_info['default']['prefix']['default'];
    if ($original_prefix != $test_prefix) {
      $tables = Database::getConnection()->schema()->findTables('%');
      foreach ($tables as $table) {
        if (Database::getConnection()->schema()->dropTable($table)) {
          unset($tables[$table]);
        }
      }
    }

    // Delete test site directory.
    file_unmanaged_delete_recursive($this->siteDirectory, array($this, 'filePreDeleteCallback'));
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();

    // Destroy the testing kernel.
    if (isset($this->kernel)) {
      $this->cleanupEnvironment();
      $this->kernel->shutdown();
    }

    // Ensure that internal logged in variable is reset.
    $this->loggedInUser = FALSE;

    if ($this->mink) {
      $this->mink->stopSessions();
    }

    // Restore original shutdown callbacks.
    if (function_exists('drupal_register_shutdown_function')) {
      $callbacks = &drupal_register_shutdown_function();
      $callbacks = $this->originalShutdownCallbacks;
    }
  }

  /**
   * Returns Mink session.
   *
   * @param string $name
   *   (optional) Name of the session. Defaults to the active session.
   *
   * @return \Behat\Mink\Session
   *   The active Mink session object.
   */
  public function getSession($name = NULL) {
    return $this->mink->getSession($name);
  }

  /**
   * Returns WebAssert object.
   *
   * @param string $name
   *   (optional) Name of the session. Defaults to the active session.
   *
   * @return \Drupal\Tests\WebAssert
   *   A new web-assert option for asserting the presence of elements with.
   */
  public function assertSession($name = NULL) {
    return new WebAssert($this->getSession($name), $this->baseUrl);
  }

  /**
   * Prepare for a request to testing site.
   *
   * The testing site is protected via a SIMPLETEST_USER_AGENT cookie that is
   * checked by drupal_valid_test_ua().
   *
   * @see drupal_valid_test_ua()
   */
  protected function prepareRequest() {
    $session = $this->getSession();
    $session->setCookie('SIMPLETEST_USER_AGENT', drupal_generate_test_ua($this->databasePrefix));
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
   *   An absolute URL stsring.
   */
  protected function buildUrl($path, array $options = array()) {
    if ($path instanceof Url) {
      $url_options = $path->getOptions();
      $options = $url_options + $options;
      $path->setOptions($options);
      return $path->setAbsolute()->toString();
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
   * Retrieves a Drupal path or an absolute path.
   *
   * @param string|\Drupal\Core\Url $path
   *   Drupal path or URL to load into Mink controlled browser.
   * @param array $options
   *   (optional) Options to be forwarded to the url generator.
   * @param string[] $headers
   *   An array containing additional HTTP request headers, the array keys are
   *   the header names and the array values the header values. This is useful
   *   to set for example the "Accept-Language" header for requesting the page
   *   in a different language. Note that not all headers are supported, for
   *   example the "Accept" header is always overridden by the browser. For
   *   testing REST APIs it is recommended to directly use an HTTP client such
   *   as Guzzle instead.
   *
   * @return string
   *   The retrieved HTML string, also available as $this->getRawContent()
   */
  protected function drupalGet($path, array $options = array(), array $headers = array()) {
    $options['absolute'] = TRUE;
    $url = $this->buildUrl($path, $options);

    $session = $this->getSession();

    $this->prepareRequest();
    foreach ($headers as $header_name => $header_value) {
      $session->setRequestHeader($header_name, $header_value);
    }

    $session->visit($url);
    $out = $session->getPage()->getContent();

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    if ($this->htmlOutputEnabled) {
      $html_output = 'GET request to: ' . $url .
        '<hr />Ending URL: ' . $this->getSession()->getCurrentUrl();
      $html_output .= '<hr />' . $out;
      $html_output .= $this->getHtmlOutputHeaders();
      $this->htmlOutput($html_output);
    }

    return $out;
  }

  /**
   * Takes a path and returns an absolute path.
   *
   * @param string $path
   *   A path from the Mink controlled browser content.
   *
   * @return string
   *   The $path with $base_url prepended, if necessary.
   */
  protected function getAbsoluteUrl($path) {
    global $base_url, $base_path;

    $parts = parse_url($path);
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
   * Logs in a user using the Mink controlled browser.
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
   *   $pass_raw = $account->passRaw;
   *   $account = User::load($account->id());
   *   $account->passRaw = $pass_raw;
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

    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm(array(
      'name' => $account->getUsername(),
      'pass' => $account->passRaw,
    ), t('Log in'));

    // @see BrowserTestBase::drupalUserIsLoggedIn()
    $account->sessionId = $this->getSession()->getCookie($this->getSessionName());
    $this->assertTrue($this->drupalUserIsLoggedIn($account), SafeMarkup::format('User %name successfully logged in.', array('name' => $account->getUsername())));

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

  /**
   * Logs a user out of the Mink controlled browser and confirms.
   *
   * Confirms logout by checking the login page.
   */
  protected function drupalLogout() {
    // Make a request to the logout page, and redirect to the user page, the
    // idea being if you were properly logged out you should be seeing a login
    // screen.
    $assert_session = $this->assertSession();
    $this->drupalGet('user/logout', array('query' => array('destination' => 'user')));
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldExists('name');
    $assert_session->fieldExists('pass');

    // @see BrowserTestBase::drupalUserIsLoggedIn()
    unset($this->loggedInUser->sessionId);
    $this->loggedInUser = FALSE;
    $this->container->get('current_user')->setAccount(new AnonymousUserSession());
  }

  /**
   * Fills and submits a form.
   *
   * @param array $edit
   *   Field data in an associative array. Changes the current input fields
   *   (where possible) to the values indicated.
   *
   *   A checkbox can be set to TRUE to be checked and should be set to FALSE to
   *   be unchecked.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated. For example,
   *   t('Save'). The processing of the request depends on this value. For
   *   example, a form may have one button with the value t('Save') and another
   *   button with the value t('Delete'), and execute different code depending
   *   on which one is clicked.
   * @param string $form_html_id
   *   (optional) HTML ID of the form to be submitted. On some pages
   *   there are many identical forms, so just using the value of the submit
   *   button is not enough. For example: 'trigger-node-presave-assign-form'.
   *   Note that this is not the Drupal $form_id, but rather the HTML ID of the
   *   form, which is typically the same thing but with hyphens replacing the
   *   underscores.
   */
  protected function submitForm(array $edit, $submit, $form_html_id = NULL) {
    $assert_session = $this->assertSession();

    // Get the form.
    if (isset($form_html_id)) {
      $form = $assert_session->elementExists('xpath', "//form[@id='$form_html_id']");
      $submit_button = $assert_session->buttonExists($submit, $form);
      $action = $form->getAttribute('action');
    }
    else {
      $submit_button = $assert_session->buttonExists($submit);
      $form = $assert_session->elementExists('xpath', './ancestor::form', $submit_button);
      $action = $form->getAttribute('action');
    }

    // Edit the form values.
    foreach ($edit as $name => $value) {
      $field = $assert_session->fieldExists($name, $form);

      // Provide support for the values '1' and '0' for checkboxes instead of
      // TRUE and FALSE.
      // @todo Get rid of supporting 1/0 by converting all tests cases using
      // this to boolean values.
      $field_type = $field->getAttribute('type');
      if ($field_type === 'checkbox') {
        $value = (bool) $value;
      }

      $field->setValue($value);
    }

    // Submit form.
    $this->prepareRequest();
    $submit_button->press();

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();
    if ($this->htmlOutputEnabled) {
      $out = $this->getSession()->getPage()->getContent();
      $html_output = 'POST request to: ' . $action .
        '<hr />Ending URL: ' . $this->getSession()->getCurrentUrl();
      $html_output .= '<hr />' . $out;
      $html_output .= $this->getHtmlOutputHeaders();
      $this->htmlOutput($html_output);
    }
  }

  /**
   * Executes a form submission.
   *
   * It will be done as usual POST request with Mink.
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
   * @param array $edit
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
   * @param string $submit
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
   * @param array $options
   *   Options to be forwarded to the url generator.
   */
  protected function drupalPostForm($path, array $edit, $submit, array $options = array()) {
    if (is_object($submit)) {
      // Cast MarkupInterface objects to string.
      $submit = (string) $submit;
    }
    if (is_array($edit)) {
      $edit = $this->castSafeStrings($edit);
    }

    if (isset($path)) {
      $this->drupalGet($path, $options);
    }

    $this->submitForm($edit, $submit);
  }

  /**
   * Helper function to get the options of select field.
   *
   * @param \Behat\Mink\Element\NodeElement|string $select
   *   Name, ID, or Label of select field to assert.
   * @param \Behat\Mink\Element\Element $container
   *   (optional) Container element to check against. Defaults to current page.
   *
   * @return array
   *   Associative array of option keys and values.
   */
  protected function getOptions($select, Element $container = NULL) {
    if (is_string($select)) {
      $select = $this->assertSession()->selectExists($select, $container);
    }
    $options = [];
    /* @var \Behat\Mink\Element\NodeElement $option */
    foreach ($select->findAll('xpath', '//option') as $option) {
      $label = $option->getText();
      $value = $option->getAttribute('value') ?: $label;
      $options[$value] = $label;
    }
    return $options;
  }

  /**
   * Installs Drupal into the Simpletest site.
   */
  public function installDrupal() {
    // Define information about the user 1 account.
    $this->rootUser = new UserSession(array(
      'uid' => 1,
      'name' => 'admin',
      'mail' => 'admin@example.com',
      'passRaw' => $this->randomMachineName(),
    ));

    // The child site derives its session name from the database prefix when
    // running web tests.
    $this->generateSessionName($this->databasePrefix);

    // Get parameters for install_drupal() before removing global variables.
    $parameters = $this->installParameters();

    // Prepare installer settings that are not install_drupal() parameters.
    // Copy and prepare an actual settings.php, so as to resemble a regular
    // installation.
    // Not using File API; a potential error must trigger a PHP warning.
    $directory = DRUPAL_ROOT . '/' . $this->siteDirectory;
    copy(DRUPAL_ROOT . '/sites/default/default.settings.php', $directory . '/settings.php');

    // All file system paths are created by System module during installation.
    // @see system_requirements()
    // @see TestBase::prepareEnvironment()
    $settings['settings']['file_public_path'] = (object) array(
      'value' => $this->publicFilesDirectory,
      'required' => TRUE,
    );
    $settings['settings']['file_private_path'] = (object) [
      'value' => $this->privateFilesDirectory,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    // Allow for test-specific overrides.
    $settings_testing_file = DRUPAL_ROOT . '/' . $this->originalSiteDirectory . '/settings.testing.php';
    if (file_exists($settings_testing_file)) {
      // Copy the testing-specific settings.php overrides in place.
      copy($settings_testing_file, $directory . '/settings.testing.php');
      // Add the name of the testing class to settings.php and include the
      // testing specific overrides.
      file_put_contents($directory . '/settings.php', "\n\$test_class = '" . get_class($this) . "';\n" . 'include DRUPAL_ROOT . \'/\' . $site_path . \'/settings.testing.php\';' . "\n", FILE_APPEND);
    }

    $settings_services_file = DRUPAL_ROOT . '/' . $this->originalSiteDirectory . '/testing.services.yml';
    if (!file_exists($settings_services_file)) {
      // Otherwise, use the default services as a starting point for overrides.
      $settings_services_file = DRUPAL_ROOT . '/sites/default/default.services.yml';
    }
    // Copy the testing-specific service overrides in place.
    copy($settings_services_file, $directory . '/services.yml');
    if ($this->strictConfigSchema) {
      // Add a listener to validate configuration schema on save.
      $content = file_get_contents($directory . '/services.yml');
      $services = Yaml::decode($content);
      $services['services']['simpletest.config_schema_checker'] = [
        'class' => ConfigSchemaChecker::class,
        'arguments' => ['@config.typed', $this->getConfigSchemaExclusions()],
        'tags' => [['name' => 'event_subscriber']]
      ];
      file_put_contents($directory . '/services.yml', Yaml::encode($services));
    }

    // Since Drupal is bootstrapped already, install_begin_request() will not
    // bootstrap into DRUPAL_BOOTSTRAP_CONFIGURATION (again). Hence, we have to
    // reload the newly written custom settings.php manually.
    Settings::initialize(DRUPAL_ROOT, $directory, $this->classLoader);

    // Execute the non-interactive installer.
    require_once DRUPAL_ROOT . '/core/includes/install.core.inc';
    install_drupal($parameters);

    // Import new settings.php written by the installer.
    Settings::initialize(DRUPAL_ROOT, $directory, $this->classLoader);
    foreach ($GLOBALS['config_directories'] as $type => $path) {
      $this->configDirectories[$type] = $path;
    }

    // After writing settings.php, the installer removes write permissions from
    // the site directory. To allow drupal_generate_test_ua() to write a file
    // containing the private key for drupal_valid_test_ua(), the site directory
    // has to be writable.
    // TestBase::restoreEnvironment() will delete the entire site directory. Not
    // using File API; a potential error must trigger a PHP warning.
    chmod($directory, 0777);

    // During tests, cacheable responses should get the debugging cacheability
    // headers by default.
    $this->setContainerParameter('http.response.debug_cacheability_headers', TRUE);

    $request = \Drupal::request();
    $this->kernel = DrupalKernel::createFromRequest($request, $this->classLoader, 'prod', TRUE);
    $this->kernel->prepareLegacyRequest($request);
    // Force the container to be built from scratch instead of loaded from the
    // disk. This forces us to not accidentally load the parent site.
    $container = $this->kernel->rebuildContainer();

    $config = $container->get('config.factory');

    // Manually create and configure private and temporary files directories.
    file_prepare_directory($this->privateFilesDirectory, FILE_CREATE_DIRECTORY);
    file_prepare_directory($this->tempFilesDirectory, FILE_CREATE_DIRECTORY);
    // While the temporary files path could be preset/enforced in settings.php
    // like the public files directory above, some tests expect it to be
    // configurable in the UI. If declared in settings.php, it would no longer
    // be configurable.
    $config->getEditable('system.file')
      ->set('path.temporary', $this->tempFilesDirectory)
      ->save();

    // Manually configure the test mail collector implementation to prevent
    // tests from sending out emails and collect them in state instead.
    // While this should be enforced via settings.php prior to installation,
    // some tests expect to be able to test mail system implementations.
    $config->getEditable('system.mail')
      ->set('interface.default', 'test_mail_collector')
      ->save();

    // By default, verbosely display all errors and disable all production
    // environment optimizations for all tests to avoid needless overhead and
    // ensure a sane default experience for test authors.
    // @see https://www.drupal.org/node/2259167
    $config->getEditable('system.logging')
      ->set('error_level', 'verbose')
      ->save();
    $config->getEditable('system.performance')
      ->set('css.preprocess', FALSE)
      ->set('js.preprocess', FALSE)
      ->save();

    // Collect modules to install.
    $class = get_class($this);
    $modules = array();
    while ($class) {
      if (property_exists($class, 'modules')) {
        $modules = array_merge($modules, $class::$modules);
      }
      $class = get_parent_class($class);
    }
    if ($modules) {
      $modules = array_unique($modules);
      $success = $container->get('module_installer')->install($modules, TRUE);
      $this->assertTrue($success, SafeMarkup::format('Enabled modules: %modules', array('%modules' => implode(', ', $modules))));
      $this->rebuildContainer();
    }

    // Reset/rebuild all data structures after enabling the modules, primarily
    // to synchronize all data structures and caches between the test runner and
    // the child site.
    // Affects e.g. StreamWrapperManagerInterface::getWrappers().
    // @see \Drupal\Core\DrupalKernel::bootCode()
    // @todo Test-specific setUp() methods may set up further fixtures; find a
    //   way to execute this after setUp() is done, or to eliminate it entirely.
    $this->resetAll();
    $this->kernel->prepareLegacyRequest($request);
  }

  /**
   * Returns the parameters that will be used when Simpletest installs Drupal.
   *
   * @see install_drupal()
   * @see install_state_defaults()
   */
  protected function installParameters() {
    $connection_info = Database::getConnectionInfo();
    $driver = $connection_info['default']['driver'];
    $connection_info['default']['prefix'] = $connection_info['default']['prefix']['default'];
    unset($connection_info['default']['driver']);
    unset($connection_info['default']['namespace']);
    unset($connection_info['default']['pdo']);
    unset($connection_info['default']['init_commands']);
    $parameters = array(
      'interactive' => FALSE,
      'parameters' => array(
        'profile' => $this->profile,
        'langcode' => 'en',
      ),
      'forms' => array(
        'install_settings_form' => array(
          'driver' => $driver,
          $driver => $connection_info['default'],
        ),
        'install_configure_form' => array(
          'site_name' => 'Drupal',
          'site_mail' => 'simpletest@example.com',
          'account' => array(
            'name' => $this->rootUser->name,
            'mail' => $this->rootUser->getEmail(),
            'pass' => array(
              'pass1' => $this->rootUser->passRaw,
              'pass2' => $this->rootUser->passRaw,
            ),
          ),
          // form_type_checkboxes_value() requires NULL instead of FALSE values
          // for programmatic form submissions to disable a checkbox.
          'update_status_module' => array(
            1 => NULL,
            2 => NULL,
          ),
        ),
      ),
    );
    return $parameters;
  }

  /**
   * Generates a database prefix for running tests.
   *
   * The database prefix is used by prepareEnvironment() to setup a public files
   * directory for the test to be run, which also contains the PHP error log,
   * which is written to in case of a fatal error. Since that directory is based
   * on the database prefix, all tests (even unit tests) need to have one, in
   * order to access and read the error log.
   *
   * The generated database table prefix is used for the Drupal installation
   * being performed for the test. It is also used by the cookie value of
   * SIMPLETEST_USER_AGENT by the Mink controlled browser. During early Drupal
   * bootstrap, the cookie is parsed, and if it matches, all database queries
   * use the database table prefix that has been generated here.
   *
   * @see drupal_valid_test_ua()
   * @see BrowserTestBase::prepareEnvironment()
   */
  private function prepareDatabasePrefix() {
    $test_db = new TestDatabase();
    $this->siteDirectory = $test_db->getTestSitePath();
    $this->databasePrefix = $test_db->getDatabasePrefix();
  }

  /**
   * Changes the database connection to the prefixed one.
   *
   * @see BrowserTestBase::prepareEnvironment()
   */
  private function changeDatabasePrefix() {
    if (empty($this->databasePrefix)) {
      $this->prepareDatabasePrefix();
    }

    // If the test is run with argument dburl then use it.
    $db_url = getenv('SIMPLETEST_DB');
    if (!empty($db_url)) {
      $database = Database::convertDbUrlToConnectionInfo($db_url, DRUPAL_ROOT);
      Database::addConnectionInfo('default', 'default', $database);
    }

    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo('default');
    if (is_null($connection_info)) {
      throw new \InvalidArgumentException('There is no database connection so no tests can be run. You must provide a SIMPLETEST_DB environment variable to run PHPUnit based functional tests outside of run-tests.sh.');
    }
    else {
      Database::renameConnection('default', 'simpletest_original_default');
      foreach ($connection_info as $target => $value) {
        // Replace the full table prefix definition to ensure that no table
        // prefixes of the test runner leak into the test.
        $connection_info[$target]['prefix'] = array(
          'default' => $value['prefix']['default'] . $this->databasePrefix,
        );
      }
      Database::addConnectionInfo('default', 'default', $connection_info['default']);
    }
  }

  /**
   * Prepares the current environment for running the test.
   *
   * Also sets up new resources for the testing environment, such as the public
   * filesystem and configuration directories.
   *
   * This method is private as it must only be called once by
   * BrowserTestBase::setUp() (multiple invocations for the same test would have
   * unpredictable consequences) and it must not be callable or overridable by
   * test classes.
   */
  protected function prepareEnvironment() {
    // Bootstrap Drupal so we can use Drupal's built in functions.
    $this->classLoader = require __DIR__ . '/../../../../autoload.php';
    $request = Request::createFromGlobals();
    $kernel = TestRunnerKernel::createFromRequest($request, $this->classLoader);
    // TestRunnerKernel expects the working directory to be DRUPAL_ROOT.
    chdir(DRUPAL_ROOT);
    $kernel->prepareLegacyRequest($request);
    $this->prepareDatabasePrefix();

    $this->originalSiteDirectory = $kernel->findSitePath($request);

    // Create test directory ahead of installation so fatal errors and debug
    // information can be logged during installation process.
    file_prepare_directory($this->siteDirectory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

    // Prepare filesystem directory paths.
    $this->publicFilesDirectory = $this->siteDirectory . '/files';
    $this->privateFilesDirectory = $this->siteDirectory . '/private';
    $this->tempFilesDirectory = $this->siteDirectory . '/temp';
    $this->translationFilesDirectory = $this->siteDirectory . '/translations';

    // Ensure the configImporter is refreshed for each test.
    $this->configImporter = NULL;

    // Unregister all custom stream wrappers of the parent site.
    $wrappers = \Drupal::service('stream_wrapper_manager')->getWrappers(StreamWrapperInterface::ALL);
    foreach ($wrappers as $scheme => $info) {
      stream_wrapper_unregister($scheme);
    }

    // Reset statics.
    drupal_static_reset();

    // Ensure there is no service container.
    $this->container = NULL;
    \Drupal::unsetContainer();

    // Unset globals.
    unset($GLOBALS['config_directories']);
    unset($GLOBALS['config']);
    unset($GLOBALS['conf']);

    // Log fatal errors.
    ini_set('log_errors', 1);
    ini_set('error_log', DRUPAL_ROOT . '/' . $this->siteDirectory . '/error.log');

    // Change the database prefix.
    $this->changeDatabasePrefix();

    // After preparing the environment and changing the database prefix, we are
    // in a valid test environment.
    drupal_valid_test_ua($this->databasePrefix);

    // Reset settings.
    new Settings(array(
      // For performance, simply use the database prefix as hash salt.
      'hash_salt' => $this->databasePrefix,
    ));

    drupal_set_time_limit($this->timeLimit);

    // Save and clean the shutdown callbacks array because it is static cached
    // and will be changed by the test run. Otherwise it will contain callbacks
    // from both environments and the testing environment will try to call the
    // handlers defined by the original one.
    $callbacks = &drupal_register_shutdown_function();
    $this->originalShutdownCallbacks = $callbacks;
    $callbacks = [];
  }

  /**
   * Returns the database connection to the site running Simpletest.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection to use for inserting assertions.
   */
  public static function getDatabaseConnection() {
    return TestDatabase::getConnection();
  }

  /**
   * Rewrites the settings.php file of the test site.
   *
   * @param array $settings
   *   An array of settings to write out, in the format expected by
   *   drupal_rewrite_settings().
   *
   * @see drupal_rewrite_settings()
   */
  protected function writeSettings(array $settings) {
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    $filename = $this->siteDirectory . '/settings.php';

    // system_requirements() removes write permissions from settings.php
    // whenever it is invoked.
    // Not using File API; a potential error must trigger a PHP warning.
    chmod($filename, 0666);
    drupal_rewrite_settings($settings, $filename);
  }

  /**
   * Rebuilds \Drupal::getContainer().
   *
   * Use this to build a new kernel and service container. For example, when the
   * list of enabled modules is changed via the Mink controlled browser, in
   * which case the test process still contains an old kernel and service
   * container with an old module list.
   *
   * @see BrowserTestBase::prepareEnvironment()
   * @see BrowserTestBase::restoreEnvironment()
   *
   * @todo Fix https://www.drupal.org/node/2021959 so that module enable/disable
   *   changes are immediately reflected in \Drupal::getContainer(). Until then,
   *   tests can invoke this workaround when requiring services from newly
   *   enabled modules to be immediately available in the same request.
   */
  protected function rebuildContainer() {
    // Rebuild the kernel and bring it back to a fully bootstrapped state.
    $this->container = $this->kernel->rebuildContainer();

    // Make sure the url generator has a request object, otherwise calls to
    // $this->drupalGet() will fail.
    $this->prepareRequestForGenerator();
  }

  /**
   * Creates a mock request and sets it on the generator.
   *
   * This is used to manipulate how the generator generates paths during tests.
   * It also ensures that calls to $this->drupalGet() will work when running
   * from run-tests.sh because the url generator no longer looks at the global
   * variables that are set there but relies on getting this information from a
   * request object.
   *
   * @param bool $clean_urls
   *   Whether to mock the request using clean urls.
   * @param array $override_server_vars
   *   An array of server variables to override.
   *
   * @return Request
   *   The mocked request object.
   */
  protected function prepareRequestForGenerator($clean_urls = TRUE, $override_server_vars = array()) {
    $request = Request::createFromGlobals();
    $server = $request->server->all();
    if (basename($server['SCRIPT_FILENAME']) != basename($server['SCRIPT_NAME'])) {
      // We need this for when the test is executed by run-tests.sh.
      // @todo Remove this once run-tests.sh has been converted to use a Request
      //   object.
      $cwd = getcwd();
      $server['SCRIPT_FILENAME'] = $cwd . '/' . basename($server['SCRIPT_NAME']);
      $base_path = rtrim($server['REQUEST_URI'], '/');
    }
    else {
      $base_path = $request->getBasePath();
    }
    if ($clean_urls) {
      $request_path = $base_path ? $base_path . '/user' : 'user';
    }
    else {
      $request_path = $base_path ? $base_path . '/index.php/user' : '/index.php/user';
    }
    $server = array_merge($server, $override_server_vars);

    $request = Request::create($request_path, 'GET', array(), array(), array(), $server);
    // Ensure the request time is REQUEST_TIME to ensure that API calls
    // in the test use the right timestamp.
    $request->server->set('REQUEST_TIME', REQUEST_TIME);
    $this->container->get('request_stack')->push($request);

    // The request context is normally set by the router_listener from within
    // its KernelEvents::REQUEST listener. In the Simpletest parent site this
    // event is not fired, therefore it is necessary to updated the request
    // context manually here.
    $this->container->get('router.request_context')->fromRequest($request);

    return $request;
  }

  /**
   * Resets all data structures after having enabled new modules.
   *
   * This method is called by \Drupal\simpletest\BrowserTestBase::setUp() after
   * enabling the requested modules. It must be called again when additional
   * modules are enabled later.
   */
  protected function resetAll() {
    // Clear all database and static caches and rebuild data structures.
    drupal_flush_all_caches();
    $this->container = \Drupal::getContainer();

    // Reset static variables and reload permissions.
    $this->refreshVariables();
  }

  /**
   * Refreshes in-memory configuration and state information.
   *
   * Useful after a page request is made that changes configuration or state in
   * a different thread.
   *
   * In other words calling a settings page with $this->submitForm() with a
   * changed value would update configuration to reflect that change, but in the
   * thread that made the call (thread running the test) the changed values
   * would not be picked up.
   *
   * This method clears the cache and loads a fresh copy.
   */
  protected function refreshVariables() {
    // Clear the tag cache.
    $this->container->get('cache_tags.invalidator')->resetChecksums();
    // @todo Replace drupal_static() usage within classes and provide a
    //   proper interface for invoking reset() on a cache backend:
    //   https://www.drupal.org/node/2311945.
    drupal_static_reset('Drupal\Core\Cache\CacheBackendInterface::tagCache');
    drupal_static_reset('Drupal\Core\Cache\DatabaseBackend::deletedTags');
    drupal_static_reset('Drupal\Core\Cache\DatabaseBackend::invalidatedTags');
    foreach (Cache::getBins() as $backend) {
      if (is_callable(array($backend, 'reset'))) {
        $backend->reset();
      }
    }

    $this->container->get('config.factory')->reset();
    $this->container->get('state')->resetCache();
  }

  /**
   * Returns whether a given user account is logged in.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account object to check.
   *
   * @return bool
   *   Return TRUE if the user is logged in, FALSE otherwise.
   */
  protected function drupalUserIsLoggedIn(AccountInterface $account) {
    $logged_in = FALSE;

    if (isset($account->sessionId)) {
      $session_handler = $this->container->get('session_handler.storage');
      $logged_in = (bool) $session_handler->read($account->sessionId);
    }

    return $logged_in;
  }

  /**
   * Clicks the element with the given CSS selector.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to click.
   */
  protected function click($css_selector) {
    $this->getSession()->getDriver()->click($this->cssSelectToXpath($css_selector));
  }

  /**
   * Prevents serializing any properties.
   *
   * Browser tests are run in a separate process. To do this PHPUnit creates a
   * script to run the test. If it fails, the test result object will contain a
   * stack trace which includes the test object. It will attempt to serialize
   * it. Returning an empty array prevents it from serializing anything it
   * should not.
   *
   * @return array
   *   An empty array.
   *
   * @see vendor/phpunit/phpunit/src/Util/PHP/Template/TestCaseMethod.tpl.dist
   */
  public function __sleep() {
    return [];
  }

  /**
   * Logs a HTML output message in a text file.
   *
   * The link to the HTML output message will be printed by the results printer.
   *
   * @param string $message
   *   The HTML output message to be stored.
   *
   * @see \Drupal\Tests\Listeners\VerbosePrinter::printResult()
   */
  protected function htmlOutput($message) {
    if (!$this->htmlOutputEnabled) {
      return;
    }
    $message = '<hr />ID #' . $this->htmlOutputCounter . ' (<a href="' . $this->htmlOutputClassName . '-' . ($this->htmlOutputCounter - 1) . '-' . $this->htmlOutputTestId . '.html">Previous</a> | <a href="' . $this->htmlOutputClassName . '-' . ($this->htmlOutputCounter + 1) . '-' . $this->htmlOutputTestId . '.html">Next</a>)<hr />' . $message;
    $html_output_filename = $this->htmlOutputClassName . '-' . $this->htmlOutputCounter . '-' . $this->htmlOutputTestId . '.html';
    file_put_contents($this->htmlOutputDirectory . '/' . $html_output_filename, $message);
    file_put_contents($this->htmlOutputCounterStorage, $this->htmlOutputCounter++);
    file_put_contents($this->htmlOutputFile, file_create_url('sites/simpletest/browser_output/' . $html_output_filename) . "\n", FILE_APPEND);
  }

  /**
   * Returns headers in HTML output format.
   *
   * @return string
   *   HTML output headers.
   */
  protected function getHtmlOutputHeaders() {
    $headers = array_map(function($headers) {
      if (is_array($headers)) {
        return implode(';', array_map('trim', $headers));
      }
      else {
        return $headers;
      }
    }, $this->getSession()->getResponseHeaders());
    return '<hr />Headers: <pre>' . Html::escape(var_export($headers, TRUE)) . '</pre>';
  }

  /**
   * Translates a CSS expression to its XPath equivalent.
   *
   * The search is relative to the root element (HTML tag normally) of the page.
   *
   * @param string $selector
   *   CSS selector to use in the search.
   * @param bool $html
   *   (optional) Enables HTML support. Disable it for XML documents.
   * @param string $prefix
   *   (optional) The prefix for the XPath expression.
   *
   * @return string
   *   The equivalent XPath of a CSS expression.
   */
  protected function cssSelectToXpath($selector, $html = TRUE, $prefix = 'descendant-or-self::') {
    return (new CssSelectorConverter($html))->toXPath($selector, $prefix);
  }

  /**
   * Searches elements using a CSS selector in the raw content.
   *
   * The search is relative to the root element (HTML tag normally) of the page.
   *
   * @param string $selector
   *   CSS selector to use in the search.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The list of elements on the page that match the selector.
   */
  protected function cssSelect($selector) {
    return $this->getSession()->getPage()->findAll('css', $selector);
  }

  /**
   * Follows a link by complete name.
   *
   * Will click the first link found with this link text.
   *
   * If the link is discovered and clicked, the test passes. Fail otherwise.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags.
   * @param int $index
   *   (optional) The index number for cases where multiple links have the same
   *   text. Defaults to 0.
   */
  protected function clickLink($label, $index = 0) {
    $label = (string) $label;
    $links = $this->getSession()->getPage()->findAll('named', ['link', $label]);
    $links[$index]->click();
  }

  /**
   * Retrieves the plain-text content from the current page.
   */
  protected function getTextContent() {
    return $this->getSession()->getPage()->getText();
  }

  /**
   * Performs an xpath search on the contents of the internal browser.
   *
   * The search is relative to the root element (HTML tag normally) of the page.
   *
   * @param string $xpath
   *   The xpath string to use in the search.
   * @param array $arguments
   *   An array of arguments with keys in the form ':name' matching the
   *   placeholders in the query. The values may be either strings or numeric
   *   values.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The list of elements matching the xpath expression.
   */
  protected function xpath($xpath, array $arguments = []) {
    $xpath = $this->assertSession()->buildXPathQuery($xpath, $arguments);
    return $this->getSession()->getPage()->findAll('xpath', $xpath);
  }

  /**
   * Configuration accessor for tests. Returns non-overridden configuration.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object with original configuration data.
   */
  protected function config($name) {
    return $this->container->get('config.factory')->getEditable($name);
  }

  /**
   * Gets the value of an HTTP response header.
   *
   * If multiple requests were required to retrieve the page, only the headers
   * from the last request will be checked by default.
   *
   * @param string $name
   *   The name of the header to retrieve. Names are case-insensitive (see RFC
   *   2616 section 4.2).
   *
   * @return string|null
   *   The HTTP header value or NULL if not found.
   */
  protected function drupalGetHeader($name) {
    return $this->getSession()->getResponseHeader($name);
  }

  /**
   * Get the current URL from the browser.
   *
   * @return string
   *   The current URL.
   */
  protected function getUrl() {
    return $this->getSession()->getCurrentUrl();
  }

  /**
   * Gets the JavaScript drupalSettings variable for the currently-loaded page.
   *
   * @return array
   *   The JSON decoded drupalSettings value from the current page.
   */
  protected function getDrupalSettings() {
    $html = $this->getSession()->getPage()->getHtml();
    if (preg_match('@<script type="application/json" data-drupal-selector="drupal-settings-json">([^<]*)</script>@', $html, $matches)) {
      return Json::decode($matches[1]);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function assertEquals($expected, $actual, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = FALSE, $ignoreCase = FALSE) {
    // Cast objects implementing MarkupInterface to string instead of
    // relying on PHP casting them to string depending on what they are being
    // comparing with.
    $expected = static::castSafeStrings($expected);
    $actual = static::castSafeStrings($actual);
    parent::assertEquals($expected, $actual, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
  }

  /**
   * Changes parameters in the services.yml file.
   *
   * @param string $name
   *   The name of the parameter.
   * @param mixed $value
   *   The value of the parameter.
   */
  protected function setContainerParameter($name, $value) {
    $filename = $this->siteDirectory . '/services.yml';
    chmod($filename, 0666);

    $services = Yaml::decode(file_get_contents($filename));
    $services['parameters'][$name] = $value;
    file_put_contents($filename, Yaml::encode($services));

    // Ensure that the cache is deleted for the yaml file loader.
    $file_cache = FileCacheFactory::get('container_yaml_loader');
    $file_cache->delete($filename);
  }

  /**
   * Gets the config schema exclusions for this test.
   *
   * @return string[]
   *   An array of config object names that are excluded from schema checking.
   */
  protected function getConfigSchemaExclusions() {
    $class = get_class($this);
    $exceptions = [];
    while ($class) {
      if (property_exists($class, 'configSchemaCheckerExclusions')) {
        $exceptions = array_merge($exceptions, $class::$configSchemaCheckerExclusions);
      }
      $class = get_parent_class($class);
    }
    // Filter out any duplicates.
    return array_unique($exceptions);
  }

}
