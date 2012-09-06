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
                                               'delete revisions', 'delete any page content', 'administer nodes'));
    $this->drupalLogin($web_user);

    // Create initial node.
    $node = $this->drupalCreateNode();
    $settings = get_object_vars($node);
    $settings['revision'] = 1;
    $settings['isDefaultRevision'] = TRUE;

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
      $settings['isDefaultRevision'] = TRUE;

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

    // Confirm that this is the default revision.
    $this->assertTrue($node->isDefaultRevision(), 'Third node revision is the default one.');

    // Confirm that revisions revert properly.
    $this->drupalPost("node/$node->nid/revisions/{$nodes[1]->vid}/revert", array(), t('Revert'));
    $this->assertRaw(t('@type %title has been reverted back to the revision from %revision-date.',
                        array('@type' => 'Basic page', '%title' => $nodes[1]->label(),
                              '%revision-date' => format_date($nodes[1]->revision_timestamp))), t('Revision reverted.'));
    $reverted_node = node_load($node->nid);
    $this->assertTrue(($nodes[1]->body[LANGUAGE_NOT_SPECIFIED][0]['value'] == $reverted_node->body[LANGUAGE_NOT_SPECIFIED][0]['value']), t('Node reverted correctly.'));

    // Confirm that this is not the default version.
    $node = node_revision_load($node->vid);
    $this->assertFalse($node->isDefaultRevision(), 'Third node revision is not the default one.');

    // Confirm revisions delete properly.
    $this->drupalPost("node/$node->nid/revisions/{$nodes[1]->vid}/delete", array(), t('Delete'));
    $this->assertRaw(t('Revision from %revision-date of @type %title has been deleted.',
                        array('%revision-date' => format_date($nodes[1]->revision_timestamp),
                              '@type' => 'Basic page', '%title' => $nodes[1]->label())), t('Revision deleted.'));
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
      '%title' => $nodes[2]->label(),
      '%revision-date' => format_date($old_revision_date),
    )));

    // Make a new revision and set it to not be default.
    // This will create a new revision that is not "front facing".
    $new_node_revision = clone $node;
    $new_body = $this->randomName();
    $new_node_revision->body[LANGUAGE_NOT_SPECIFIED][0]['value'] = $new_body;
    // Save this as a non-default revision.
    $new_node_revision->revision = TRUE;
    $new_node_revision->isDefaultRevision = FALSE;
    node_save($new_node_revision);

    $this->drupalGet("node/$node->nid");
    $this->assertNoText($new_body, t('Revision body text is not present on default version of node.'));

    // Verify that the new body text is present on the revision.
    $this->drupalGet("node/$node->nid/revisions/" . $new_node_revision->vid . "/view");
    $this->assertText($new_body, t('Revision body text is present when loading specific revision.'));

    // Verify that the non-default revision vid is greater than the default
    // revision vid.
    $default_revision = db_select('node', 'n')
      ->fields('n', array('vid'))
      ->condition('nid', $node->nid)
      ->execute()
      ->fetchCol();
    $default_revision_vid = $default_revision[0];
    $this->assertTrue($new_node_revision->vid > $default_revision_vid, 'Revision vid is greater than default revision vid.');
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
    $node_revision = node_load($node->nid, TRUE);
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
    $node_revision = node_load($node->nid, TRUE);
    $this->assertTrue(empty($node_revision->log), 'After a new node revision is saved with an empty log message, the log message for the node is empty.');
  }
}
