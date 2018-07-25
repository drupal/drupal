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

    // Normalise the expected data.
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
