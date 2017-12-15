<?php

namespace Drupal\Tests\migrate_drupal\Traits;

use Drupal\aggregator\Entity\Feed;
use Drupal\aggregator\Entity\Item;
use Drupal\block_content\Entity\BlockContent;
use Drupal\comment\Entity\Comment;
use Drupal\file\Entity\File;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * Provides helper methods for creating test content.
 */
trait CreateTestContentEntitiesTrait {

  /**
   * Gets required modules.
   *
   * @return array
   */
  protected function getRequiredModules() {
    return [
      'aggregator',
      'block_content',
      'comment',
      'field',
      'file',
      'link',
      'menu_link_content',
      'migrate_drupal',
      'node',
      'options',
      'system',
      'taxonomy',
      'text',
      'user',
    ];
  }

  /**
   * Install required entity schemas.
   */
  protected function installEntitySchemas() {
    $this->installEntitySchema('aggregator_feed');
    $this->installEntitySchema('aggregator_item');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
  }

  /**
   * Create several pieces of generic content.
   */
  protected function createContent() {
    // Create an aggregator feed.
    $feed = Feed::create([
      'title' => 'feed',
      'url' => 'http://www.example.com',
    ]);
    $feed->save();

    // Create an aggregator feed item.
    $item = Item::create([
      'title' => 'feed item',
      'fid' => $feed->id(),
      'link' => 'http://www.example.com',
    ]);
    $item->save();

    // Create a block content.
    $block = BlockContent::create([
      'info' => 'block',
      'type' => 'block',
    ]);
    $block->save();

    // Create a node.
    $node = Node::create([
      'type' => 'page',
      'title' => 'page',
    ]);
    $node->save();

    // Create a comment.
    $comment = Comment::create([
      'comment_type' => 'comment',
      'field_name' => 'comment',
      'entity_type' => 'node',
      'entity_id' => $node->id(),
    ]);
    $comment->save();

    // Create a file.
    $file = File::create([
      'uri' => 'public://example.txt',
    ]);
    $file->save();

    // Create a menu link.
    $menu_link = MenuLinkContent::create([
      'title' => 'menu link',
      'link' => ['uri' => 'http://www.example.com'],
      'menu_name' => 'tools',
    ]);
    $menu_link->save();

    // Create a taxonomy term.
    $term = Term::create([
      'name' => 'term',
      'vid' => 'term',
    ]);
    $term->save();

    // Create a user.
    $user = User::create([
      'uid' => 2,
      'name' => 'user',
      'mail' => 'user@example.com',
    ]);
    $user->save();
  }

}
