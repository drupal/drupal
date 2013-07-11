<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeRevisionsAllTestCase.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests actions against revisions for user with access to all revisions.
 */
class NodeRevisionsAllTestCase extends NodeTestBase {
  protected $nodes;
  protected $logs;
  protected $profile = "standard";

  public static function getInfo() {
    return array(
      'name' => 'Node revisions all',
      'description' => 'Create a node with revisions and test viewing, saving, reverting, and deleting revisions for user with access to all.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp();

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
      $logs[] = $node->log = $this->randomName(32);

      // Create revision with a random title and body and update variables.
      $node->title = $this->randomName();
      $node->body[$node->language()->id][0] = array(
        'value' => $this->randomName(32),
        'format' => filter_default_format(),
      );
      $node->setNewRevision();
      $node->save();

      $node = node_load($node->nid, TRUE); // Make sure we get revision information.
      $nodes[] = clone $node;
    }

    $this->nodes = $nodes;
    $this->logs = $logs;
  }

  /**
   * Checks node revision operations.
   */
  function testRevisions() {
    $nodes = $this->nodes;
    $logs = $this->logs;

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
    $this->drupalGet("node/$node->nid/revisions/$node->vid/view");
    $this->assertText($node->body[Language::LANGCODE_NOT_SPECIFIED][0]['value'], 'Correct text displays for version.');

    // Confirm the correct log message appears on "revisions overview" page.
    $this->drupalGet("node/$node->nid/revisions");
    foreach ($logs as $log) {
      $this->assertText($log, 'Log message found.');
    }

    // Confirm that this is the current revision.
    $this->assertTrue($node->isDefaultRevision(), 'Third node revision is the current one.');

    // Confirm that revisions revert properly.
    $this->drupalPost("node/$node->nid/revisions/{$nodes[1]->vid}/revert", array(), t('Revert'));
    $this->assertRaw(t('@type %title has been reverted back to the revision from %revision-date.',
      array(
        '@type' => 'Basic page',
        '%title' => $nodes[1]->title,
        '%revision-date' => format_date($nodes[1]->revision_timestamp)
      )),
      'Revision reverted.');
    $reverted_node = node_load($node->nid, TRUE);
    $this->assertTrue(($nodes[1]->body[Language::LANGCODE_NOT_SPECIFIED][0]['value'] == $reverted_node->body[Language::LANGCODE_NOT_SPECIFIED][0]['value']), 'Node reverted correctly.');

    // Confirm that this is not the current version.
    $node = node_revision_load($node->vid);
    $this->assertFalse($node->isDefaultRevision(), 'Third node revision is not the current one.');

    // Confirm revisions delete properly.
    $this->drupalPost("node/$node->nid/revisions/{$nodes[1]->vid}/delete", array(), t('Delete'));
    $this->assertRaw(t('Revision from %revision-date of @type %title has been deleted.',
      array(
        '%revision-date' => format_date($nodes[1]->revision_timestamp),
        '@type' => 'Basic page',
        '%title' => $nodes[1]->title,
      )),
      'Revision deleted.');
    $this->assertTrue(db_query('SELECT COUNT(vid) FROM {node_field_revision} WHERE nid = :nid and vid = :vid',
      array(':nid' => $node->nid, ':vid' => $nodes[1]->vid))->fetchField() == 0,
      'Revision not found.');

    // Set the revision timestamp to an older date to make sure that the
    // confirmation message correctly displays the stored revision date.
    $old_revision_date = REQUEST_TIME - 86400;
    db_update('node_field_revision')
      ->condition('vid', $nodes[2]->vid)
      ->fields(array(
        'revision_timestamp' => $old_revision_date,
      ))
      ->execute();
    $this->drupalPost("node/$node->nid/revisions/{$nodes[2]->vid}/revert", array(), t('Revert'));
    $this->assertRaw(t('@type %title has been reverted back to the revision from %revision-date.', array(
      '@type' => 'Basic page',
      '%title' => $nodes[2]->title,
      '%revision-date' => format_date($old_revision_date),
    )));
  }
}
