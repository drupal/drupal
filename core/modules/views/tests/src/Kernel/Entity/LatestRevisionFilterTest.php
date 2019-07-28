<?php

namespace Drupal\Tests\views\Kernel\Entity;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Tests the 'Latest revision' filter.
 *
 * @group views
 */
class LatestRevisionFilterTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_latest_revision_filter'];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

  /**
   * Tests the 'Latest revision' filter.
   */
  public function testLatestRevisionFilter() {
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    NodeType::create(['type' => 'article'])->save();

    // Create a node that goes through various default/pending revision stages.
    $node = Node::create([
      'title' => 'First node - v1 - default',
      'type' => 'article',
    ]);
    $node->save();
    $all_revisions[$node->getRevisionId()] = $node;

    $node->setTitle('First node - v2 - pending');
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(FALSE);
    $node->save();
    $all_revisions[$node->getRevisionId()] = $node;

    $node->setTitle('First node - v3 - default');
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(TRUE);
    $node->save();
    $all_revisions[$node->getRevisionId()] = $node;

    $node->setTitle('First node - v4 - pending');
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(TRUE);
    $node->save();
    $all_revisions[$node->getRevisionId()] = $node;
    $latest_revisions[$node->getRevisionId()] = $node;

    // Create a node that has a default and a pending revision.
    $node = Node::create([
      'title' => 'Second node - v1 - default',
      'type' => 'article',
    ]);
    $node->save();
    $all_revisions[$node->getRevisionId()] = $node;

    $node->setTitle('Second node - v2 - pending');
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(FALSE);
    $node->save();
    $all_revisions[$node->getRevisionId()] = $node;
    $latest_revisions[$node->getRevisionId()] = $node;

    // Create a node that only has a default revision.
    $node = Node::create([
      'title' => 'Third node - v1 - default',
      'type' => 'article',
    ]);
    $node->save();
    $all_revisions[$node->getRevisionId()] = $node;
    $latest_revisions[$node->getRevisionId()] = $node;

    // Create a node that only has a pending revision.
    $node = Node::create([
      'title' => 'Fourth node - v1 - pending',
      'type' => 'article',
    ]);
    $node->isDefaultRevision(FALSE);
    $node->save();
    $all_revisions[$node->getRevisionId()] = $node;
    $latest_revisions[$node->getRevisionId()] = $node;

    $view = Views::getView('test_latest_revision_filter');

    $this->executeView($view);

    // Check that we have all the results.
    $this->assertCount(count($latest_revisions), $view->result);

    $expected = $not_expected = [];
    foreach ($all_revisions as $revision_id => $revision) {
      if (isset($latest_revisions[$revision_id])) {
        $expected[] = [
          'vid' => $revision_id,
          'title' => $revision->label(),
        ];
      }
      else {
        $not_expected[] = $revision_id;
      }
    }
    $this->assertIdenticalResultset($view, $expected, ['vid' => 'vid', 'title' => 'title'], 'The test view only shows the latest revisions.');
    $this->assertNotInResultSet($view, $not_expected);
    $view->destroy();
  }

  /**
   * Asserts that a list of revision IDs are not in the result.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   An executed View.
   * @param array $not_expected_revision_ids
   *   An array of revision IDs which should not be part of the result set.
   */
  protected function assertNotInResultSet(ViewExecutable $view, array $not_expected_revision_ids) {
    $found_revision_ids = array_filter($view->result, function ($row) use ($not_expected_revision_ids) {
      return in_array($row->vid, $not_expected_revision_ids);
    });
    $this->assertFalse($found_revision_ids);
  }

}
