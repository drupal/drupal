<?php

/**
 * @file
 * Contains \Drupal\tracker\Tests\TrackerTest.
 */

namespace Drupal\tracker\Tests;

use Drupal\comment\CommentInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Cache\Cache;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Create and delete nodes and check for their display in the tracker listings.
 *
 * @group tracker
 */
class TrackerTest extends WebTestBase {

  use CommentTestTrait;
  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'comment', 'tracker', 'history', 'node_test'];

  /**
   * The main user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A second user that will 'create' comments and nodes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $otherUser;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $permissions = array('access comments', 'create page content', 'post comments', 'skip comment approval');
    $this->user = $this->drupalCreateUser($permissions);
    $this->otherUser = $this->drupalCreateUser($permissions);
    $this->addDefaultCommentField('node', 'page');
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, array(
      'access content',
      'access user profiles',
    ));
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'page_tabs_block']);
    $this->drupalPlaceBlock('local_actions_block', ['id' => 'page_actions_block']);
  }

  /**
   * Tests for the presence of nodes on the global tracker listing.
   */
  function testTrackerAll() {
    $this->drupalLogin($this->user);

    $unpublished = $this->drupalCreateNode(array(
      'title' => $this->randomMachineName(8),
      'status' => 0,
    ));
    $published = $this->drupalCreateNode(array(
      'title' => $this->randomMachineName(8),
      'status' => 1,
    ));

    $this->drupalGet('activity');
    $this->assertNoText($unpublished->label(), 'Unpublished node does not show up in the tracker listing.');
    $this->assertText($published->label(), 'Published node shows up in the tracker listing.');
    $this->assertLink(t('My recent content'), 0, 'User tab shows up on the global tracker page.');

    // Assert cache contexts, specifically the pager and node access contexts.
    $this->assertCacheContexts(['languages:language_interface', 'route.name', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'url.query_args.pagers:0', 'user.node_grants:view', 'user']);
    // Assert cache tags for the action/tabs blocks, visible node, and node list
    // cache tag.
    $expected_tags = Cache::mergeTags($published->getCacheTags(), $published->getOwner()->getCacheTags());
    $block_tags = [
      'block_view',
      'config:block.block.page_actions_block',
      'config:block.block.page_tabs_block',
      'config:block_list',
    ];
    $expected_tags = Cache::mergeTags($expected_tags, $block_tags);
    $additional_tags = [
      'node_list',
      'rendered',
    ];
    $expected_tags = Cache::mergeTags($expected_tags, $additional_tags);
    $this->assertCacheTags($expected_tags);

    // Delete a node and ensure it no longer appears on the tracker.
    $published->delete();
    $this->drupalGet('activity');
    $this->assertNoText($published->label(), 'Deleted node does not show up in the tracker listing.');

    // Test proper display of time on activity page when comments are disabled.
    // Disable comments.
    FieldStorageConfig::loadByName('node', 'comment')->delete();
    $node = $this->drupalCreateNode([
      // This title is required to trigger the custom changed time set in the
      // node_test module. This is needed in order to ensure a sufficiently
      // large 'time ago' interval that isn't numbered in seconds.
      'title' => 'testing_node_presave',
      'status' => 1,
    ]);

    $this->drupalGet('activity');
    $this->assertText($node->label(), 'Published node shows up in the tracker listing.');
    $this->assertText(\Drupal::service('date.formatter')->formatTimeDiffSince($node->getChangedTime()), 'The changed time was displayed on the tracker listing.');
  }

  /**
   * Tests for the presence of nodes on a user's tracker listing.
   */
  function testTrackerUser() {
    $this->drupalLogin($this->user);

    $unpublished = $this->drupalCreateNode(array(
      'title' => $this->randomMachineName(8),
      'uid' => $this->user->id(),
      'status' => 0,
    ));
    $my_published = $this->drupalCreateNode(array(
      'title' => $this->randomMachineName(8),
      'uid' => $this->user->id(),
      'status' => 1,
    ));
    $other_published_no_comment = $this->drupalCreateNode(array(
      'title' => $this->randomMachineName(8),
      'uid' => $this->otherUser->id(),
      'status' => 1,
    ));
    $other_published_my_comment = $this->drupalCreateNode(array(
      'title' => $this->randomMachineName(8),
      'uid' => $this->otherUser->id(),
      'status' => 1,
    ));
    $comment = array(
      'subject[0][value]' => $this->randomMachineName(),
      'comment_body[0][value]' => $this->randomMachineName(20),
    );
    $this->drupalPostForm('comment/reply/node/' . $other_published_my_comment->id() . '/comment', $comment, t('Save'));

    $this->drupalGet('user/' . $this->user->id() . '/activity');
    $this->assertNoText($unpublished->label(), "Unpublished nodes do not show up in the user's tracker listing.");
    $this->assertText($my_published->label(), "Published nodes show up in the user's tracker listing.");
    $this->assertNoText($other_published_no_comment->label(), "Another user's nodes do not show up in the user's tracker listing.");
    $this->assertText($other_published_my_comment->label(), "Nodes that the user has commented on appear in the user's tracker listing.");

    // Assert cache contexts.
    $this->assertCacheContexts(['languages:language_interface', 'route.name', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'url.query_args.pagers:0', 'user', 'user.node_grants:view']);
    // Assert cache tags for the visible nodes (including owners) and node list
    // cache tag.
    $expected_tags = Cache::mergeTags($my_published->getCacheTags(), $my_published->getOwner()->getCacheTags());
    $expected_tags = Cache::mergeTags($expected_tags, $other_published_my_comment->getCacheTags());
    $expected_tags = Cache::mergeTags($expected_tags, $other_published_my_comment->getOwner()->getCacheTags());
    $block_tags = [
      'block_view',
      'config:block.block.page_actions_block',
      'config:block.block.page_tabs_block',
      'config:block_list',
    ];
    $expected_tags = Cache::mergeTags($expected_tags, $block_tags);
    $additional_tags = [
      'node_list',
      'rendered',
    ];
    $expected_tags = Cache::mergeTags($expected_tags, $additional_tags);

    $this->assertCacheTags($expected_tags);
    $this->assertCacheContexts(['languages:language_interface', 'route.name', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'url.query_args.pagers:0', 'user', 'user.node_grants:view']);

    $this->assertLink($my_published->label());
    $this->assertNoLink($unpublished->label());
    // Verify that title and tab title have been set correctly.
    $this->assertText('Activity', 'The user activity tab has the name "Activity".');
    $this->assertTitle(t('@name | @site', array('@name' => $this->user->getUsername(), '@site' => $this->config('system.site')->get('name'))), 'The user tracker page has the correct page title.');

    // Verify that unpublished comments are removed from the tracker.
    $admin_user = $this->drupalCreateUser(array('post comments', 'administer comments', 'access user profiles'));
    $this->drupalLogin($admin_user);
    $this->drupalPostForm('comment/1/edit', array('status' => CommentInterface::NOT_PUBLISHED), t('Save'));
    $this->drupalGet('user/' . $this->user->id() . '/activity');
    $this->assertNoText($other_published_my_comment->label(), 'Unpublished comments are not counted on the tracker listing.');
  }

  /**
   * Tests the metadata for the "new"/"updated" indicators.
   */
  function testTrackerHistoryMetadata() {
    $this->drupalLogin($this->user);

    // Create a page node.
    $edit = array(
      'title' => $this->randomMachineName(8),
    );
    $node = $this->drupalCreateNode($edit);

    // Verify that the history metadata is present.
    $this->drupalGet('activity');
    $this->assertHistoryMetadata($node->id(), $node->getChangedTime(), $node->getChangedTime());
    $this->drupalGet('activity/' . $this->user->id());
    $this->assertHistoryMetadata($node->id(), $node->getChangedTime(), $node->getChangedTime());
    $this->drupalGet('user/' . $this->user->id() . '/activity');
    $this->assertHistoryMetadata($node->id(), $node->getChangedTime(), $node->getChangedTime());

    // Add a comment to the page, make sure it is created after the node by
    // sleeping for one second, to ensure the last comment timestamp is
    // different from before.
    $comment = array(
      'subject[0][value]' => $this->randomMachineName(),
      'comment_body[0][value]' => $this->randomMachineName(20),
    );
    sleep(1);
    $this->drupalPostForm('comment/reply/node/' . $node->id() . '/comment', $comment, t('Save'));
    // Reload the node so that comment.module's hook_node_load()
    // implementation can set $node->last_comment_timestamp for the freshly
    // posted comment.
    $node = Node::load($node->id());

    // Verify that the history metadata is updated.
    $this->drupalGet('activity');
    $this->assertHistoryMetadata($node->id(), $node->getChangedTime(), $node->get('comment')->last_comment_timestamp);
    $this->drupalGet('activity/' . $this->user->id());
    $this->assertHistoryMetadata($node->id(), $node->getChangedTime(), $node->get('comment')->last_comment_timestamp);
    $this->drupalGet('user/' . $this->user->id() . '/activity');
    $this->assertHistoryMetadata($node->id(), $node->getChangedTime(), $node->get('comment')->last_comment_timestamp);

    // Log out, now verify that the metadata is still there, but the library is
    // not.
    $this->drupalLogout();
    $this->drupalGet('activity');
    $this->assertHistoryMetadata($node->id(), $node->getChangedTime(), $node->get('comment')->last_comment_timestamp, FALSE);
    $this->drupalGet('user/' . $this->user->id() . '/activity');
    $this->assertHistoryMetadata($node->id(), $node->getChangedTime(), $node->get('comment')->last_comment_timestamp, FALSE);
  }

  /**
   * Tests for ordering on a users tracker listing when comments are posted.
   */
  function testTrackerOrderingNewComments() {
    $this->drupalLogin($this->user);

    $node_one = $this->drupalCreateNode(array(
      'title' => $this->randomMachineName(8),
    ));

    $node_two = $this->drupalCreateNode(array(
      'title' => $this->randomMachineName(8),
    ));

    // Now get otherUser to track these pieces of content.
    $this->drupalLogin($this->otherUser);

    // Add a comment to the first page.
    $comment = array(
      'subject[0][value]' => $this->randomMachineName(),
      'comment_body[0][value]' => $this->randomMachineName(20),
    );
    $this->drupalPostForm('comment/reply/node/' . $node_one->id() . '/comment', $comment, t('Save'));

    // If the comment is posted in the same second as the last one then Drupal
    // can't tell the difference, so we wait one second here.
    sleep(1);

    // Add a comment to the second page.
    $comment = array(
      'subject[0][value]' => $this->randomMachineName(),
      'comment_body[0][value]' => $this->randomMachineName(20),
    );
    $this->drupalPostForm('comment/reply/node/' . $node_two->id() . '/comment', $comment, t('Save'));

    // We should at this point have in our tracker for otherUser:
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
      'subject[0][value]' => $this->randomMachineName(),
      'comment_body[0][value]' => $this->randomMachineName(20),
    );
    $this->drupalPostForm('comment/reply/node/' . $node_one->id() . '/comment', $comment, t('Save'));

    // Switch back to the otherUser and assert that the order has swapped.
    $this->drupalLogin($this->otherUser);
    $this->drupalGet('user/' . $this->otherUser->id() . '/activity');
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
        'title' => $this->randomMachineName(),
      );
      $nodes[$i] = $this->drupalCreateNode($edits[$i]);
    }

    // Add a comment to the last node as other user.
    $this->drupalLogin($this->otherUser);
    $comment = array(
      'subject[0][value]' => $this->randomMachineName(),
      'comment_body[0][value]' => $this->randomMachineName(20),
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
    $this->drupalGet('activity/' . $this->user->id());

    // Assert that all node titles are displayed.
    foreach ($nodes as $i => $node) {
      $this->assertText($node->label(), format_string('Node @i is displayed on the tracker listing pages.', array('@i' => $i)));
    }

    // Fetch the site-wide tracker.
    $this->drupalGet('activity');

    // Assert that all node titles are displayed.
    foreach ($nodes as $i => $node) {
      $this->assertText($node->label(), format_string('Node @i is displayed on the tracker listing pages.', array('@i' => $i)));
    }
  }

  /**
   * Tests that publish/unpublish works at admin/content/node.
   */
  function testTrackerAdminUnpublish() {
    \Drupal::service('module_installer')->install(array('views'));
    \Drupal::service('router.builder')->rebuild();
    $admin_user = $this->drupalCreateUser(array('access content overview', 'administer nodes', 'bypass node access'));
    $this->drupalLogin($admin_user);

    $node = $this->drupalCreateNode(array(
      'title' => $this->randomMachineName(),
    ));

    // Assert that the node is displayed.
    $this->drupalGet('activity');
    $this->assertText($node->label(), 'A node is displayed on the tracker listing pages.');

    // Unpublish the node and ensure that it's no longer displayed.
    $edit = array(
      'action' => 'node_unpublish_action',
      'node_bulk_form[0]' => $node->id(),
    );
    $this->drupalPostForm('admin/content', $edit, t('Apply'));

    $this->drupalGet('activity');
    $this->assertText(t('No content available.'), 'A node is displayed on the tracker listing pages.');
  }

  /**
   * Passes if the appropriate history metadata exists.
   *
   * Verify the data-history-node-id, data-history-node-timestamp and
   * data-history-node-last-comment-timestamp attributes, which are used by the
   * drupal.tracker-history library to add the appropriate "new" and "updated"
   * indicators, as well as the "x new" replies link to the tracker.
   * We do this in JavaScript to prevent breaking the render cache.
   *
   * @param $node_id
   *   A node ID, that must exist as a data-history-node-id attribute
   * @param $node_timestamp
   *   A node timestamp, that must exist as a data-history-node-timestamp
   *   attribute.
   * @param $node_last_comment_timestamp
   *   A node's last comment timestamp, that must exist as a
   *   data-history-node-last-comment-timestamp attribute.
   * @param bool $library_is_present
   *   Whether the drupal.tracker-history library should be present or not.
   */
  function assertHistoryMetadata($node_id, $node_timestamp, $node_last_comment_timestamp, $library_is_present = TRUE) {
    $settings = $this->getDrupalSettings();
    $this->assertIdentical($library_is_present, isset($settings['ajaxPageState']) && in_array('tracker/history', explode(',', $settings['ajaxPageState']['libraries'])), 'drupal.tracker-history library is present.');
    $this->assertIdentical(1, count($this->xpath('//table/tbody/tr/td[@data-history-node-id="' . $node_id . '" and @data-history-node-timestamp="' . $node_timestamp . '"]')), 'Tracker table cell contains the data-history-node-id and data-history-node-timestamp attributes for the node.');
    $this->assertIdentical(1, count($this->xpath('//table/tbody/tr/td[@data-history-node-last-comment-timestamp="' . $node_last_comment_timestamp . '"]')), 'Tracker table cell contains the data-history-node-last-comment-timestamp attribute for the node.');
  }

}
