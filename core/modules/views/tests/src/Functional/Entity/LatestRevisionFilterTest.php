<?php

namespace Drupal\Tests\views\Functional\Entity;

use Drupal\node\Entity\Node;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Tests the 'Latest revision' filter.
 *
 * @group views
 */
class LatestRevisionFilterTest extends ViewTestBase {

  /**
   * An array of node revisions.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $allRevisions = [];

  /**
   * An array of node revisions.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $latestRevisions = [];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_latest_revision_filter'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);

    // Create a node that goes through various default/pending revision stages.
    $node = Node::create([
      'title' => 'First node - v1 - default',
      'type' => 'article',
    ]);
    $node->save();
    $this->allRevisions[$node->getRevisionId()] = $node;

    $node->setTitle('First node - v2 - pending');
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(FALSE);
    $node->save();
    $this->allRevisions[$node->getRevisionId()] = $node;

    $node->setTitle('First node - v3 - default');
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(TRUE);
    $node->save();
    $this->allRevisions[$node->getRevisionId()] = $node;

    $node->setTitle('First node - v4 - pending');
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(TRUE);
    $node->save();
    $this->allRevisions[$node->getRevisionId()] = $node;
    $this->latestRevisions[$node->getRevisionId()] = $node;

    // Create a node that has a default and a pending revision.
    $node = Node::create([
      'title' => 'Second node - v1 - default',
      'type' => 'article',
    ]);
    $node->save();
    $this->allRevisions[$node->getRevisionId()] = $node;

    $node->setTitle('Second node - v2 - pending');
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(FALSE);
    $node->save();
    $this->allRevisions[$node->getRevisionId()] = $node;
    $this->latestRevisions[$node->getRevisionId()] = $node;

    // Create a node that only has a default revision.
    $node = Node::create([
      'title' => 'Third node - v1 - default',
      'type' => 'article',
    ]);
    $node->save();
    $this->allRevisions[$node->getRevisionId()] = $node;
    $this->latestRevisions[$node->getRevisionId()] = $node;

    // Create a node that only has a pending revision.
    $node = Node::create([
      'title' => 'Fourth node - v1 - pending',
      'type' => 'article',
    ]);
    $node->isDefaultRevision(FALSE);
    $node->save();
    $this->allRevisions[$node->getRevisionId()] = $node;
    $this->latestRevisions[$node->getRevisionId()] = $node;
  }

  /**
   * Tests the 'Latest revision' filter.
   */
  public function testLatestRevisionFilter() {
    $view = Views::getView('test_latest_revision_filter');

    $this->executeView($view);

    // Check that we have all the results.
    $this->assertCount(count($this->latestRevisions), $view->result);

    $expected = $not_expected = [];
    foreach ($this->allRevisions as $revision_id => $revision) {
      if (isset($this->latestRevisions[$revision_id])) {
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
    $this->assertNotInResultSet($view, $not_expected, 'Non-latest revisions are not shown by the view.');
    $view->destroy();
  }

  /**
   * Verifies that a list of revision IDs are not in the result.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   An executed View.
   * @param array $not_expected_revision_ids
   *   An array of revision IDs which should not be part of the result set.
   * @param string $message
   *   (optional) A custom message to display with the assertion.
   */
  protected function assertNotInResultSet(ViewExecutable $view, array $not_expected_revision_ids, $message = '') {
    $found_revision_ids = array_filter($view->result, function ($row) use ($not_expected_revision_ids) {
      return in_array($row->vid, $not_expected_revision_ids);
    });
    $this->assertFalse($found_revision_ids, $message);
  }

}
