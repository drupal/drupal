<?php

namespace Drupal\Tests\forum\Functional\Views;

use Drupal\node\NodeInterface;
use Drupal\views\Views;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the forum integration into views.
 *
 * @group forum
 */
class ForumIntegrationTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['forum_test_views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_forum_index'];

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(get_class($this), ['forum_test_views']);
  }

  /**
   * Tests the integration.
   */
  public function testForumIntegration() {
    // Create a forum.
    $entity_type_manager = $this->container->get('entity_type.manager');
    $term = $entity_type_manager->getStorage('taxonomy_term')->create(['vid' => 'forums', 'name' => $this->randomMachineName()]);
    $term->save();

    $comment_storage = $entity_type_manager->getStorage('comment');

    // Create some nodes which are part of this forum with some comments.
    $nodes = [];
    for ($i = 0; $i < 3; $i++) {
      $node = $this->drupalCreateNode(['type' => 'forum', 'taxonomy_forums' => [$term->id()], 'sticky' => $i == 0 ? NodeInterface::STICKY : NodeInterface::NOT_STICKY]);
      $nodes[] = $node;
    }

    $account = $this->drupalCreateUser(['skip comment approval']);
    $this->drupalLogin($account);

    $comments = [];
    foreach ($nodes as $index => $node) {
      for ($i = 0; $i <= $index; $i++) {
        $comment = $comment_storage->create(['entity_type' => 'node', 'entity_id' => $node->id(), 'field_name' => 'comment_forum']);
        $comment->save();
        $comments[$comment->get('entity_id')->target_id][$comment->id()] = $comment;
      }
    }

    $view = Views::getView('test_forum_index');
    $this->executeView($view);

    $expected_result = [];
    $expected_result[] = [
      'nid' => $nodes[0]->id(),
      'sticky' => NodeInterface::STICKY,
      'comment_count' => 1.,
    ];
    $expected_result[] = [
      'nid' => $nodes[1]->id(),
      'sticky' => NodeInterface::NOT_STICKY,
      'comment_count' => 2.,
    ];
    $expected_result[] = [
      'nid' => $nodes[2]->id(),
      'sticky' => NodeInterface::NOT_STICKY,
      'comment_count' => 3.,
    ];
    $column_map = [
      'nid' => 'nid',
      'forum_index_sticky' => 'sticky',
      'forum_index_comment_count' => 'comment_count',
    ];
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

}
