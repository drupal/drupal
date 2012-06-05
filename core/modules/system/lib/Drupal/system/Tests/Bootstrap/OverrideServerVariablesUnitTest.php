<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Bootstrap\OverrideServerVariablesUnitTest.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests for overriding server variables via the API.
 */
class OverrideServerVariablesUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Overriding server variables',
      'description' => 'Test that drupal_override_server_variables() works correctly.',
      'group' => 'Bootstrap',
    );
  }

  /**
   * Test providing a direct URL to to drupal_override_server_variables().
   */
  function testDrupalOverrideServerVariablesProvidedURL() {
    $tests = array(
      'http://example.com' => array(
        'HTTP_HOST' => 'example.com',
        'SCRIPT_NAME' => isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : NULL,
      ),
      'http://example.com/index.php' => array(
        'HTTP_HOST' => 'example.com',
        'SCRIPT_NAME' => '/index.php',
      ),
      'http://example.com/subdirectory/index.php' => array(
        'HTTP_HOST' => 'example.com',
        'SCRIPT_NAME' => '/subdirectory/index.php',
      ),
    );
    foreach ($tests as $url => $expected_server_values) {
      // Remember the original value of $_SERVER, since the function call below
      // will modify it.
      $original_server = $_SERVER;
      // Call drupal_override_server_variables() and ensure that all expected
      // $_SERVER variables were modified correctly.
      drupal_override_server_variables(array('url' => $url));
      foreach ($expected_server_values as $key => $value) {
        $this->assertIdentical($_SERVER[$key], $value);
      }
      // Restore the original value of $_SERVER.
      $_SERVER = $original_server;
    }
  }
}
