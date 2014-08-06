<?php

/**
 * @file
 * Definition of Drupal\node\Tests\PageEditTest.
 */

namespace Drupal\node\Tests;

/**
 * Create a node and test node edit functionality.
 *
 * @group node
 */
class PageEditTest extends NodeTestBase {
  protected $web_user;
  protected $admin_user;

  function setUp() {
    parent::setUp();

    $this->web_user = $this->drupalCreateUser(array('edit own page content', 'create page content'));
    $this->admin_user = $this->drupalCreateUser(array('bypass node access', 'administer nodes'));
  }

  /**
   * Checks node edit functionality.
   */
  function testPageEdit() {
    $this->drupalLogin($this->web_user);

    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    // Create node to edit.
    $edit = array();
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit[$title_key]);
    $this->assertTrue($node, 'Node found in database.');

    // Check that "edit" link points to correct page.
    $this->clickLink(t('Edit'));
    $edit_url = url("node/" . $node->id() . "/edit", array('absolute' => TRUE));
    $actual_url = $this->getURL();
    $this->assertEqual($edit_url, $actual_url, 'On edit page.');

    // Check that the title and body fields are displayed with the correct values.
    $active = '<span class="visually-hidden">' . t('(active tab)') . '</span>';
    $link_text = t('!local-task-title!active', array('!local-task-title' => t('Edit'), '!active' => $active));
    $this->assertText(strip_tags($link_text), 0, 'Edit tab found and marked active.');
    $this->assertFieldByName($title_key, $edit[$title_key], 'Title field displayed.');
    $this->assertFieldByName($body_key, $edit[$body_key], 'Body field displayed.');

    // Edit the content of the node.
    $edit = array();
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    // Stay on the current page, without reloading.
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check that the title and body fields are displayed with the updated values.
    $this->assertText($edit[$title_key], 'Title displayed.');
    $this->assertText($edit[$body_key], 'Body displayed.');

    // Login as a second administrator user.
    $second_web_user = $this->drupalCreateUser(array('administer nodes', 'edit any page content'));
    $this->drupalLogin($second_web_user);
    // Edit the same node, creating a new revision.
    $this->drupalGet("node/" . $node->id() . "/edit");
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit['revision'] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));

    // Ensure that the node revision has been created.
    $revised_node = $this->drupalGetNodeByTitle($edit['title[0][value]'], TRUE);
    $this->assertNotIdentical($node->getRevisionId(), $revised_node->getRevisionId(), 'A new revision has been created.');
    // Ensure that the node author is preserved when it was not changed in the
    // edit form.
    $this->assertIdentical($node->getOwnerId(), $revised_node->getOwnerId(), 'The node author has been preserved.');
    // Ensure that the revision authors are different since the revisions were
    // made by different users.
    $first_node_version = node_revision_load($node->getRevisionId());
    $second_node_version = node_revision_load($revised_node->getRevisionId());
    $this->assertNotIdentical($first_node_version->getRevisionAuthor()->id(), $second_node_version->getRevisionAuthor()->id(), 'Each revision has a distinct user.');
  }

  /**
   * Tests changing a node's "authored by" field.
   */
  function testPageAuthoredBy() {
    $this->drupalLogin($this->admin_user);

    // Create node to edit.
    $body_key = 'body[0][value]';
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));

    // Check that the node was authored by the currently logged in user.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertIdentical($node->getOwnerId(), $this->admin_user->id(), 'Node authored by admin user.');

    // Try to change the 'authored by' field to an invalid user name.
    $edit = array(
      'uid' => 'invalid-name',
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $this->assertText('The username invalid-name does not exist.');

    // Change the authored by field to an empty string, which should assign
    // authorship to the anonymous user (uid 0).
    $edit['uid'] = '';
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $node = node_load($node->id(), TRUE);
    $this->assertIdentical($node->getOwnerId(), '0', 'Node authored by anonymous user.');

    // Change the authored by field to another user's name (that is not
    // logged in).
    $edit['uid'] = $this->web_user->getUsername();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $node = node_load($node->id(), TRUE);
    $this->assertIdentical($node->getOwnerId(), $this->web_user->id(), 'Node authored by normal user.');

    // Check that normal users cannot change the authored by information.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertNoFieldByName('uid');
  }
}
