<?php

namespace Drupal\Tests\node\Kernel\Views;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Ensures that the revision create time can be accessed in views.
 *
 * @group views
 */
class RevisionCreateTimestampTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node_test_views', 'node', 'views', 'user'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_node_revision_timestamp'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    if ($import_test_views) {
      ViewTestData::createTestViews(static::class, ['node_test_views']);
    }
  }

  public function testRevisionCreateTimestampView() {
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();
    $node = Node::create([
      'title' => 'Test node',
      'type' => 'article',
      'revision_timestamp' => 1000,
    ]);
    $node->save();

    $node->setRevisionCreationTime(1200);
    $node->setNewRevision(TRUE);
    $node->save();

    $node->setRevisionCreationTime(1400);
    $node->setNewRevision(TRUE);
    $node->save();

    $view = Views::getView('test_node_revision_timestamp');
    $this->executeView($view);

    $this->assertIdenticalResultset($view, [
      ['vid' => 3, 'revision_timestamp' => 1400],
      ['vid' => 2, 'revision_timestamp' => 1200],
      ['vid' => 1, 'revision_timestamp' => 1000],
    ], ['vid' => 'vid', 'revision_timestamp' => 'revision_timestamp']);
  }

}
