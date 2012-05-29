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
  public static function getInfo() {
    return array(
      'name' => 'Node type persist',
      'description' => 'Ensures that node type customization survives module enabling and disabling.',
      'group' => 'Node',
    );
  }

  /**
   * Test node type customizations persist through disable and uninstall.
   */
  function testNodeTypeCustomizationPersistence() {
    $web_user = $this->drupalCreateUser(array('bypass node access', 'administer content types', 'administer modules'));
    $this->drupalLogin($web_user);
    $poll_key = 'modules[Core][poll][enable]';
    $poll_enable = array($poll_key => "1");
    $poll_disable = array($poll_key => FALSE);

    // Enable poll and verify that the node type is in the DB and is not
    // disabled.
    $this->drupalPost('admin/modules', $poll_enable, t('Save configuration'));
    $disabled = db_query('SELECT disabled FROM {node_type} WHERE type = :type', array(':type' => 'poll'))->fetchField();
    $this->assertNotIdentical($disabled, FALSE, t('Poll node type found in the database'));
    $this->assertEqual($disabled, 0, t('Poll node type is not disabled'));

    // Check that poll node type (uncustomized) shows up.
    $this->drupalGet('node/add');
    $this->assertText('poll', t('poll type is found on node/add'));

    // Customize poll description.
    $description = $this->randomName();
    $edit = array('description' => $description);
    $this->drupalPost('admin/structure/types/manage/poll', $edit, t('Save content type'));

    // Check that poll node type customization shows up.
    $this->drupalGet('node/add');
    $this->assertText($description, t('Customized description found'));

    // Disable poll and check that the node type gets disabled.
    $this->drupalPost('admin/modules', $poll_disable, t('Save configuration'));
    $disabled = db_query('SELECT disabled FROM {node_type} WHERE type = :type', array(':type' => 'poll'))->fetchField();
    $this->assertEqual($disabled, 1, t('Poll node type is disabled'));
    $this->drupalGet('node/add');
    $this->assertNoText('poll', t('poll type is not found on node/add'));

    // Reenable poll and check that the customization survived the module
    // disable.
    $this->drupalPost('admin/modules', $poll_enable, t('Save configuration'));
    $disabled = db_query('SELECT disabled FROM {node_type} WHERE type = :type', array(':type' => 'poll'))->fetchField();
    $this->assertNotIdentical($disabled, FALSE, t('Poll node type found in the database'));
    $this->assertEqual($disabled, 0, t('Poll node type is not disabled'));
    $this->drupalGet('node/add');
    $this->assertText($description, t('Customized description found'));

    // Disable and uninstall poll.
    $this->drupalPost('admin/modules', $poll_disable, t('Save configuration'));
    $edit = array('uninstall[poll]' => 'poll');
    $this->drupalPost('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPost(NULL, array(), t('Uninstall'));
    $disabled = db_query('SELECT disabled FROM {node_type} WHERE type = :type', array(':type' => 'poll'))->fetchField();
    $this->assertTrue($disabled, t('Poll node type is in the database and is disabled'));
    $this->drupalGet('node/add');
    $this->assertNoText('poll', t('poll type is no longer found on node/add'));

    // Reenable poll and check that the customization survived the module
    // uninstall.
    $this->drupalPost('admin/modules', $poll_enable, t('Save configuration'));
    $this->drupalGet('node/add');
    $this->assertText($description, t('Customized description is found even after uninstall and reenable.'));
  }
}
