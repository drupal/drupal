<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeRevisionsUiTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests the UI for controlling node revision behavior.
 *
 * @group node
 */
class NodeRevisionsUiTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and log in user.
    $web_user = $this->drupalCreateUser(
      array(
        'administer nodes',
        'edit any page content'
      )
    );

    $this->drupalLogin($web_user);
  }

  /**
   * Checks that unchecking 'Create new revision' works when editing a node.
   */
  function testNodeFormSaveWithoutRevision() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');

    // Set page revision setting 'create new revision'. This will mean new
    // revisions are created by default when the node is edited.
    $type = entity_load('node_type', 'page');
    $type->setNewRevision(TRUE);
    $type->save();

    // Create the node.
    $node = $this->drupalCreateNode();

    // Verify the checkbox is checked on the node edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldChecked('edit-revision', "'Create new revision' checkbox is checked");

    // Uncheck the create new revision checkbox and save the node.
    $edit = array('revision' => FALSE);
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Load the node again and check the revision is the same as before.
    $node_storage->resetCache(array($node->id()));
    $node_revision = $node_storage->load($node->id(), TRUE);
    $this->assertEqual($node_revision->getRevisionId(), $node->getRevisionId(), "After an existing node is saved with 'Create new revision' unchecked, a new revision is not created.");

    // Verify the checkbox is checked on the node edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldChecked('edit-revision', "'Create new revision' checkbox is checked");

    // Submit the form without changing the checkbox.
    $edit = array();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Load the node again and check the revision is different from before.
    $node_storage->resetCache(array($node->id()));
    $node_revision = $node_storage->load($node->id());
    $this->assertNotEqual($node_revision->getRevisionId(), $node->getRevisionId(), "After an existing node is saved with 'Create new revision' checked, a new revision is created.");

  }
}
