<?php

/**
 * @file
 * Contains \Drupal\node\Tests\PageEditTest.
 */

namespace Drupal\node\Tests;

/**
 * Create a node and test node edit functionality.
 *
 * @group node
 */
class PageEditTest extends NodeTestBase {
  protected $webUser;
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['block', 'node', 'datetime'];

  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(array('edit own page content', 'create page content'));
    $this->adminUser = $this->drupalCreateUser(array('bypass node access', 'administer nodes'));
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Checks node edit functionality.
   */
  function testPageEdit() {
    $this->drupalLogin($this->webUser);

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
    $this->assertUrl($node->url('edit-form', ['absolute' => TRUE]));

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
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $this->drupalLogin($this->adminUser);

    // Create node to edit.
    $body_key = 'body[0][value]';
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));

    // Check that the node was authored by the currently logged in user.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertIdentical($node->getOwnerId(), $this->adminUser->id(), 'Node authored by admin user.');

    // Try to change the 'authored by' field to an invalid user name.
    $edit = array(
      'uid[0][target_id]' => 'invalid-name',
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $this->assertRaw(t('There are no entities matching "%name".', array('%name' => 'invalid-name')));

    // Change the authored by field to the anonymous user (uid 0).
    $edit['uid[0][target_id]'] = 'Anonymous (0)';
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $uid = $node->getOwnerId();
    // Most SQL database drivers stringify fetches but entities are not
    // necessarily stored in a SQL database. At the same time, NULL/FALSE/""
    // won't do.
    $this->assertTrue($uid === 0 || $uid === '0', 'Node authored by anonymous user.');

    // Change the authored by field to another user's name (that is not
    // logged in).
    $edit['uid[0][target_id]'] = $this->webUser->getUsername();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertIdentical($node->getOwnerId(), $this->webUser->id(), 'Node authored by normal user.');

    // Check that normal users cannot change the authored by information.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertNoFieldByName('uid[0][target_id]');
  }
}
