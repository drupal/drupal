<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeRevisionsTest.
 */

namespace Drupal\node\Tests;

class NodeRevisionsTest extends NodeTestBase {
  protected $nodes;
  protected $logs;

  public static function getInfo() {
    return array(
      'name' => 'Node revisions',
      'description' => 'Create a node with revisions and test viewing, saving, reverting, and deleting revisions.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp();

    // Create and login user.
    $web_user = $this->drupalCreateUser(array('view revisions', 'revert revisions', 'edit any page content',
                                               'delete revisions', 'delete any page content'));
    $this->drupalLogin($web_user);

    // Create initial node.
    $node = $this->drupalCreateNode();
    $settings = get_object_vars($node);
    $settings['revision'] = 1;

    $nodes = array();
    $logs = array();

    // Get original node.
    $nodes[] = $node;

    // Create three revisions.
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $logs[] = $settings['log'] = $this->randomName(32);

      // Create revision with random title and body and update variables.
      $this->drupalCreateNode($settings);
      $node = node_load($node->nid); // Make sure we get revision information.
      $settings = get_object_vars($node);

      $nodes[] = $node;
    }

    $this->nodes = $nodes;
    $this->logs = $logs;
  }

  /**
   * Check node revision related operations.
   */
  function testRevisions() {
    $nodes = $this->nodes;
    $logs = $this->logs;

    // Get last node for simple checks.
    $node = $nodes[3];

    // Confirm the correct revision text appears on "view revisions" page.
    $this->drupalGet("node/$node->nid/revisions/$node->vid/view");
    $this->assertText($node->body[LANGUAGE_NOT_SPECIFIED][0]['value'], t('Correct text displays for version.'));

    // Confirm the correct log message appears on "revisions overview" page.
    $this->drupalGet("node/$node->nid/revisions");
    foreach ($logs as $log) {
      $this->assertText($log, t('Log message found.'));
    }

    // Confirm that revisions revert properly.
    $this->drupalPost("node/$node->nid/revisions/{$nodes[1]->vid}/revert", array(), t('Revert'));
    $this->assertRaw(t('@type %title has been reverted back to the revision from %revision-date.',
                        array('@type' => 'Basic page', '%title' => $nodes[1]->title,
                              '%revision-date' => format_date($nodes[1]->revision_timestamp))), t('Revision reverted.'));
    $reverted_node = node_load($node->nid);
    $this->assertTrue(($nodes[1]->body[LANGUAGE_NOT_SPECIFIED][0]['value'] == $reverted_node->body[LANGUAGE_NOT_SPECIFIED][0]['value']), t('Node reverted correctly.'));

    // Confirm revisions delete properly.
    $this->drupalPost("node/$node->nid/revisions/{$nodes[1]->vid}/delete", array(), t('Delete'));
    $this->assertRaw(t('Revision from %revision-date of @type %title has been deleted.',
                        array('%revision-date' => format_date($nodes[1]->revision_timestamp),
                              '@type' => 'Basic page', '%title' => $nodes[1]->title)), t('Revision deleted.'));
    $this->assertTrue(db_query('SELECT COUNT(vid) FROM {node_revision} WHERE nid = :nid and vid = :vid', array(':nid' => $node->nid, ':vid' => $nodes[1]->vid))->fetchField() == 0, t('Revision not found.'));

    // Set the revision timestamp to an older date to make sure that the
    // confirmation message correctly displays the stored revision date.
    $old_revision_date = REQUEST_TIME - 86400;
    db_update('node_revision')
      ->condition('vid', $nodes[2]->vid)
      ->fields(array(
        'timestamp' => $old_revision_date,
      ))
      ->execute();
    $this->drupalPost("node/$node->nid/revisions/{$nodes[2]->vid}/revert", array(), t('Revert'));
    $this->assertRaw(t('@type %title has been reverted back to the revision from %revision-date.', array(
      '@type' => 'Basic page',
      '%title' => $nodes[2]->title,
      '%revision-date' => format_date($old_revision_date),
    )));
  }

  /**
   * Checks that revisions are correctly saved without log messages.
   */
  function testNodeRevisionWithoutLogMessage() {
    // Create a node with an initial log message.
    $log = $this->randomName(10);
    $node = $this->drupalCreateNode(array('log' => $log));

    // Save over the same revision and explicitly provide an empty log message
    // (for example, to mimic the case of a node form submitted with no text in
    // the "log message" field), and check that the original log message is
    // preserved.
    $new_title = $this->randomName(10) . 'testNodeRevisionWithoutLogMessage1';

    $node = clone $node;
    $node->title = $new_title;
    $node->log = '';
    $node->revision = FALSE;

    $node->save();
    $this->drupalGet('node/' . $node->nid);
    $this->assertText($new_title, t('New node title appears on the page.'));
    $node_revision = node_load($node->nid, NULL, TRUE);
    $this->assertEqual($node_revision->log, $log, t('After an existing node revision is re-saved without a log message, the original log message is preserved.'));

    // Create another node with an initial log message.
    $node = $this->drupalCreateNode(array('log' => $log));

    // Save a new node revision without providing a log message, and check that
    // this revision has an empty log message.
    $new_title = $this->randomName(10) . 'testNodeRevisionWithoutLogMessage2';

    $node = clone $node;
    $node->title = $new_title;
    $node->revision = TRUE;
    $node->log = NULL;

    $node->save();
    $this->drupalGet('node/' . $node->nid);
    $this->assertText($new_title, 'New node title appears on the page.');
    $node_revision = node_load($node->nid, NULL, TRUE);
    $this->assertTrue(empty($node_revision->log), 'After a new node revision is saved with an empty log message, the log message for the node is empty.');
  }
}
