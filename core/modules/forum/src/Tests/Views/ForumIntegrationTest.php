<?php

/**
 * @file
 * Contains \Drupal\forum\Tests\Views\ForumIntegrationTest.
 */

namespace Drupal\forum\Tests\Views;

use Drupal\views\Views;
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

    ViewTestData::createTestViews(get_class($this), array('forum_test_views'));
  }


  /**
   * Tests the integration.
   */
  public function testForumIntegration() {
    // Create a forum.
    $entity_manager = $this->container->get('entity.manager');
    $term = $entity_manager->getStorage('taxonomy_term')->create(array('vid' => 'forums'));
    $term->save();

    $comment_storage = $entity_manager->getStorage('comment');

    // Create some nodes which are part of this forum with some comments.
    $nodes = array();
    for ($i = 0; $i < 3; $i++) {
      $node = $this->drupalCreateNode(array('type' => 'forum', 'taxonomy_forums' => array($term->id()), 'sticky' => $i == 0 ? NODE_STICKY : NODE_NOT_STICKY));
      $nodes[] = $node;
    }

    $account = $this->drupalCreateUser(array('skip comment approval'));
    $this->drupalLogin($account);

    $comments = array();
    foreach ($nodes as $index => $node) {
      for ($i = 0; $i <= $index; $i++) {
        $comment = $comment_storage->create(array('entity_type' => 'node', 'entity_id' => $node->id(), 'field_name' => 'comment_forum'));
        $comment->save();
        $comments[$comment->get('entity_id')->target_id][$comment->id()] = $comment;
      }
    }

    $view = Views::getView('test_forum_index');
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
