<?php

/**
 * @file
 * Contains \Drupal\forum\Tests\Views\ForumIntegrationTest.
 */

namespace Drupal\forum\Tests\Views;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the forum integration into views.
 */
class ForumIntegrationTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('forum_test_views');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_forum_index');

  public static function getInfo() {
    return array(
      'name' => 'Forum: Views data',
      'description' => 'Tests the forum integration into views.',
      'group' => 'Views module integration',
    );
  }

  protected function setUp() {
    parent::setUp();

    ViewTestData::importTestViews(get_class($this), array('forum_test_views'));
  }


  /**
   * Tests the integration.
   */
  public function testForumIntegration() {
    // Create a forum.
    $entity_manager = $this->container->get('plugin.manager.entity');
    $term = $entity_manager->getStorageController('taxonomy_term')->create(array('vid' => 'forums'));
    $term->save();

    $comment_storage_controller = $entity_manager->getStorageController('comment');

    // Create some nodes which are part of this forum with some comments.
    $nodes = array();
    for ($i = 0; $i < 3; $i++) {
      $node = $this->drupalCreateNode(array('type' => 'forum', 'taxonomy_forums' => array($term->id()), 'sticky' => $i == 0 ? NODE_STICKY : NODE_NOT_STICKY));
      $nodes[] = $node;
    }

    $comments = array();
    foreach ($nodes as $index => $node) {
      for ($i = 0; $i <= $index; $i++) {
        $comment = $comment_storage_controller->create(array('node_type' => 'node_type_forum', 'nid' => $node->id()));
        $comment->save();
        $comments[$comment->get('nid')->target_id][$comment->id()] = $comment;
      }
    }

    $view = views_get_view('test_forum_index');
    $this->executeView($view);

    $expected_result = array();
    $expected_result[] = array(
      'nid' => $nodes[0]->id(),
      'sticky' => NODE_STICKY,
      'comment_count' => 1.
    );
    $expected_result[] = array(
      'nid' => $nodes[1]->id(),
      'sticky' => NODE_NOT_STICKY,
      'comment_count' => 2.
    );
    $expected_result[] = array(
      'nid' => $nodes[2]->id(),
      'sticky' => NODE_NOT_STICKY,
      'comment_count' => 3.
    );
    $column_map = array(
      'nid' => 'nid',
      'forum_index_sticky' => 'sticky',
      'forum_index_comment_count' => 'comment_count',
    );
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

}
