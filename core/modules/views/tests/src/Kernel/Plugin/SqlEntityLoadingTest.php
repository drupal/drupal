<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the loading of entities and entity revisions.
 *
 * @group views
 *
 * @see \Drupal\views\Plugin\views\query\Sql
 */
class SqlEntityLoadingTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['base_and_revision'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', 'node_access');
  }

  public function testViewWithNonDefaultPendingRevision() {
    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $node_type->save();

    $node = Node::create([
      'type' => 'page',
      'title' => 'test title',
    ]);
    $node->save();

    // Creates the first revision, which is set as default.
    $revision = clone $node;
    $revision->setNewRevision(TRUE);
    $revision->isDefaultRevision(TRUE);
    $revision->save();

    // Creates the second revision, which is not set as default.
    $revision2 = clone $node;
    $revision2->setNewRevision(TRUE);
    $revision2->isDefaultRevision(FALSE);
    $revision2->save();

    $view = Views::getView('base_and_revision');
    $view->execute();

    $expected = [
      [
        'nid' => $node->id(),
        // The default revision ID.
        'vid_1' => $revision->getRevisionId(),
        // The latest revision ID.
        'vid' => $revision2->getRevisionId(),
      ],
    ];
    $this->assertIdenticalResultset($view, $expected, [
      'node_field_data_node_field_revision_nid' => 'nid',
      'vid_1' => 'vid_1',
      'vid' => 'vid',
    ]);
  }

}
