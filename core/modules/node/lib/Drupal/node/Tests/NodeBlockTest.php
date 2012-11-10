<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeBlockTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests the availability of the syndicate block.
 */
class NodeBlockTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  public static function getInfo() {
    return array(
      'name' => 'Block availability',
      'description' => 'Check if the syndicate block is available.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp();

    // Create and login user
    $admin_user = $this->drupalCreateUser(array('administer blocks'));
    $this->drupalLogin($admin_user);
  }

  function testSyndicateBlock() {
    // Set block title to confirm that the interface is available.
    $this->drupalPost('admin/structure/block/manage/node/syndicate/configure', array('title' => $this->randomName(8)), t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), 'Block configuration set.');

    // Set the block to a region to confirm block is available.
    $edit = array();
    $edit['blocks[node_syndicate][region]'] = 'footer';
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
    $this->assertText(t('The block settings have been updated.'), 'Block successfully move to footer region.');
  }
}
