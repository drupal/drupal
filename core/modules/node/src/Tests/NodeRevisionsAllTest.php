<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeRevisionsAllTest.
 */

namespace Drupal\node\Tests;

/**
 * Create a node with revisions and test viewing, saving, reverting, and
 * deleting revisions for user with access to all.
 *
 * @group node
 */
class NodeRevisionsAllTest extends NodeTestBase {
  protected $nodes;
  protected $revisionLogs;
  protected $profile = "standard";

  protected function setUp() {
    parent::setUp();
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    // Create and log in user.
    $web_user = $this->drupalCreateUser(
      array(
        'view page revisions',
        'revert page revisions',
        'delete page revisions',
        'edit any page content',
        'delete any page content'
      )
    );
    $this->drupalLogin($web_user);

    // Create an initial node.
    $node = $this->drupalCreateNode();

    $settings = get_object_vars($node);
    $settings['revision'] = 1;

    $nodes = array();
    $logs = array();

    // Get the original node.
    $nodes[] = clone $node;

    // Create three revisions.
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $logs[] = $node->revision_log = $this->randomMachineName(32);

      // Create revision with a random title and body and update variables.
      $node->title = $this->randomMachineName();
      $node->body = array(
        'value' => $this->randomMachineName(32),
        'format' => filter_default_format(),
      );
      $node->setNewRevision();
      $node->save();

      $node_storage->resetCache(array($node->id()));
      $node = $node_storage->load($node->id()); // Make sure we get revision information.
      $nodes[] = clone $node;
    }

    $this->nodes = $nodes;
    $this->revisionLogs = $logs;
  }

  /**
   * Checks node revision operations.
   */
  function testRevisions() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $nodes = $this->nodes;
    $logs = $this->revisionLogs;

    // Get last node for simple checks.
    $node = $nodes[3];

    // Create and login user.
    $content_admin = $this->drupalCreateUser(
      array(
        'view all revisions',
        'revert all revisions',
        'delete all revisions',
        'edit any page content',
        'delete any page content'
      )
    );
    $this->drupalLogin($content_admin);

    // Confirm the correct revision text appears on "view revisions" page.
    $this->drupalGet("node/" . $node->id() . "/revisions/" . $node->getRevisionId() . "/view");
    $this->assertText($node->body->value, 'Correct text displays for version.');

    // Confirm the correct revision log message appears on the "revisions
    // overview" page.
    $this->drupalGet("node/" . $node->id() . "/revisions");
    foreach ($logs as $revision_log) {
      $this->assertText($revision_log, 'Revision log message found.');
    }

    // Confirm that this is the current revision.
    $this->assertTrue($node->isDefaultRevision(), 'Third node revision is the current one.');

    // Confirm that revisions revert properly.
    $this->drupalPostForm("node/" . $node->id() . "/revisions/" . $nodes[1]->getRevisionId() . "/revert", array(), t('Revert'));
    $this->assertRaw(t('@type %title has been reverted to the revision from %revision-date.',
      array(
        '@type' => 'Basic page',
        '%title' => $nodes[1]->getTitle(),
        '%revision-date' => format_date($nodes[1]->getRevisionCreationTime())
      )),
      'Revision reverted.');
    $node_storage->resetCache(array($node->id()));
    $reverted_node = $node_storage->load($node->id());
    $this->assertTrue(($nodes[1]->body->value == $reverted_node->body->value), 'Node reverted correctly.');

    // Confirm that this is not the current version.
    $node = node_revision_load($node->getRevisionId());
    $this->assertFalse($node->isDefaultRevision(), 'Third node revision is not the current one.');

    // Confirm revisions delete properly.
    $this->drupalPostForm("node/" . $node->id() . "/revisions/" . $nodes[1]->getRevisionId() . "/delete", array(), t('Delete'));
    $this->assertRaw(t('Revision from %revision-date of @type %title has been deleted.',
      array(
        '%revision-date' => format_date($nodes[1]->getRevisionCreationTime()),
        '@type' => 'Basic page',
        '%title' => $nodes[1]->getTitle(),
      )),
      'Revision deleted.');
    $this->assertTrue(db_query('SELECT COUNT(vid) FROM {node_revision} WHERE nid = :nid and vid = :vid',
      array(':nid' => $node->id(), ':vid' => $nodes[1]->getRevisionId()))->fetchField() == 0,
      'Revision not found.');

    // Set the revision timestamp to an older date to make sure that the
    // confirmation message correctly displays the stored revision date.
    $old_revision_date = REQUEST_TIME - 86400;
    db_update('node_revision')
      ->condition('vid', $nodes[2]->getRevisionId())
      ->fields(array(
        'revision_timestamp' => $old_revision_date,
      ))
      ->execute();
    $this->drupalPostForm("node/" . $node->id() . "/revisions/" . $nodes[2]->getRevisionId() . "/revert", array(), t('Revert'));
    $this->assertRaw(t('@type %title has been reverted to the revision from %revision-date.', array(
      '@type' => 'Basic page',
      '%title' => $nodes[2]->getTitle(),
      '%revision-date' => format_date($old_revision_date),
    )));
  }
}
