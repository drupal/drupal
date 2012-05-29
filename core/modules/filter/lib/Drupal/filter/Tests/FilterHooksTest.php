<?php

/**
 * @file
 * Definition of Drupal\filter\Tests\FilterHooksTest.
 */

namespace Drupal\filter\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for filter hook invocation.
 */
class FilterHooksTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Filter format hooks',
      'description' => 'Test hooks for text formats insert/update/disable.',
      'group' => 'Filter',
    );
  }

  function setUp() {
    parent::setUp('block', 'filter_test');
    $admin_user = $this->drupalCreateUser(array('administer filters', 'administer blocks'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Test that hooks run correctly on creating, editing, and deleting a text format.
   */
  function testFilterHooks() {
    // Add a text format.
    $name = $this->randomName();
    $edit = array();
    $edit['format'] = drupal_strtolower($this->randomName());
    $edit['name'] = $name;
    $edit['roles[' . DRUPAL_ANONYMOUS_RID . ']'] = 1;
    $this->drupalPost('admin/config/content/formats/add', $edit, t('Save configuration'));
    $this->assertRaw(t('Added text format %format.', array('%format' => $name)), t('New format created.'));
    $this->assertText('hook_filter_format_insert invoked.', t('hook_filter_format_insert was invoked.'));

    $format_id = $edit['format'];

    // Update text format.
    $edit = array();
    $edit['roles[' . DRUPAL_AUTHENTICATED_RID . ']'] = 1;
    $this->drupalPost('admin/config/content/formats/' . $format_id, $edit, t('Save configuration'));
    $this->assertRaw(t('The text format %format has been updated.', array('%format' => $name)), t('Format successfully updated.'));
    $this->assertText('hook_filter_format_update invoked.', t('hook_filter_format_update() was invoked.'));

    // Add a new custom block.
    $custom_block = array();
    $custom_block['info'] = $this->randomName(8);
    $custom_block['title'] = $this->randomName(8);
    $custom_block['body[value]'] = $this->randomName(32);
    // Use the format created.
    $custom_block['body[format]'] = $format_id;
    $this->drupalPost('admin/structure/block/add', $custom_block, t('Save block'));
    $this->assertText(t('The block has been created.'), t('New block successfully created.'));

    // Verify the new block is in the database.
    $bid = db_query("SELECT bid FROM {block_custom} WHERE info = :info", array(':info' => $custom_block['info']))->fetchField();
    $this->assertNotNull($bid, t('New block found in database'));

    // Disable the text format.
    $this->drupalPost('admin/config/content/formats/' . $format_id . '/disable', array(), t('Disable'));
    $this->assertRaw(t('Disabled text format %format.', array('%format' => $name)), t('Format successfully disabled.'));
    $this->assertText('hook_filter_format_disable invoked.', t('hook_filter_format_disable() was invoked.'));
  }
}
