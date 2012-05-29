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

  public static function getInfo() {
    return array(
      'name' => 'Block HTML id',
      'description' => 'Test block HTML id validity.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp(array('block', 'block_test'));

    // Create an admin user, log in and enable test blocks.
    $this->admin_user = $this->drupalCreateUser(array('administer blocks', 'access administration pages'));
    $this->drupalLogin($this->admin_user);

    // Enable our test block.
    $edit['blocks[block_test_test_html_id][region]'] = 'sidebar_first';
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Make sure the block has some content so it will appear
    $current_content = $this->randomName();
    variable_set('block_test_content', $current_content);
  }

  /**
   * Test valid HTML id.
   */
  function testHtmlId() {
    $this->drupalGet('');
    $this->assertRaw('block-block-test-test-html-id', t('HTML id for test block is valid.'));
  }
}
