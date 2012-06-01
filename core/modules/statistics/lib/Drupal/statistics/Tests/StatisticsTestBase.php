<?php

/**
 * @file
 * Definition of Drupal\statistics\Tests\StatisticsTestBase.
 */

namespace Drupal\statistics\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Defines a base class for testing the Statistics module.
 */
class StatisticsTestBase extends WebTestBase {

  function setUp() {
    parent::setUp(array('node', 'block', 'statistics'));

    // Create Basic page node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    }

    // Create user.
    $this->blocking_user = $this->drupalCreateUser(array(
      'access administration pages',
      'access site reports',
      'access statistics',
      'block IP addresses',
      'administer blocks',
      'administer statistics',
      'administer users',
    ));
    $this->drupalLogin($this->blocking_user);

    // Enable access logging.
    variable_set('statistics_enable_access_log', 1);
    variable_set('statistics_count_content_views', 1);

    // Insert dummy access by anonymous user into access log.
    db_insert('accesslog')
      ->fields(array(
        'title' => 'test',
        'path' => 'node/1',
        'url' => 'http://example.com',
        'hostname' => '192.168.1.1',
        'uid' => 0,
        'sid' => 10,
        'timer' => 10,
        'timestamp' => REQUEST_TIME,
      ))
      ->execute();
  }
}
