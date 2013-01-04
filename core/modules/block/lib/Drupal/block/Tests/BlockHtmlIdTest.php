<?php

/**
 * @file
 * Definition of Drupal\block\Tests\BlockHtmlIdTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test block HTML id validity.
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
      'name' => 'Block HTML id',
      'description' => 'Test block HTML id validity.',
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
    $default_theme = variable_get('theme_default', 'stark');
    $block = array();
    $block['machine_name'] = 'test_id_block';
    $block['region'] = 'sidebar_first';
    $this->drupalPost('admin/structure/block/manage/test_html_id' . '/' . $default_theme, array('machine_name' => $block['machine_name'], 'region' => $block['region']), t('Save block'));
  }

  /**
   * Tests for a valid HTML ID for a block.
   */
  function testHtmlId() {
    $this->drupalGet('');
    $this->assertRaw('id="block-test-id-block"', 'HTML ID for test block is valid.');
  }
}
