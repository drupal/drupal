<?php

namespace Drupal\Tests;

use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Element\Element;
use Behat\Mink\Mink;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Session;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Database;
use Drupal\Core\Test\FunctionalTestSetupTrait;
use Drupal\Core\Test\TestSetupTrait;
use Drupal\Core\Utility\Error;
use Drupal\FunctionalTests\AssertLegacyTrait;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Provides a test case for functional Drupal tests.
 *
 * Tests extending BrowserTestBase must exist in the
 * Drupal\Tests\yourmodule\Functional namespace and live in the
 * modules/yourmodule/tests/src/Functional directory.
 *
 * Tests extending this base class should only translate text when testing
 * translation functionality. For example, avoid wrapping test text with t()
 * or TranslatableMarkup().
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
  use AssertLegacyTrait;
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
  use PhpunitCompatibilityTrait;

  /**
   * The database prefix of this test run.
   *
   * @var string
   */
  protected $databasePrefix;

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
  protected static $modules = [];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * An array of custom translations suitable for drupal_rewrite_settings().
   *
   * @var array
   */
  protected $customTranslations;

  /*
   * Mink class for the default driver to use.
   *
   * Should be a fully-qualified class name that implements
   * Behat\Mink\Driver\DriverInterface.
   *
   * Value can be overridden using the environment variable MINK_DRIVER_CLASS.
   *
   * @var string
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
   * The app root.
   *
   * @var string
   */
  protected $root;

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
  public function __construct($name = NULL, array $data = [], $dataName = '') {
    parent::__construct($name, $data, $dataName);

    $this->root = dirname(dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__))));
  }

  /**
   * Initializes Mink sessions.
   */
  protected function initMink() {
    $driver = $this->getDefaultDriverInstance();

    if ($driver instanceof GoutteDriver) {
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
   * @return Behat\Mink\Driver\DriverInterface
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
   * Get the Mink driver args from an environment variable, if it is set. Can
   * be overridden in a derived class so it is possible to use a different
   * value for a subset of tests, e.g. the JavaScript tests.
   *
   *  @return string|false
   *   The JSON-encoded argument string. False if it is not set.
   */
  protected function getMinkDriverArgs() {
    return getenv('MINK_DRIVER_ARGS');
  }

  /**
   * Provides a Guzzle middleware handler to log every response received.
   *
   * @return callable
   *   The callable handler that will do the logging.
   */
  protected function getResponseLogHandler() {
    return function (callable $handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        return $handler($request, $options)
          ->then(function (ResponseInterface $response) use ($request) {
            if ($this->htmlOutputEnabled) {

              $caller = $this->getTestMethodCaller();
              $html_output = 'Called from ' . $caller['function'] . ' line ' . $caller['line'];
              $html_output .= '<hr />' . $request->getMethod() . ' request to: ' . $request->getUri();

              // On redirect responses (status code starting with '3') we need
              // to remove the meta tag that would do a browser refresh. We
              // don't want to redirect developers away when they look at the
              // debug output file in their browser.
              $body = $response->getBody();
              $status_code = (string) $response->getStatusCode();
              if ($status_code[0] === '3') {
                $body = preg_replace('#<meta http-equiv="refresh" content=.+/>#', '', $body, 1);
              }
              $html_output .= '<hr />' . $body;
              $html_output .= $this->formatHtmlOutputHeaders($response->getHeaders());

              $this->htmlOutput($html_output);
            }
            return $response;
          });
      };
    };
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
    parent::setUp();

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
    \Drupal::service('file_system')->deleteRecursive($this->siteDirectory, [$this, 'filePreDeleteCallback']);
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
    /* @var $mink_driver \Behat\Mink\Driver\DriverInterface */
    $mink_driver = $this->getSession()->getDriver();
    if ($mink_driver instanceof GoutteDriver) {
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
    $this->initUserSession();
    $this->prepareSettings();
    $this->doInstall();
    $this->initSettings();
    $container = $this->initKernel(\Drupal::request());
    $this->initConfig($container);
    $this->installModulesFromClassProperty($container);
    $this->rebuildAll();
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
   * Returns all response headers.
   *
   * @return array
   *   The HTTP headers values.
   *
   * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0.
   *   Use $this->getSession()->getResponseHeaders() instead.
   *
   * @see https://www.drupal.org/node/3067207
   */
  protected function drupalGetHeaders() {
    @trigger_error('Drupal\Tests\BrowserTestBase::drupalGetHeaders() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use $this->getSession()->getResponseHeaders() instead. See https://www.drupal.org/node/3067207', E_USER_DEPRECATED);
    return $this->getSession()->getResponseHeaders();
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
   * Gets the JavaScript drupalSettings variable for the currently-loaded page.
   *
   * @return array
   *   The JSON decoded drupalSettings value from the current page.
   */
  protected function getDrupalSettings() {
    $html = $this->getSession()->getPage()->getContent();
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
   * Retrieves the current calling line in the class under test.
   *
   * @return array
   *   An associative array with keys 'file', 'line' and 'function'.
   */
  protected function getTestMethodCaller() {
    $backtrace = debug_backtrace();
    // Find the test class that has the test method.
    while ($caller = Error::getLastCaller($backtrace)) {
      if (isset($caller['class']) && $caller['class'] === get_class($this)) {
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
      // Otherwise we have not reached our test class yet: save the last caller
      // and remove an element from to backtrace to process the next call.
      $last_caller = $caller;
      array_shift($backtrace);
    }

    return $caller;
  }

  /**
   * Transforms a nested array into a flat array suitable for drupalPostForm().
   *
   * @param array $values
   *   A multi-dimensional form values array to convert.
   *
   * @return array
   *   The flattened $edit array suitable for BrowserTestBase::drupalPostForm().
   */
  protected function translatePostValues(array $values) {
    $edit = [];
    // The easiest and most straightforward way to translate values suitable for
    // BrowserTestBase::drupalPostForm() is to actually build the POST data
    // string and convert the resulting key/value pairs back into a flat array.
    $query = http_build_query($values);
    foreach (explode('&', $query) as $item) {
      list($key, $value) = explode('=', $item);
      $edit[urldecode($key)] = urldecode($value);
    }
    return $edit;
  }

}
