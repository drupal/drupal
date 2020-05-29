<?php

namespace Drupal\Tests\Core\Security;

use Drupal\Core\Security\RequestSanitizer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests RequestSanitizer class.
 *
 * @coversDefaultClass \Drupal\Core\Security\RequestSanitizer
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @group Security
 */
class RequestSanitizerTest extends UnitTestCase {

  /**
   * Log of errors triggered during sanitization.
   *
   * @var array
   */
  protected $errors;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->errors = [];
    set_error_handler([$this, "errorHandler"]);
  }

  /**
   * Tests RequestSanitizer class.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to sanitize.
   * @param array $expected
   *   An array of expected request parameters after sanitization. The possible
   *   keys are 'cookies', 'query', 'request' which correspond to the parameter
   *   bags names on the request object. These values are also used to test the
   *   PHP globals post sanitization.
   * @param array|null $expected_errors
   *   An array of expected errors. If set to NULL then error logging is
   *   disabled.
   * @param array $whitelist
   *   An array of keys to whitelist and not sanitize.
   *
   * @dataProvider providerTestRequestSanitization
   */
  public function testRequestSanitization(Request $request, array $expected = [], array $expected_errors = NULL, array $whitelist = []) {
    // Set up globals.
    $_GET = $request->query->all();
    $_POST = $request->request->all();
    $_COOKIE = $request->cookies->all();
    $_REQUEST = array_merge($request->query->all(), $request->request->all());
    $request->server->set('QUERY_STRING', http_build_query($request->query->all()));
    $_SERVER['QUERY_STRING'] = $request->server->get('QUERY_STRING');

    $request = RequestSanitizer::sanitize($request, $whitelist, is_null($expected_errors) ? FALSE : TRUE);

    // Normalize the expected data.
    $expected += ['cookies' => [], 'query' => [], 'request' => []];
    $expected_query_string = http_build_query($expected['query']);

    // Test the request.
    $this->assertEquals($expected['cookies'], $request->cookies->all());
    $this->assertEquals($expected['query'], $request->query->all());
    $this->assertEquals($expected['request'], $request->request->all());
    $this->assertTrue($request->attributes->get(RequestSanitizer::SANITIZED));
    // The request object normalizes the request query string.
    $this->assertEquals(Request::normalizeQueryString($expected_query_string), $request->getQueryString());

    // Test PHP globals.
    $this->assertEquals($expected['cookies'], $_COOKIE);
    $this->assertEquals($expected['query'], $_GET);
    $this->assertEquals($expected['request'], $_POST);
    $expected_request = array_merge($expected['query'], $expected['request']);
    $this->assertEquals($expected_request, $_REQUEST);
    $this->assertEquals($expected_query_string, $_SERVER['QUERY_STRING']);

    // Ensure any expected errors have been triggered.
    if (!empty($expected_errors)) {
      foreach ($expected_errors as $expected_error) {
        $this->assertError($expected_error, E_USER_NOTICE);
      }
    }
    else {
      $this->assertEquals([], $this->errors);
    }
  }

  /**
   * Data provider for testRequestSanitization.
   *
   * @return array
   */
  public function providerTestRequestSanitization() {
    $tests = [];

    $request = new Request(['q' => 'index.php']);
    $tests['no sanitization GET'] = [$request, ['query' => ['q' => 'index.php']]];

    $request = new Request([], ['field' => 'value']);
    $tests['no sanitization POST'] = [$request, ['request' => ['field' => 'value']]];

    $request = new Request([], [], [], ['key' => 'value']);
    $tests['no sanitization COOKIE'] = [$request, ['cookies' => ['key' => 'value']]];

    $request = new Request(['q' => 'index.php'], ['field' => 'value'], [], ['key' => 'value']);
    $tests['no sanitization GET, POST, COOKIE'] = [$request, ['query' => ['q' => 'index.php'], 'request' => ['field' => 'value'], 'cookies' => ['key' => 'value']]];

    $request = new Request(['q' => 'index.php']);
    $tests['no sanitization GET log'] = [$request, ['query' => ['q' => 'index.php']], []];

    $request = new Request([], ['field' => 'value']);
    $tests['no sanitization POST log'] = [$request, ['request' => ['field' => 'value']], []];

    $request = new Request([], [], [], ['key' => 'value']);
    $tests['no sanitization COOKIE log'] = [$request, ['cookies' => ['key' => 'value']], []];

    $request = new Request(['#q' => 'index.php']);
    $tests['sanitization GET'] = [$request];

    $request = new Request([], ['#field' => 'value']);
    $tests['sanitization POST'] = [$request];

    $request = new Request([], [], [], ['#key' => 'value']);
    $tests['sanitization COOKIE'] = [$request];

    $request = new Request(['#q' => 'index.php'], ['#field' => 'value'], [], ['#key' => 'value']);
    $tests['sanitization GET, POST, COOKIE'] = [$request];

    $request = new Request(['#q' => 'index.php']);
    $tests['sanitization GET log'] = [$request, [], ['Potentially unsafe keys removed from query string parameters (GET): #q']];

    $request = new Request([], ['#field' => 'value']);
    $tests['sanitization POST log'] = [$request, [], ['Potentially unsafe keys removed from request body parameters (POST): #field']];

    $request = new Request([], [], [], ['#key' => 'value']);
    $tests['sanitization COOKIE log'] = [$request, [], ['Potentially unsafe keys removed from cookie parameters: #key']];

    $request = new Request(['#q' => 'index.php'], ['#field' => 'value'], [], ['#key' => 'value']);
    $tests['sanitization GET, POST, COOKIE log'] = [$request, [], ['Potentially unsafe keys removed from query string parameters (GET): #q', 'Potentially unsafe keys removed from request body parameters (POST): #field', 'Potentially unsafe keys removed from cookie parameters: #key']];

    $request = new Request(['q' => 'index.php', 'foo' => ['#bar' => 'foo']]);
    $tests['recursive sanitization log'] = [$request, ['query' => ['q' => 'index.php', 'foo' => []]], ['Potentially unsafe keys removed from query string parameters (GET): #bar']];

    $request = new Request(['q' => 'index.php', 'foo' => ['#bar' => 'foo']]);
    $tests['recursive no sanitization whitelist'] = [$request, ['query' => ['q' => 'index.php', 'foo' => ['#bar' => 'foo']]], [], ['#bar']];

    $request = new Request([], ['#field' => 'value']);
    $tests['no sanitization POST whitelist'] = [$request, ['request' => ['#field' => 'value']], [], ['#field']];

    $request = new Request(['q' => 'index.php', 'foo' => ['#bar' => 'foo', '#foo' => 'bar']]);
    $tests['recursive multiple sanitization log'] = [$request, ['query' => ['q' => 'index.php', 'foo' => []]], ['Potentially unsafe keys removed from query string parameters (GET): #bar, #foo']];

    $request = new Request(['#q' => 'index.php']);
    $request->attributes->set(RequestSanitizer::SANITIZED, TRUE);
    $tests['already sanitized request'] = [$request, ['query' => ['#q' => 'index.php']]];

    $request = new Request(['destination' => 'whatever?%23test=value']);
    $tests['destination removal GET'] = [$request];

    $request = new Request([], ['destination' => 'whatever?%23test=value']);
    $tests['destination removal POST'] = [$request];

    $request = new Request([], [], [], ['destination' => 'whatever?%23test=value']);
    $tests['destination removal COOKIE'] = [$request];

    $request = new Request(['destination' => 'whatever?%23test=value']);
    $tests['destination removal GET log'] = [$request, [], ['Potentially unsafe destination removed from query parameter bag because it contained the following keys: #test']];

    $request = new Request([], ['destination' => 'whatever?%23test=value']);
    $tests['destination removal POST log'] = [$request, [], ['Potentially unsafe destination removed from request parameter bag because it contained the following keys: #test']];

    $request = new Request([], [], [], ['destination' => 'whatever?%23test=value']);
    $tests['destination removal COOKIE log'] = [$request, [], ['Potentially unsafe destination removed from cookies parameter bag because it contained the following keys: #test']];

    $request = new Request(['destination' => 'whatever?q[%23test]=value']);
    $tests['destination removal subkey'] = [$request];

    $request = new Request(['destination' => 'whatever?q[%23test]=value']);
    $tests['destination whitelist'] = [$request, ['query' => ['destination' => 'whatever?q[%23test]=value']], [], ['#test']];

    $request = new Request(['destination' => "whatever?\x00bar=base&%23test=value"]);
    $tests['destination removal zero byte'] = [$request];

    $request = new Request(['destination' => 'whatever?q=value']);
    $tests['destination kept'] = [$request, ['query' => ['destination' => 'whatever?q=value']]];

    $request = new Request(['destination' => 'whatever']);
    $tests['destination no query'] = [$request, ['query' => ['destination' => 'whatever']]];

    return $tests;
  }

  /**
   * Tests acceptable destinations are not removed from GET requests.
   *
   * @param string $destination
   *   The destination string to test.
   *
   * @dataProvider providerTestAcceptableDestinations
   */
  public function testAcceptableDestinationGet($destination) {
    // Set up a GET request.
    $request = $this->createRequestForTesting(['destination' => $destination]);

    $request = RequestSanitizer::sanitize($request, [], TRUE);

    $this->assertSame($destination, $request->query->get('destination', NULL));
    $this->assertNull($request->request->get('destination', NULL));
    $this->assertSame($destination, $_GET['destination']);
    $this->assertSame($destination, $_REQUEST['destination']);
    $this->assertArrayNotHasKey('destination', $_POST);
    $this->assertEquals([], $this->errors);
  }

  /**
   * Tests unacceptable destinations are removed from GET requests.
   *
   * @param string $destination
   *   The destination string to test.
   *
   * @dataProvider providerTestSanitizedDestinations
   */
  public function testSanitizedDestinationGet($destination) {
    // Set up a GET request.
    $request = $this->createRequestForTesting(['destination' => $destination]);

    $request = RequestSanitizer::sanitize($request, [], TRUE);

    $this->assertNull($request->request->get('destination', NULL));
    $this->assertNull($request->query->get('destination', NULL));
    $this->assertArrayNotHasKey('destination', $_POST);
    $this->assertArrayNotHasKey('destination', $_REQUEST);
    $this->assertArrayNotHasKey('destination', $_GET);
    $this->assertError('Potentially unsafe destination removed from query parameter bag because it points to an external URL.', E_USER_NOTICE);
  }

  /**
   * Tests acceptable destinations are not removed from POST requests.
   *
   * @param string $destination
   *   The destination string to test.
   *
   * @dataProvider providerTestAcceptableDestinations
   */
  public function testAcceptableDestinationPost($destination) {
    // Set up a POST request.
    $request = $this->createRequestForTesting([], ['destination' => $destination]);

    $request = RequestSanitizer::sanitize($request, [], TRUE);

    $this->assertSame($destination, $request->request->get('destination', NULL));
    $this->assertNull($request->query->get('destination', NULL));
    $this->assertSame($destination, $_POST['destination']);
    $this->assertSame($destination, $_REQUEST['destination']);
    $this->assertArrayNotHasKey('destination', $_GET);
    $this->assertEquals([], $this->errors);
  }

  /**
   * Tests unacceptable destinations are removed from GET requests.
   *
   * @param string $destination
   *   The destination string to test.
   *
   * @dataProvider providerTestSanitizedDestinations
   */
  public function testSanitizedDestinationPost($destination) {
    // Set up a POST request.
    $request = $this->createRequestForTesting([], ['destination' => $destination]);

    $request = RequestSanitizer::sanitize($request, [], TRUE);

    $this->assertNull($request->request->get('destination', NULL));
    $this->assertNull($request->query->get('destination', NULL));
    $this->assertArrayNotHasKey('destination', $_POST);
    $this->assertArrayNotHasKey('destination', $_REQUEST);
    $this->assertArrayNotHasKey('destination', $_GET);
    $this->assertError('Potentially unsafe destination removed from request parameter bag because it points to an external URL.', E_USER_NOTICE);
  }

  /**
   * Creates a request and sets PHP globals for testing.
   *
   * @param array $query
   *   (optional) The GET parameters.
   * @param array $request
   *   (optional) The POST parameters.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request object.
   */
  protected function createRequestForTesting(array $query = [], array $request = []) {
    $request = new Request($query, $request);

    // Set up globals.
    $_GET = $request->query->all();
    $_POST = $request->request->all();
    $_COOKIE = $request->cookies->all();
    $_REQUEST = array_merge($request->query->all(), $request->request->all());
    $request->server->set('QUERY_STRING', http_build_query($request->query->all()));
    $_SERVER['QUERY_STRING'] = $request->server->get('QUERY_STRING');
    return $request;
  }

  /**
   * Data provider for testing acceptable destinations.
   */
  public function providerTestAcceptableDestinations() {
    $data = [];
    // Standard internal example node path is present in the 'destination'
    // parameter.
    $data[] = ['node'];
    // Internal path with one leading slash is allowed.
    $data[] = ['/example.com'];
    // Internal URL using a colon is allowed.
    $data[] = ['example:test'];
    // Javascript URL is allowed because it is treated as an internal URL.
    $data[] = ['javascript:alert(0)'];
    return $data;
  }

  /**
   * Data provider for testing sanitized destinations.
   */
  public function providerTestSanitizedDestinations() {
    $data = [];
    // External URL without scheme is not allowed.
    $data[] = ['//example.com/test'];
    // External URL is not allowed.
    $data[] = ['http://example.com'];
    return $data;
  }

  /**
   * Catches and logs errors to $this->errors.
   *
   * @param int $errno
   *   The severity level of the error.
   * @param string $errstr
   *   The error message.
   */
  public function errorHandler($errno, $errstr) {
    $this->errors[] = compact('errno', 'errstr');
  }

  /**
   * Asserts that the expected error has been logged.
   *
   * @param string $errstr
   *   The error message.
   * @param int $errno
   *   The severity level of the error.
   */
  protected function assertError($errstr, $errno) {
    foreach ($this->errors as $error) {
      if ($error['errstr'] === $errstr && $error['errno'] === $errno) {
        return;
      }
    }
    $this->fail("Error with level $errno and message '$errstr' not found in " . var_export($this->errors, TRUE));
  }

}
