<?php

namespace Drupal\comment\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests comments as part of an RSS feed.
 *
 * @group comment
 */
class CommentRssTest extends CommentTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('views');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Setup the rss view display.
    EntityViewDisplay::create([
      'status' => TRUE,
      'targetEntityType' => 'node',
      'bundle' => 'article',
      'mode' => 'rss',
      'content' => ['links' => ['weight' => 100]],
    ])->save();
  }

  /**
   * Tests comments as part of an RSS feed.
   */
  function testCommentRss() {
    // Find comment in RSS feed.
    $this->drupalLogin($this->webUser);
    $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName());
    $this->drupalGet('rss.xml');

    $cache_contexts = [
      'languages:language_interface',
      'theme',
      'url.site',
      'user.node_grants:view',
      'user.permissions',
      'timezone',
    ];
    $this->assertCacheContexts($cache_contexts);

    $cache_context_tags = \Drupal::service('cache_contexts_manager')->convertTokensToKeys($cache_contexts)->getCacheTags();
    $this->assertCacheTags(Cache::mergeTags($cache_context_tags, [
      'config:views.view.frontpage',
      'node:1', 'node_list',
      'node_view',
      'user:3',
    ]));

    $raw = '<comments>' . $this->node->url('canonical', array('fragment' => 'comments', 'absolute' => TRUE)) . '</comments>';
    $this->assertRaw($raw, 'Comments as part of RSS feed.');

    // Hide comments from RSS feed and check presence.
    $this->node->set('comment', CommentItemInterface::HIDDEN);
    $this->node->save();
    $this->drupalGet('rss.xml');
    $this->assertNoRaw($raw, 'Hidden comments is not a part of RSS feed.');
  }

}
