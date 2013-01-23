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
    $forum_disable = array($forum_key => FALSE);

    // Enable forum and verify that the node type is in the DB and is not
    // disabled.
    $this->drupalPost('admin/modules', $forum_enable, t('Save configuration'));
    $disabled = db_query('SELECT disabled FROM {node_type} WHERE type = :type', array(':type' => 'forum'))->fetchField();
    $this->assertNotIdentical($disabled, FALSE, 'Forum node type found in the database');
    $this->assertEqual($disabled, 0, 'Forum node type is not disabled');

    // Check that forum node type (uncustomized) shows up.
    $this->drupalGet('node/add');
    $this->assertText('forum', 'forum type is found on node/add');

    // Customize forum description.
    $description = $this->randomName();
    $edit = array('description' => $description);
    $this->drupalPost('admin/structure/types/manage/forum', $edit, t('Save content type'));

    // Check that forum node type customization shows up.
    $this->drupalGet('node/add');
    $this->assertText($description, 'Customized description found');

    // Disable forum and check that the node type gets disabled.
    $this->drupalPost('admin/modules', $forum_disable, t('Save configuration'));
    $disabled = db_query('SELECT disabled FROM {node_type} WHERE type = :type', array(':type' => 'forum'))->fetchField();
    $this->assertEqual($disabled, 1, 'Forum node type is disabled');
    $this->drupalGet('node/add');
    $this->assertNoText('forum', 'forum type is not found on node/add');

    // Reenable forum and check that the customization survived the module
    // disable.
    $this->drupalPost('admin/modules', $forum_enable, t('Save configuration'));
    $disabled = db_query('SELECT disabled FROM {node_type} WHERE type = :type', array(':type' => 'forum'))->fetchField();
    $this->assertNotIdentical($disabled, FALSE, 'Forum node type found in the database');
    $this->assertEqual($disabled, 0, 'Forum node type is not disabled');
    $this->drupalGet('node/add');
    $this->assertText($description, 'Customized description found');

    // Disable and uninstall forum.
    $this->drupalPost('admin/modules', $forum_disable, t('Save configuration'));
    $edit = array('uninstall[forum]' => 'forum');
    $this->drupalPost('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPost(NULL, array(), t('Uninstall'));
    $disabled = db_query('SELECT disabled FROM {node_type} WHERE type = :type', array(':type' => 'forum'))->fetchField();
    $this->assertTrue($disabled, 'Forum node type is in the database and is disabled');
    $this->drupalGet('node/add');
    $this->assertNoText('forum', 'forum type is no longer found on node/add');

    // Reenable forum and check that the customization survived the module
    // uninstall.
    $this->drupalPost('admin/modules', $forum_enable, t('Save configuration'));
    $this->drupalGet('node/add');
    $this->assertText($description, 'Customized description is found even after uninstall and reenable.');
  }
}
