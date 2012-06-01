<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeSaveTest.
 */

namespace Drupal\node\Tests;

/**
 * Test case to check node save related functionality, including import-save
 */
class NodeSaveTest extends NodeTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Node save',
      'description' => 'Test $node->save() for saving content.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp('node_test');
    // Create a user that is allowed to post; we'll use this to test the submission.
    $web_user = $this->drupalCreateUser(array('create article content'));
    $this->drupalLogin($web_user);
    $this->web_user = $web_user;
  }

  /**
   * Import test, to check if custom node ids are saved properly.
   * Workflow:
   *  - first create a piece of content
   *  - save the content
   *  - check if node exists
   */
  function testImport() {
    // Node ID must be a number that is not in the database.
    $max_nid = db_query('SELECT MAX(nid) FROM {node}')->fetchField();
    $test_nid = $max_nid + mt_rand(1000, 1000000);
    $title = $this->randomName(8);
    $node = array(
      'title' => $title,
      'body' => array(LANGUAGE_NOT_SPECIFIED => array(array('value' => $this->randomName(32)))),
      'uid' => $this->web_user->uid,
      'type' => 'article',
      'nid' => $test_nid,
      'enforceIsNew' => TRUE,
    );
    $node = node_submit(entity_create('node', $node));

    // Verify that node_submit did not overwrite the user ID.
    $this->assertEqual($node->uid, $this->web_user->uid, t('Function node_submit() preserves user ID'));

    $node->save();
    // Test the import.
    $node_by_nid = node_load($test_nid);
    $this->assertTrue($node_by_nid, t('Node load by node ID.'));

    $node_by_title = $this->drupalGetNodeByTitle($title);
    $this->assertTrue($node_by_title, t('Node load by node title.'));
  }

  /**
   * Check that the "created" and "changed" timestamps are set correctly when
   * saving a new node or updating an existing node.
   */
  function testTimestamps() {
    // Use the default timestamps.
    $edit = array(
      'uid' => $this->web_user->uid,
      'type' => 'article',
      'title' => $this->randomName(8),
    );

    entity_create('node', $edit)->save();
    $node = $this->drupalGetNodeByTitle($edit['title']);
    $this->assertEqual($node->created, REQUEST_TIME, t('Creating a node sets default "created" timestamp.'));
    $this->assertEqual($node->changed, REQUEST_TIME, t('Creating a node sets default "changed" timestamp.'));

    // Store the timestamps.
    $created = $node->created;
    $changed = $node->changed;

    $node->save();
    $node = $this->drupalGetNodeByTitle($edit['title'], TRUE);
    $this->assertEqual($node->created, $created, t('Updating a node preserves "created" timestamp.'));

    // Programmatically set the timestamps using hook_node_presave.
    $node->title = 'testing_node_presave';

    $node->save();
    $node = $this->drupalGetNodeByTitle('testing_node_presave', TRUE);
    $this->assertEqual($node->created, 280299600, t('Saving a node uses "created" timestamp set in presave hook.'));
    $this->assertEqual($node->changed, 979534800, t('Saving a node uses "changed" timestamp set in presave hook.'));

    // Programmatically set the timestamps on the node.
    $edit = array(
      'uid' => $this->web_user->uid,
      'type' => 'article',
      'title' => $this->randomName(8),
      'created' => 280299600, // Sun, 19 Nov 1978 05:00:00 GMT
      'changed' => 979534800, // Drupal 1.0 release.
    );

    entity_create('node', $edit)->save();
    $node = $this->drupalGetNodeByTitle($edit['title']);
    $this->assertEqual($node->created, 280299600, t('Creating a node uses user-set "created" timestamp.'));
    $this->assertNotEqual($node->changed, 979534800, t('Creating a node doesn\'t use user-set "changed" timestamp.'));

    // Update the timestamps.
    $node->created = 979534800;
    $node->changed = 280299600;

    $node->save();
    $node = $this->drupalGetNodeByTitle($edit['title'], TRUE);
    $this->assertEqual($node->created, 979534800, t('Updating a node uses user-set "created" timestamp.'));
    $this->assertNotEqual($node->changed, 280299600, t('Updating a node doesn\'t use user-set "changed" timestamp.'));
  }

  /**
   * Tests determing changes in hook_node_presave() and verifies the static node
   * load cache is cleared upon save.
   */
  function testDeterminingChanges() {
    // Initial creation.
    $node = entity_create('node', array(
      'uid' => $this->web_user->uid,
      'type' => 'article',
      'title' => 'test_changes',
    ));
    $node->save();

    // Update the node without applying changes.
    $node->save();
    $this->assertEqual($node->title, 'test_changes', 'No changes have been determined.');

    // Apply changes.
    $node->title = 'updated';
    $node->save();

    // The hook implementations node_test_node_presave() and
    // node_test_node_update() determine changes and change the title.
    $this->assertEqual($node->title, 'updated_presave_update', 'Changes have been determined.');

    // Test the static node load cache to be cleared.
    $node = node_load($node->nid);
    $this->assertEqual($node->title, 'updated_presave', 'Static cache has been cleared.');
  }
}
