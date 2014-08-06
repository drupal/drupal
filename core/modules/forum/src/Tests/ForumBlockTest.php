<?php

/**
 * @file
 * Definition of Drupal\forum\Tests\ForumBlockTest.
 */

namespace Drupal\forum\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Tests the forum blocks.
 *
 * @group forum
 */
class ForumBlockTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('forum', 'block');

  /**
   * A user with various administrative privileges.
   */
  protected $adminUser;

  function setUp() {
    parent::setUp();

    // Create users.
    $this->adminUser = $this->drupalCreateUser(array(
      'access administration pages',
      'administer blocks',
      'administer nodes',
      'create forum content',
      'post comments',
      'skip comment approval',
    ));
  }

  /**
   * Tests the "New forum topics" block.
   */
  public function testNewForumTopicsBlock() {
    $this->drupalLogin($this->adminUser);

    // Enable the new forum topics block.
    $block = $this->drupalPlaceBlock('forum_new_block');
    $this->drupalGet('');

    // Create 5 forum topics.
    $topics = $this->createForumTopics();


    $this->assertLink(t('More'), 0, 'New forum topics block has a "more"-link.');
    $this->assertLinkByHref('forum', 0, 'New forum topics block has a "more"-link.');

    // We expect all 5 forum topics to appear in the "New forum topics" block.
    foreach ($topics as $topic) {
      $this->assertLink($topic, 0, format_string('Forum topic @topic found in the "New forum topics" block.', array('@topic' => $topic)));
    }

    // Configure the new forum topics block to only show 2 topics.
    $block->getPlugin()->setConfigurationValue('block_count', 2);
    $block->save();

    $this->drupalGet('');
    // We expect only the 2 most recent forum topics to appear in the "New forum
    // topics" block.
    for ($index = 0; $index < 5; $index++) {
      if (in_array($index, array(3, 4))) {
        $this->assertLink($topics[$index], 0, format_string('Forum topic @topic found in the "New forum topics" block.', array('@topic' => $topics[$index])));
      }
      else {
        $this->assertNoText($topics[$index], format_string('Forum topic @topic not found in the "New forum topics" block.', array('@topic' => $topics[$index])));
      }
    }
  }

  /**
   * Tests the "Active forum topics" block.
   */
  public function testActiveForumTopicsBlock() {
    $this->drupalLogin($this->adminUser);

    // Create 10 forum topics.
    $topics = $this->createForumTopics(10);

    // Comment on the first 5 topics.
    $date = new DrupalDateTime();
    for ($index = 0; $index < 5; $index++) {
      // Get the node from the topic title.
      $node = $this->drupalGetNodeByTitle($topics[$index]);
      $date->modify('+1 minute');
      $comment = entity_create('comment', array(
        'entity_id' => $node->id(),
        'field_name' => 'comment_forum',
        'entity_type' => 'node',
        'node_type' => 'node_type_' . $node->bundle(),
        'subject' => $this->randomString(20),
        'comment_body' => $this->randomString(256),
        'created' => $date->getTimestamp(),
      ));
      $comment->save();
    }

    // Enable the block.
    $block = $this->drupalPlaceBlock('forum_active_block');
    $this->drupalGet('');
    $this->assertLink(t('More'), 0, 'Active forum topics block has a "more"-link.');
    $this->assertLinkByHref('forum', 0, 'Active forum topics block has a "more"-link.');

    // We expect the first 5 forum topics to appear in the "Active forum topics"
    // block.
    $this->drupalGet('<front>');
    for ($index = 0; $index < 10; $index++) {
      if ($index < 5) {
        $this->assertLink($topics[$index], 0, format_string('Forum topic @topic found in the "Active forum topics" block.', array('@topic' => $topics[$index])));
      }
      else {
        $this->assertNoText($topics[$index], format_string('Forum topic @topic not found in the "Active forum topics" block.', array('@topic' => $topics[$index])));
      }
    }

    // Configure the active forum block to only show 2 topics.
    $block->getPlugin()->setConfigurationValue('block_count', 2);
    $block->save();

    $this->drupalGet('');

    // We expect only the 2 forum topics with most recent comments to appear in
    // the "Active forum topics" block.
    for ($index = 0; $index < 10; $index++) {
      if (in_array($index, array(3, 4))) {
        $this->assertLink($topics[$index], 0, 'Forum topic found in the "Active forum topics" block.');
      }
      else {
        $this->assertNoText($topics[$index], 'Forum topic not found in the "Active forum topics" block.');
      }
    }
  }

  /**
   * Creates a forum topic.
   *
   * @return string
   *   The title of the newly generated topic.
   */
  protected function createForumTopics($count = 5) {
    $topics = array();
    $date = new DrupalDateTime();
    $date->modify('-24 hours');

    for ($index = 0; $index < $count; $index++) {
      // Generate a random subject/body.
      $title = $this->randomMachineName(20);
      $body = $this->randomMachineName(200);
      // Forum posts are ordered by timestamp, so force a unique timestamp by
      // changing the date.
      $date->modify('+1 minute');

      $edit = array(
        'title[0][value]' => $title,
        'body[0][value]' => $body,
        // Forum posts are ordered by timestamp, so force a unique timestamp by
        // adding the index.
        'created[date]' => $date->format('Y-m-d'),
        'created[time]' => $date->format('H:i:s'),
      );

      // Create the forum topic, preselecting the forum ID via a URL parameter.
      $this->drupalPostForm('node/add/forum', $edit, t('Save and publish'), array('query' => array('forum_id' => 1)));
      $topics[] = $title;
    }

    return $topics;
  }
}
