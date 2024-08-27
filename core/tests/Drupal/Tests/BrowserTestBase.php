<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Element\Element;
use Behat\Mink\Mink;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Session;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Database;
use Drupal\Core\Test\FunctionalTestSetupTrait;
use Drupal\Core\Test\TestSetupTrait;
use Drupal\Core\Utility\Error;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\Traits\PhpUnitWarnings;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\TestTools\Comparator\MarkupInterfaceComparator;
use Drupal\TestTools\TestVarDumper;
use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Provides a test case for functional Drupal tests.
 *
 * Tests extending BrowserTestBase must exist in the
 * Drupal\Tests\your_module\Functional namespace and live in the
 * modules/your_module/tests/src/Functional directory.
 *
 * Tests extending this base class should only translate text when testing
 * translation functionality. For example, avoid wrapping test text with t()
 * or TranslatableMarkup().
 *
 * Using Symfony's dump() function in functional test test code will produce
 * output on the command line; using dump() in site code will produce output in
 * the requested web page, which can then be inspected in the HTML output from
 * the test.
 *
 * @ingroup testing
 */
abstract class BrowserTestBase extends TestCase {

  use FunctionalTestSetupTrait;
  use UiHelperTrait {
    FunctionalTestSetupTrait::refreshVariables insteadof UiHelperTrait;
  }
  use TestSetupTrait;
  use BlockCreationTrait {
    placeBlock as drupalPlaceBlock;
  }
  use RandomGeneratorTrait;
  use NodeCreationTrait {
    getNodeByTitle as drupalGetNodeByTitle;
    createNode as drupalCreateNode;
  }
  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }
  use ConfigTestTrait;
  use TestRequirementsTrait;
  use UserCreationTrait {
    createRole as drupalCreateRole;
    createUser as drupalCreateUser;
  }
  use XdebugRequestTrait;
  use PhpUnitWarnings;
  use PhpUnitCompatibilityTrait;
  use ExpectDeprecationTrait;
  use ExtensionListTestTrait;

  /**
   * Time limit in seconds for the test.
   *
   * @var int
   */
  protected $timeLimit = 500;

  /**
   * The translation file directory for the test environment.
   *
   * This is set in BrowserTestBase::prepareEnvironment().
   *
   * @var string
   */
  protected $translationFilesDirectory;

  /**
   * The config importer that can be used in a test.
   *
   * @var \Drupal\Core\Config\ConfigImporter
   */
  protected $configImporter;

  /**
   * Modules to install.
   *
   * The test runner will merge the $modules lists from this class, the class
   * it extends, and so on up the class hierarchy. It is not necessary to
   * include modules in your list that a parent class has already declared.
   *
   * @var string[]
   *
   * @see \Drupal\Tests\BrowserTestBase::installDrupal()
   */
  protected static $modules = [];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * The theme to install as the default for testing.
   *
   * Defaults to the install profile's default theme, if it specifies any.
   *
   * @var string
   */
  protected $defaultTheme;

  /**
   * An array of custom translations suitable for SettingsEditor::rewrite().
   *
   * @var array
   *
   * @see \Drupal\Core\Site\SettingsEditor::rewrite()
   */
  protected $customTranslations;

  /**
   * Mink class for the default driver to use.
   *
   * Should be a fully-qualified class name that implements
   * Behat\Mink\Driver\DriverInterface.
   *
   * Value can be overridden using the environment variable MINK_DRIVER_CLASS.
   *
   * @var string
   */
  protected $minkDefaultDriverClass = BrowserKitDriver::class;

  /**
   * Mink default driver params.
   *
   * If it's an array its contents are used as constructor params when default
   * Mink driver class is instantiated.
   *
   * Can be overridden using the environment variable MINK_DRIVER_ARGS. In this
   * case that variable should be a JSON array, for example:
   * '["firefox", null, "http://localhost:4444/wd/hub"]'.
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
   * The original container.
   *
   * Move this to \Drupal\Core\Test\FunctionalTestSetupTrait once TestBase no
   * longer provides the same value.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $originalContainer;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    VarDumper::setHandler(TestVarDumper::class . '::cliHandler');
  }

  /**
   * Initializes Mink sessions.
   */
  protected function initMink() {
    $driver = $this->getDefaultDriverInstance();

    if ($driver instanceof BrowserKitDriver) {
      // Turn off curl timeout. Having a timeout is not a problem in a normal
      // test running, but it is a problem when debugging. Also, disable SSL
      // peer verification so that testing under HTTPS always works.
      /** @var \GuzzleHttp\Client $client */
      $client = $this->container->get('http_client_factory')->fromOptions([
        'timeout' => NULL,
        'verify' => FALSE,
      ]);

      // Inject a Guzzle middleware to generate debug output for every request
      // performed in the test.
      $handler_stack = $client->getConfig('handler');
      $handler_stack->push($this->getResponseLogHandler());

      $driver->getClient()->setClient($client);
    }

    $selectors_handler = new SelectorsHandler([
      'hidden_field_selector' => new HiddenFieldSelector(),
    ]);
    $session = new Session($driver, $selectors_handler);
    $this->mink = new Mink();
    $this->mink->registerSession('default', $session);
    $this->mink->setDefaultSessionName('default');
    $this->registerSessions();

    $this->initFrontPage();

    // Copies cookies from the current environment, for example, XDEBUG_SESSION
    // in order to support Xdebug.
    // @see BrowserTestBase::initFrontPage()
    $cookies = $this->extractCookiesFromRequest(\Drupal::request());
    foreach ($cookies as $cookie_name => $values) {
      foreach ($values as $value) {
        $session->setCookie($cookie_name, $value);
      }
    }

    return $session;
  }

  /**
   * Visits the front page when initializing Mink.
   *
   * According to the W3C WebDriver specification a cookie can only be set if
   * the cookie domain is equal to the domain of the active document. When the
   * browser starts up the active document is not our domain but 'about:blank'
   * or similar. To be able to set our User-Agent and Xdebug cookies at the
   * start of the test we now do a request to the front page so the active
   * document matches the domain.
   *
   * @see https://w3c.github.io/webdriver/webdriver-spec.html#add-cookie
   * @see https://www.w3.org/Bugs/Public/show_bug.cgi?id=20975
   */
  protected function initFrontPage() {
    $session = $this->getSession();
    $session->visit($this->baseUrl);
  }

  /**
   * Gets an instance of the default Mink driver.
   *
   * @return \Behat\Mink\Driver\DriverInterface
   *   Instance of default Mink driver.
   *
   * @throws \InvalidArgumentException
   *   When provided default Mink driver class can't be instantiated.
   */
  protected function getDefaultDriverInstance() {
    // Get default driver params from environment if available.
    if ($arg_json = $this->getMinkDriverArgs()) {
      $this->minkDefaultDriverArgs = json_decode($arg_json, TRUE);
    }

    // Get and check default driver class from environment if available.
    if ($minkDriverClass = getenv('MINK_DRIVER_CLASS')) {
      if (class_exists($minkDriverClass)) {
        $this->minkDefaultDriverClass = $minkDriverClass;
      }
      else {
        throw new \InvalidArgumentException("Can't instantiate provided $minkDriverClass class by environment as default driver class.");
      }
    }

    if ($this->minkDefaultDriverClass === BrowserKitDriver::class) {
      $driver = new $this->minkDefaultDriverClass(new DrupalTestBrowser());
    }
    elseif (is_array($this->minkDefaultDriverArgs)) {
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
   * Gets the Mink driver args from an environment variable.
   *
   * The environment variable can be overridden in a derived class so it is
   * possible to use a different value for a subset of tests, e.g. the
   * JavaScript tests.
   *
   * @return string|false
   *   The JSON-encoded argument string. False if it is not set.
   */
  protected function getMinkDriverArgs() {
    return getenv('MINK_DRIVER_ARGS');
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
  protected function setUp(): void {
    parent::setUp();

    $this->setUpAppRoot();

    // Allow tests to compare MarkupInterface objects via assertEquals().
    $this->registerComparator(new MarkupInterfaceComparator());

    $this->setupBaseUrl();

    // Install Drupal test site.
    $this->prepareEnvironment();
    $this->installDrupal();

    // Setup Mink.
    $this->initMink();

    // Set up the browser test output file.
    $this->initBrowserOutputFile();

    // Ensure that the test is not marked as risky because of no assertions. In
    // PHPUnit 6 tests that only make assertions using $this->assertSession()
    // can be marked as risky.
    $this->addToAssertionCount(1);
  }

  /**
   * {@inheritdoc}
   */
  public function __get(string $name) {
    if ($name === 'randomGenerator') {
      @trigger_error('Accessing the randomGenerator property is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use getRandomGenerator() instead. See https://www.drupal.org/node/3358445', E_USER_DEPRECATED);

      return $this->getRandomGenerator();
    }
  }

  /**
   * Sets up the root application path.
   */
  protected function setUpAppRoot(): void {
    if ($this->root === NULL) {
      $this->root = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)), 2);
    }
  }

  /**
   * Ensures test files are deletable.
   *
   * Some tests chmod generated files to be read only. During
   * BrowserTestBase::cleanupEnvironment() and other cleanup operations,
   * these files need to get deleted too.
   *
   * @param string $path
   *   The file path.
   *
   * @see \Drupal\Core\File\FileSystemInterface::deleteRecursive()
   */
  public static function filePreDeleteCallback($path) {
    // When the webserver runs with the same system user as phpunit, we can
    // make read-only files writable again. If not, chmod will fail while the
    // file deletion still works if file permissions have been configured
    // correctly. Thus, we ignore any problems while running chmod.
    @chmod($path, 0700);
  }

  /**
   * Clean up the test environment.
   */
  protected function cleanupEnvironment() {
    // Remove all prefixed tables.
    $original_connection_info = Database::getConnectionInfo('simpletest_original_default');
    $original_prefix = $original_connection_info['default']['prefix'];
    $test_connection_info = Database::getConnectionInfo('default');
    $test_prefix = $test_connection_info['default']['prefix'];
    if ($original_prefix != $test_prefix) {
      $tables = Database::getConnection()->schema()->findTables('%');
      foreach ($tables as $table) {
        if (Database::getConnection()->schema()->dropTable($table)) {
          unset($tables[$table]);
        }
      }
    }

    // Delete test site directory.
    \Drupal::service('file_system')->deleteRecursive($this->siteDirectory, [$this, 'filePreDeleteCallback']);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();

    if ($this->container) {
      // Cleanup mock session started in DrupalKernel::preHandle().
      try {
        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $this->container->get('request_stack')->getSession();
        $session->clear();
        $session->save();
      }
      catch (SessionNotFoundException) {
        @trigger_error('Pushing requests without a session onto the request_stack is deprecated in drupal:10.3.0 and an error will be thrown from drupal:11.0.0. See https://www.drupal.org/node/3337193', E_USER_DEPRECATED);
      }
    }

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
   * Get session cookies from current session.
   *
   * @return \GuzzleHttp\Cookie\CookieJar
   *   A cookie jar with the current session.
   */
  protected function getSessionCookies() {
    $domain = parse_url($this->getUrl(), PHP_URL_HOST);
    $session_id = $this->getSession()->getCookie($this->getSessionName());
    $cookies = CookieJar::fromArray([$this->getSessionName() => $session_id], $domain);

    return $cookies;
  }

  /**
   * Obtain the HTTP client for the system under test.
   *
   * Use this method for arbitrary HTTP requests to the site under test. For
   * most tests, you should not get the HTTP client and instead use navigation
   * methods such as drupalGet() and clickLink() in order to benefit from
   * assertions.
   *
   * Subclasses which substitute a different Mink driver should override this
   * method and provide a Guzzle client if the Mink driver provides one.
   *
   * @return \GuzzleHttp\ClientInterface
   *   The client with BrowserTestBase configuration.
   *
   * @throws \RuntimeException
   *   If the Mink driver does not support a Guzzle HTTP client, throw an
   *   exception.
   */
  protected function getHttpClient() {
    /** @var \Behat\Mink\Driver\DriverInterface $mink_driver */
    $mink_driver = $this->getSession()->getDriver();
    if ($this->isTestUsingGuzzleClient()) {
      return $mink_driver->getClient()->getClient();
    }
    throw new \RuntimeException('The Mink client type ' . get_class($mink_driver) . ' does not support getHttpClient().');
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
  protected function getOptions($select, ?Element $container = NULL) {
    if (is_string($select)) {
      $select = $this->assertSession()->selectExists($select, $container);
    }
    $options = [];
    /** @var \Behat\Mink\Element\NodeElement $option */
    foreach ($select->findAll('xpath', '//option') as $option) {
      $label = $option->getText();
      $value = $option->getAttribute('value') ?: $label;
      $options[$value] = $label;
    }
    return $options;
  }

  /**
   * Installs Drupal into the test site.
   */
  public function installDrupal() {
    $this->initUserSession();
    $this->prepareSettings();
    $this->doInstall();
    $this->initSettings();
    $this->container = $container = $this->initKernel(\Drupal::request());
    $this->initConfig($container);
    $this->installDefaultThemeFromClassProperty($container);
    $this->installModulesFromClassProperty($container);

    // Clear the static cache so that subsequent cache invalidations will work
    // as expected.
    $this->container->get('cache_tags.invalidator')->resetChecksums();

    // Explicitly call register() again on the container registered in \Drupal.
    // @todo This should already be called through
    //   DrupalKernel::prepareLegacyRequest() -> DrupalKernel::boot() but that
    //   appears to be calling a different container.
    $this->container->get('stream_wrapper_manager')->register();
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
   * Gets the JavaScript drupalSettings variable for the currently-loaded page.
   *
   * @return array
   *   The JSON decoded drupalSettings value from the current page.
   */
  protected function getDrupalSettings() {
    $html = $this->getSession()->getPage()->getContent();
    if (preg_match('@<script type="application/json" data-drupal-selector="drupal-settings-json">([^<]*)</script>@', $html, $matches)) {
      $settings = Json::decode($matches[1]);
      if (isset($settings['ajaxPageState']['libraries'])) {
        $settings['ajaxPageState']['libraries'] = UrlHelper::uncompressQueryParameter($settings['ajaxPageState']['libraries']);
      }
      return $settings;
    }
    return [];
  }

  /**
   * Retrieves the current calling line in the class under test.
   *
   * @return array
   *   An associative array with keys 'file', 'line' and 'function'.
   */
  protected function getTestMethodCaller() {
    $backtrace = debug_backtrace();
    // Find the test class that has the test method.
    while ($caller = Error::getLastCaller($backtrace)) {
      // If we match PHPUnit's TestCase::runTest, then the previously processed
      // caller entry is where our test method sits.
      if (isset($last_caller) && isset($caller['function']) && $caller['function'] === 'PHPUnit\Framework\TestCase->runTest()') {
        // Return the last caller since that has to be the test class.
        $caller = $last_caller;
        break;
      }

      // If the test method is implemented by a test class's parent then the
      // class name of $this will not be part of the backtrace.
      // In that case we process the backtrace until the caller is not a
      // subclass of $this and return the previous caller.
      if (isset($last_caller) && (!isset($caller['class']) || !is_subclass_of($this, $caller['class']))) {
        // Return the last caller since that has to be the test class.
        $caller = $last_caller;
        break;
      }

      if (isset($caller['class']) && $caller['class'] === static::class) {
        break;
      }

      // Otherwise we have not reached our test class yet: save the last caller
      // and remove an element from to backtrace to process the next call.
      $last_caller = $caller;
      array_shift($backtrace);
    }

    return $caller;
  }

  /**
   * Transforms a nested array into a flat array suitable for submitForm().
   *
   * @param array $values
   *   A multi-dimensional form values array to convert.
   *
   * @return array
   *   The flattened $edit array suitable for BrowserTestBase::submitForm().
   */
  protected function translatePostValues(array $values) {
    $edit = [];
    // The easiest and most straightforward way to translate values suitable for
    // BrowserTestBase::submitForm() is to actually build the POST data
    // string and convert the resulting key/value pairs back into a flat array.
    $query = http_build_query($values);
    foreach (explode('&', $query) as $item) {
      [$key, $value] = explode('=', $item);
      $edit[urldecode($key)] = urldecode($value);
    }
    return $edit;
  }

}
