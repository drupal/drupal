<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTypePersistenceTest.
 */

namespace Drupal\node\Tests;

/**
 * Test node type customizations persistence.
 */
class NodeTypePersistenceTest extends NodeTestBase {
  // Enable the prerequisite modules for forum
  public static $modules = array('history', 'taxonomy', 'options', 'comment');
  public static function getInfo() {
    return array(
      'name' => 'Node type persist',
      'description' => 'Ensures that node type customization survives module enabling and disabling.',
      'group' => 'Node',
    );
  }

  /**
   * Tests that node type customizations persist through disable and uninstall.
   */
  function testNodeTypeCustomizationPersistence() {
    $web_user = $this->drupalCreateUser(array('bypass node access', 'administer content types', 'administer modules'));
    $this->drupalLogin($web_user);
    $forum_key = 'modules[Core][forum][enable]';
    $forum_enable = array($forum_key => "1");

    // Enable forum and verify that the node type exists and is not disabled.
    $this->drupalPostForm('admin/modules', $forum_enable, t('Save configuration'));
    $forum = entity_load('node_type', 'forum');
    $this->assertTrue($forum->id(), 'Forum node type found.');
    $this->assertTrue($forum->isLocked(), 'Forum node type is locked');

    // Check that forum node type (uncustomized) shows up.
    $this->drupalGet('node/add');
    $this->assertText('forum', 'forum type is found on node/add');

    // Customize forum description.
    $description = $this->randomName();
    $edit = array('description' => $description);
    $this->drupalPostForm('admin/structure/types/manage/forum', $edit, t('Save content type'));

    // Check that forum node type customization shows up.
    $this->drupalGet('node/add');
    $this->assertText($description, 'Customized description found');

    // Uninstall forum.
    $edit = array('uninstall[forum]' => 'forum');
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPostForm(NULL, array(), t('Uninstall'));
    $forum = entity_load('node_type', 'forum');
    $this->assertFalse($forum->isLocked(), 'Forum node type is not locked');
    $this->drupalGet('node/add');
    $this->assertNoText('forum', 'forum type is no longer found on node/add');

    // Reenable forum and check that the customization survived the module
    // uninstall.
    $this->drupalPostForm('admin/modules', $forum_enable, t('Save configuration'));
    $this->drupalGet('node/add');
    $this->assertText($description, 'Customized description is found even after uninstall and reenable.');
  }

}
