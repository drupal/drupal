<?php

/**
 * @file
 * Definition of Drupal\tracker\Tests\TrackerTest.
 */

namespace Drupal\tracker\Tests;

use Drupal\comment\CommentInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Defines a base class for testing tracker.module.
 */
class TrackerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'tracker', 'history');

  /**
   * The main user for testing.
   *
   * @var object
   */
  protected $user;

  /**
   * A second user that will 'create' comments and nodes.
   *
   * @var object
   */
  protected $other_user;

  public static function getInfo() {
    return array(
      'name' => 'Tracker',
      'description' => 'Create and delete nodes and check for their display in the tracker listings.',
      'group' => 'Tracker'
    );
  }

  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $permissions = array('access comments', 'create page content', 'post comments', 'skip comment approval');
    $this->user = $this->drupalCreateUser($permissions);
    $this->other_user = $this->drupalCreateUser($permissions);
    $this->container->get('comment.manager')->addDefaultField('node', 'page');
  }

  /**
   * Tests for the presence of nodes on the global tracker listing.
   */
  function testTrackerAll() {
    $this->drupalLogin($this->user);

    $unpublished = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
      'status' => 0,
    ));
    $published = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
      'status' => 1,
    ));

    $this->drupalGet('tracker');
    $this->assertNoText($unpublished->label(), 'Unpublished node does not show up in the tracker listing.');
    $this->assertText($published->label(), 'Published node shows up in the tracker listing.');
    $this->assertLink(t('My recent content'), 0, 'User tab shows up on the global tracker page.');

    // Delete a node and ensure it no longer appears on the tracker.
    $published->delete();
    $this->drupalGet('tracker');
    $this->assertNoText($published->label(), 'Deleted node does not show up in the tracker listing.');
  }

  /**
   * Tests for the presence of nodes on a user's tracker listing.
   */
  function testTrackerUser() {
    $this->drupalLogin($this->user);

    $unpublished = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
      'uid' => $this->user->id(),
      'status' => 0,
    ));
    $my_published = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
      'uid' => $this->user->id(),
      'status' => 1,
    ));
    $other_published_no_comment = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
      'uid' => $this->other_user->id(),
      'status' => 1,
    ));
    $other_published_my_comment = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
      'uid' => $this->other_user->id(),
      'status' => 1,
    ));
    $comment = array(
      'subject' => $this->randomName(),
      'comment_body[0][value]' => $this->randomName(20),
    );
    $this->drupalPostForm('comment/reply/node/' . $other_published_my_comment->id() . '/comment', $comment, t('Save'));

    $this->drupalGet('user/' . $this->user->id() . '/track');
    $this->assertNoText($unpublished->label(), "Unpublished nodes do not show up in the user's tracker listing.");
    $this->assertText($my_published->label(), "Published nodes show up in the user's tracker listing.");
    $this->assertNoText($other_published_no_comment->label(), "Another user's nodes do not show up in the user's tracker listing.");
    $this->assertText($other_published_my_comment->label(), "Nodes that the user has commented on appear in the user's tracker listing.");
    // Verify that title and tab title have been set correctly.
    $this->assertText('Track', 'The user tracker tab has the name "Track".');
    $this->assertTitle(t('@name | @site', array('@name' => $this->user->getUsername(), '@site' => \Drupal::config('system.site')->get('name'))), 'The user tracker page has the correct page title.');

    // Verify that unpublished comments are removed from the tracker.
    $admin_user = $this->drupalCreateUser(array('post comments', 'administer comments', 'access user profiles'));
    $this->drupalLogin($admin_user);
    $this->drupalPostForm('comment/1/edit', array('status' => CommentInterface::NOT_PUBLISHED), t('Save'));
    $this->drupalGet('user/' . $this->user->id() . '/track');
    $this->assertNoText($other_published_my_comment->label(), 'Unpublished comments are not counted on the tracker listing.');
  }

  /**
   * Tests for the presence of the "new" flag for nodes.
   */
  function testTrackerNewNodes() {
    $this->drupalLogin($this->user);

    $edit = array(
      'title' => $this->randomName(8),
    );

    $node = $this->drupalCreateNode($edit);
    $title = $edit['title'];
    $this->drupalGet('tracker');
    $this->assertPattern('/' . $title . '.*new/', 'New nodes are flagged as such in the tracker listing.');

    $this->drupalGet('node/' . $node->id());
    // Simulate the JavaScript on the node page to mark the node as read.
    // @todo Get rid of curlExec() once https://drupal.org/node/2074037 lands.
    $this->curlExec(array(
      CURLOPT_URL => url('history/' . $node->id() . '/read', array('absolute' => TRUE)),
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
      ),
    ));
    $this->drupalGet('tracker');
    $this->assertNoPattern('/' . $title . '.*new/', 'Visited nodes are not flagged as new.');

    $this->drupalLogin($this->other_user);
    $this->drupalGet('tracker');
    $this->assertPattern('/' . $title . '.*new/', 'For another user, new nodes are flagged as such in the tracker listing.');

    $this->drupalGet('node/' . $node->id());
    // Simulate the JavaScript on the node page to mark the node as read.
    // @todo Get rid of curlExec() once https://drupal.org/node/2074037 lands.
    $this->curlExec(array(
      CURLOPT_URL => url('history/' . $node->id() . '/read', array('absolute' => TRUE)),
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
      ),
    ));
    $this->drupalGet('tracker');
    $this->assertNoPattern('/' . $title . '.*new/', 'For another user, visited nodes are not flagged as new.');
  }

  /**
   * Tests for comment counters on the tracker listing.
   */
  function testTrackerNewComments() {
    $this->drupalLogin($this->user);

    $node = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
    ));

    // Add a comment to the page.
    $comment = array(
      'subject' => $this->randomName(),
      'comment_body[0][value]' => $this->randomName(20),
    );
    $this->drupalPostForm('comment/reply/node/' . $node->id() . '/comment', $comment, t('Save'));
    // The new comment is automatically viewed by the current user. Simulate the
    // JavaScript that does this.
    // @todo Get rid of curlExec() once https://drupal.org/node/2074037 lands.
    $this->curlExec(array(
      CURLOPT_URL => url('history/' . $node->id() . '/read', array('absolute' => TRUE)),
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
      ),
    ));

    $this->drupalLogin($this->other_user);
    $this->drupalGet('tracker');
    $this->assertText('1 new', 'New comments are counted on the tracker listing pages.');
    $this->drupalGet('node/' . $node->id());

    // Add another comment as other_user.
    $comment = array(
      'subject' => $this->randomName(),
      'comment_body[0][value]' => $this->randomName(20),
    );
    // If the comment is posted in the same second as the last one then Drupal
    // can't tell the difference, so we wait one second here.
    sleep(1);
    $this->drupalPostForm('comment/reply/node/' . $node->id(). '/comment', $comment, t('Save'));

    $this->drupalLogin($this->user);
    $this->drupalGet('tracker');
    $this->assertText('1 new', 'New comments are counted on the tracker listing pages.');
  }

  /**
   * Tests for ordering on a users tracker listing when comments are posted.
   */
  function testTrackerOrderingNewComments() {
    $this->drupalLogin($this->user);

    $node_one = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
    ));

    $node_two = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
    ));

    // Now get other_user to track these pieces of content.
    $this->drupalLogin($this->other_user);

    // Add a comment to the first page.
    $comment = array(
      'subject' => $this->randomName(),
      'comment_body[0][value]' => $this->randomName(20),
    );
    $this->drupalPostForm('comment/reply/node/' . $node_one->id() . '/comment', $comment, t('Save'));

    // If the comment is posted in the same second as the last one then Drupal
    // can't tell the difference, so we wait one second here.
    sleep(1);

    // Add a comment to the second page.
    $comment = array(
      'subject' => $this->randomName(),
      'comment_body[0][value]' => $this->randomName(20),
    );
    $this->drupalPostForm('comment/reply/node/' . $node_two->id() . '/comment', $comment, t('Save'));

    // We should at this point have in our tracker for other_user:
    // 1. node_two
    // 2. node_one
    // Because that's the reverse order of the posted comments.

    // Now we're going to post a comment to node_one which should jump it to the
    // top of the list.

    $this->drupalLogin($this->user);
    // If the comment is posted in the same second as the last one then Drupal
    // can't tell the difference, so we wait one second here.
    sleep(1);

    // Add a comment to the second page.
    $comment = array(
      'subject' => $this->randomName(),
      'comment_body[0][value]' => $this->randomName(20),
    );
    $this->drupalPostForm('comment/reply/node/' . $node_one->id() . '/comment', $comment, t('Save'));

    // Switch back to the other_user and assert that the order has swapped.
    $this->drupalLogin($this->other_user);
    $this->drupalGet('user/' . $this->other_user->id() . '/track');
    // This is a cheeky way of asserting that the nodes are in the right order
    // on the tracker page.
    // It's almost certainly too brittle.
    $pattern = '/' . preg_quote($node_one->getTitle()) . '.+' . preg_quote($node_two->getTitle()) . '/s';
    $this->verbose($pattern);
    $this->assertPattern($pattern, 'Most recently commented on node appears at the top of tracker');
  }

  /**
   * Tests that existing nodes are indexed by cron.
   */
  function testTrackerCronIndexing() {
    $this->drupalLogin($this->user);

    // Create 3 nodes.
    $edits = array();
    $nodes = array();
    for ($i = 1; $i <= 3; $i++) {
      $edits[$i] = array(
        'title' => $this->randomName(),
      );
      $nodes[$i] = $this->drupalCreateNode($edits[$i]);
    }

    // Add a comment to the last node as other user.
    $this->drupalLogin($this->other_user);
    $comment = array(
      'subject' => $this->randomName(),
      'comment_body[0][value]' => $this->randomName(20),
    );
    $this->drupalPostForm('comment/reply/node/' . $nodes[3]->id() . '/comment', $comment, t('Save'));

    // Start indexing backwards from node 3.
    \Drupal::state()->set('tracker.index_nid', 3);

    // Clear the current tracker tables and rebuild them.
    db_delete('tracker_node')
      ->execute();
    db_delete('tracker_user')
      ->execute();
    tracker_cron();

    $this->drupalLogin($this->user);

    // Fetch the user's tracker.
    $this->drupalGet('tracker/' . $this->user->id());

    // Assert that all node titles are displayed.
    foreach ($nodes as $i => $node) {
      $this->assertText($node->label(), format_string('Node @i is displayed on the tracker listing pages.', array('@i' => $i)));
    }
    $this->assertText('1 new', 'One new comment is counted on the tracker listing pages.');
    $this->assertText('updated', 'Node is listed as updated');

    // Fetch the site-wide tracker.
    $this->drupalGet('tracker');

    // Assert that all node titles are displayed.
    foreach ($nodes as $i => $node) {
      $this->assertText($node->label(), format_string('Node @i is displayed on the tracker listing pages.', array('@i' => $i)));
    }
    $this->assertText('1 new', 'New comment is counted on the tracker listing pages.');
  }

  /**
   * Tests that publish/unpublish works at admin/content/node.
   */
  function testTrackerAdminUnpublish() {
    \Drupal::moduleHandler()->install(array('views'));
    $admin_user = $this->drupalCreateUser(array('access content overview', 'administer nodes', 'bypass node access'));
    $this->drupalLogin($admin_user);

    $node = $this->drupalCreateNode(array(
      'title' => $this->randomName(),
    ));

    // Assert that the node is displayed.
    $this->drupalGet('tracker');
    $this->assertText($node->label(), 'A node is displayed on the tracker listing pages.');

    // Unpublish the node and ensure that it's no longer displayed.
    $edit = array(
      'action' => 'node_unpublish_action',
      'node_bulk_form[0]' => $node->id(),
    );
    $this->drupalPostForm('admin/content', $edit, t('Apply'));

    $this->drupalGet('tracker');
    $this->assertText(t('No content available.'), 'A node is displayed on the tracker listing pages.');
  }
}
