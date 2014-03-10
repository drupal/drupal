<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentRssTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;

/**
 * Tests for Comment module integration with RSS feeds.
 */
class CommentRssTest extends CommentTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views');

  public static function getInfo() {
    return array(
      'name' => 'Comment RSS',
      'description' => 'Test comments as part of an RSS feed.',
      'group' => 'Comment',
    );
  }

  /**
   * Tests comments as part of an RSS feed.
   */
  function testCommentRss() {
    // Find comment in RSS feed.
    $this->drupalLogin($this->web_user);
    $this->postComment($this->node, $this->randomName(), $this->randomName());
    $this->drupalGet('rss.xml');
    $raw = '<comments>' . url('node/' . $this->node->id(), array('fragment' => 'comments', 'absolute' => TRUE)) . '</comments>';
    $this->assertRaw($raw, 'Comments as part of RSS feed.');

    // Hide comments from RSS feed and check presence.
    $this->node->set('comment', CommentItemInterface::HIDDEN);
    $this->node->save();
    $this->drupalGet('rss.xml');
    $this->assertNoRaw($raw, 'Hidden comments is not a part of RSS feed.');
  }
}
