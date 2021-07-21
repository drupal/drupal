<?php

namespace Drupal\Core\Test\HttpClientMiddleware;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Test\TestDatabase;
use Drupal\Core\Utility\Error;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Overrides the User-Agent HTTP header for outbound HTTP requests.
 */
class TestHttpClientMiddleware {

  /**
   * Test last prefix.
   *
   * @var string
   */
  protected static $testLastPrefix;

  /**
   * Test key.
   *
   * @var string
   */
  protected static $testKey;

  /**
   * {@inheritdoc}
   *
   * HTTP middleware that replaces the user agent for simpletest requests.
   */
  public function __invoke() {
    // If the database prefix is being used by SimpleTest to run the tests in a copied
    // database then set the user-agent header to the database prefix so that any
    // calls to other Drupal pages will run the SimpleTest prefixed database. The
    // user-agent is used to ensure that multiple testing sessions running at the
    // same time won't interfere with each other as they would if the database
    // prefix were stored statically in a file or database variable.
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        if ($test_prefix = drupal_valid_test_ua()) {
          $request = $request->withHeader('User-Agent', static::generate($test_prefix));
        }
        return $handler($request, $options)
          ->then(function (ResponseInterface $response) use ($request, $test_prefix) {
            if (!$test_prefix) {
              return $response;
            }
            $headers = $response->getHeaders();
            foreach ($headers as $header_name => $header_values) {
              if (preg_match('/^X-Drupal-Assertion-[0-9]+$/', $header_name, $matches)) {
                foreach ($header_values as $header_value) {
                  $parameters = unserialize(urldecode($header_value));
                  if (count($parameters) === 3) {
                    if ($parameters[1] === 'User deprecated function') {
                      // Fire the same deprecation message to allow it to be
                      // collected by
                      // \Symfony\Bridge\PhpUnit\DeprecationErrorHandler::collectDeprecations().
                      @trigger_error((string) $parameters[0], E_USER_DEPRECATED);
                    }
                    else {
                      throw new \Exception($parameters[1] . ': ' . $parameters[0] . "\n" . Error::formatBacktrace([$parameters[2]]));
                    }
                  }
                  else {
                    throw new \Exception('Error thrown with the wrong amount of parameters.');
                  }
                }
              }
            }
            return $response;
          });
      };
    };
  }

  /**
   * Generates a user agent string with a HMAC and timestamp for testing.
   *
   * @param string $prefix
   *   The testing database prefix.
   *
   * @return string
   *   User agent string.
   */
  public static function generate(string $prefix): string {
    if (!isset(static::$testKey) || static::$testLastPrefix !== $prefix) {
      static::$testLastPrefix = $prefix;
      $test_db = new TestDatabase($prefix);
      $key_file = DRUPAL_ROOT . '/' . $test_db->getTestSitePath() . '/.htkey';
      // When issuing an outbound HTTP client request from within an inbound
      // test request, then the outbound request has to use the same User-Agent
      // header as the inbound request. A newly generated private key for the
      // same test prefix would invalidate all subsequent inbound requests.
      /* @see \Drupal\Core\Test\HttpClientMiddleware\TestHttpClientMiddleware::__invoke() */
      if (defined('DRUPAL_TEST_IN_CHILD_SITE') && DRUPAL_TEST_IN_CHILD_SITE && $parent_prefix = drupal_valid_test_ua()) {
        if ($parent_prefix !== $prefix) {
          throw new \RuntimeException("Malformed User-Agent: Expected '$parent_prefix' but got '$prefix'.");
        }
        // If the file is not readable, a PHP warning is expected in this case.
        $private_key = file_get_contents($key_file);
      }
      else {
        // Generate and save a new hash salt for a test run.
        // Consumed by drupal_valid_test_ua() before settings.php is loaded.
        $private_key = Crypt::randomBytesBase64(55);
        file_put_contents($key_file, $private_key);
      }
      // The file properties add more entropy not easily accessible to others.
      static::$testKey = $private_key . filectime(__FILE__) . fileinode(__FILE__);
    }
    // Generate a moderately secure HMAC based on the database credentials.
    $salt = uniqid('', TRUE);
    $check_string = $prefix . ':' . time() . ':' . $salt;
    return 'simple' . $check_string . ':' . Crypt::hmacBase64($check_string, static::$testKey);
  }

}
