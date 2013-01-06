<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockUiTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the block configuration UI.
 */
class BlockUiTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  protected $regions;

  /**
   * An administrative user to configure the test environment.
   */
  protected $adminUser;

  public static function getInfo() {
    return array(
      'name' => 'Block UI',
      'description' => 'Checks that the block configuration UI stores data correctly.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp();
    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer blocks',
      'access administration pages',
    ));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test block visibility.
   */
  function testBlockVisibility() {
  }
}
