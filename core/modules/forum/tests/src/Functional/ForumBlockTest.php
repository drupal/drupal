<?php

namespace Drupal\Tests\forum\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\comment\Entity\Comment;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the forum blocks.
 *
 * @group forum
 */
class ForumBlockTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['forum', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with various administrative privileges.
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    // Create users.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer blocks',
      'administer nodes',
      'create forum content',
      'post comments',
      'skip comment approval',
    ]);
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
      $this->assertLink($topic, 0, new FormattableMarkup('Forum topic @topic found in the "New forum topics" block.', ['@topic' => $topic]));
    }

    // Configure the new forum topics block to only show 2 topics.
    $block->getPlugin()->setConfigurationValue('block_count', 2);
    $block->save();

    $this->drupalGet('');
    // We expect only the 2 most recent forum topics to appear in the "New forum
    // topics" block.
    for ($index = 0; $index < 5; $index++) {
      if (in_array($index, [3, 4])) {
        $this->assertLink($topics[$index], 0, new FormattableMarkup('Forum topic @topic found in the "New forum topics" block.', ['@topic' => $topics[$index]]));
      }
      else {
        $this->assertNoText($topics[$index], new FormattableMarkup('Forum topic @topic not found in the "New forum topics" block.', ['@topic' => $topics[$index]]));
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
      $comment = Comment::create([
        'entity_id' => $node->id(),
        'field_name' => 'comment_forum',
        'entity_type' => 'node',
        'node_type' => 'node_type_' . $node->bundle(),
        'subject' => $this->randomString(20),
        'comment_body' => $this->randomString(256),
        'created' => $date->getTimestamp(),
      ]);
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
        $this->assertLink($topics[$index], 0, new FormattableMarkup('Forum topic @topic found in the "Active forum topics" block.', ['@topic' => $topics[$index]]));
      }
      else {
        $this->assertNoText($topics[$index], new FormattableMarkup('Forum topic @topic not found in the "Active forum topics" block.', ['@topic' => $topics[$index]]));
      }
    }

    // Configure the active forum block to only show 2 topics.
    $block->getPlugin()->setConfigurationValue('block_count', 2);
    $block->save();

    $this->drupalGet('');

    // We expect only the 2 forum topics with most recent comments to appear in
    // the "Active forum topics" block.
    for ($index = 0; $index < 10; $index++) {
      if (in_array($index, [3, 4])) {
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
    $topics = [];
    $date = new DrupalDateTime();
    $date->modify('-24 hours');

    for ($index = 0; $index < $count; $index++) {
      // Generate a random subject/body.
      $title = $this->randomMachineName(20);
      $body = $this->randomMachineName(200);
      // Forum posts are ordered by timestamp, so force a unique timestamp by
      // changing the date.
      $date->modify('+1 minute');

      $edit = [
        'title[0][value]' => $title,
        'body[0][value]' => $body,
        // Forum posts are ordered by timestamp, so force a unique timestamp by
        // adding the index.
        'created[0][value][date]' => $date->format('Y-m-d'),
        'created[0][value][time]' => $date->format('H:i:s'),
      ];

      // Create the forum topic, preselecting the forum ID via a URL parameter.
      $this->drupalPostForm('node/add/forum', $edit, t('Save'), ['query' => ['forum_id' => 1]]);
      $topics[] = $title;
    }

    return $topics;
  }

}
