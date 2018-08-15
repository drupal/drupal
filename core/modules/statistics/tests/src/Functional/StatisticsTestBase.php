<?php

namespace Drupal\Tests\statistics\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Defines a base class for testing the Statistics module.
 */
abstract class StatisticsTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'block', 'ban', 'statistics'];

  /**
   * User with permissions to ban IP's.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $blockingUser;

  protected function setUp() {
    parent::setUp();

    // Create Basic page node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    }

    // Create user.
    $this->blockingUser = $this->drupalCreateUser([
      'access administration pages',
      'access site reports',
      'ban IP addresses',
      'administer blocks',
      'administer statistics',
      'administer users',
    ]);
    $this->drupalLogin($this->blockingUser);

    // Enable logging.
    $this->config('statistics.settings')
      ->set('count_content_views', 1)
      ->save();
  }

}
