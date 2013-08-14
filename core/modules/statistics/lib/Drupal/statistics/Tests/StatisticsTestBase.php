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
abstract class StatisticsTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'block', 'ban', 'statistics');

  function setUp() {
    parent::setUp();

    // Create Basic page node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    }

    // Create user.
    $this->blocking_user = $this->drupalCreateUser(array(
      'access administration pages',
      'access site reports',
      'ban IP addresses',
      'administer blocks',
      'administer statistics',
      'administer users',
    ));
    $this->drupalLogin($this->blocking_user);

    // Enable logging.
    \Drupal::config('statistics.settings')
      ->set('count_content_views', 1)
      ->save();
  }
}
