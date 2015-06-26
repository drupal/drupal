<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodePostSettingsTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests that the post information (submitted by Username on date) text displays
 * appropriately.
 *
 * @group node
 */
class NodePostSettingsTest extends NodeTestBase {

  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('create page content', 'administer content types', 'access user profiles'));
    $this->drupalLogin($web_user);
  }

  /**
   * Confirms "Basic page" content type and post information is on a new node.
   */
  function testPagePostInfo() {

    // Set "Basic page" content type to display post information.
    $edit = array();
    $edit['display_submitted'] = TRUE;
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));

    // Create a node.
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    // Check that the post information is displayed.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $elements = $this->xpath('//div[contains(@class, :class)]', array(':class' => 'node__submitted'));
    $this->assertEqual(count($elements), 1, 'Post information is displayed.');
    $node->delete();

    // Set "Basic page" content type to display post information.
    $edit = array();
    $edit['display_submitted'] = FALSE;
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));

    // Create a node.
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    // Check that the post information is displayed.
    $elements = $this->xpath('//div[contains(@class, :class)]', array(':class' => 'node__submitted'));
    $this->assertEqual(count($elements), 0, 'Post information is not displayed.');
  }
}
