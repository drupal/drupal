<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\BlockTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

class BlockTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Block functionality',
      'description' => 'Configure and move powered-by block.',
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp('block');

    // Create and login user
    $admin_user = $this->drupalCreateUser(array('administer blocks', 'access administration pages'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Test displaying and hiding the powered-by and help blocks.
   */
  function testSystemBlocks() {
    // Set block title and some settings to confirm that the interface is available.
    $this->drupalPost('admin/structure/block/manage/system/powered-by/configure', array('title' => $this->randomName(8)), t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), t('Block configuration set.'));

    // Set the powered-by block to the footer region.
    $edit = array();
    $edit['blocks[system_powered-by][region]'] = 'footer';
    $edit['blocks[system_main][region]'] = 'content';
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
    $this->assertText(t('The block settings have been updated.'), t('Block successfully moved to footer region.'));

    // Confirm that the block is being displayed.
    $this->drupalGet('node');
    $this->assertRaw('id="block-system-powered-by"', t('Block successfully being displayed on the page.'));

    // Set the block to the disabled region.
    $edit = array();
    $edit['blocks[system_powered-by][region]'] = '-1';
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Confirm that the block is hidden.
    $this->assertNoRaw('id="block-system-powered-by"', t('Block no longer appears on page.'));

    // For convenience of developers, set the block to its default settings.
    $edit = array();
    $edit['blocks[system_powered-by][region]'] = 'footer';
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
    $this->drupalPost('admin/structure/block/manage/system/powered-by/configure', array('title' => ''), t('Save block'));

    // Set the help block to the help region.
    $edit = array();
    $edit['blocks[system_help][region]'] = 'help';
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));

    // Test displaying the help block with block caching enabled.
    variable_set('block_cache', TRUE);
    $this->drupalGet('admin/structure/block/add');
    $this->assertRaw(t('Use this page to create a new custom block.'));
    $this->drupalGet('admin/index');
    $this->assertRaw(t('This page shows you all available administration tasks for each module.'));
  }
}
