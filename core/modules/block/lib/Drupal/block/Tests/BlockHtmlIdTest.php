<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockHtmlIdTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests block HTML ID validity.
 */
class BlockHtmlIdTest extends WebTestBase {

  /**
   * An administrative user to configure the test environment.
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_test');

  public static function getInfo() {
    return array(
      'name' => 'Block HTML ID',
      'description' => 'Tests block HTML ID validity.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp();

    // Create an admin user, log in and enable test blocks.
    $this->adminUser = $this->drupalCreateUser(array('administer blocks', 'access administration pages'));
    $this->drupalLogin($this->adminUser);

    // Make sure the block has some content so it will appear.
    $current_content = $this->randomName();
    state()->set('block_test.content', $current_content);

    // Enable our test block.
    $this->drupalPlaceBlock('test_html_id', array('machine_name' => 'test_id_block'));
  }

  /**
   * Tests for a valid HTML ID for a block.
   */
  function testHtmlId() {
    $this->drupalGet('');
    $this->assertRaw('id="block-test-id-block"', 'HTML ID for test block is valid.');
  }

}
