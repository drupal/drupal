<?php

/**
 * @file
 * Definition of Drupal\rest\test\DBLogTest.
 */

namespace Drupal\rest\Tests;

use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests the Watchdog resource to retrieve log messages.
 */
class DBLogTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('hal', 'rest', 'dblog');

  public static function getInfo() {
    return array(
      'name' => 'DB Log resource',
      'description' => 'Tests the watchdog database log resource.',
      'group' => 'REST',
    );
  }

  public function setUp() {
    parent::setUp();
    // Enable REST API for the watchdog resource.
    $this->enableService('dblog');
  }

  /**
   * Writes a log messages and retrieves it via the REST API.
   */
  public function testWatchdog() {
    // Write a log message to the DB.
    watchdog('rest_test', 'Test message');
    // Get the ID of the written message.
    $id = db_query_range("SELECT wid FROM {watchdog} WHERE type = :type ORDER BY wid DESC", 0, 1, array(':type' => 'rest_test'))
      ->fetchField();

    // Create a user account that has the required permissions to read
    // the watchdog resource via the REST API.
    $account = $this->drupalCreateUser(array('restful get dblog'));
    $this->drupalLogin($account);

    $response = $this->httpRequest("dblog/$id", 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(200);
    $this->assertHeader('content-type', $this->defaultMimeType);
    $log = drupal_json_decode($response);
    $this->assertEqual($log['wid'], $id, 'Log ID is correct.');
    $this->assertEqual($log['type'], 'rest_test', 'Type of log message is correct.');
    $this->assertEqual($log['message'], 'Test message', 'Log message text is correct.');

    // Request an unknown log entry.
    $response = $this->httpRequest("dblog/9999", 'GET', NULL, $this->defaultMimeType);
    $this->assertResponse(404);
    $decoded = drupal_json_decode($response);
    $this->assertEqual($decoded['error'], 'Log entry with ID 9999 was not found', 'Response message is correct.');
  }
}
