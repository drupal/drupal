<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel\Views;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the node_uid_revision handler.
 *
 * @group node
 */
class FilterUidRevisionTest extends KernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;
  use ViewResultAssertionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'node',
    'node_test_views',
    'system',
    'user',
    'views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_filter_node_uid_revision'];

  /**
   * Tests the node_uid_revision filter.
   */
  public function testFilter(): void {
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['filter']);
    ViewTestData::createTestViews(static::class, ['node_test_views']);

    $author = $this->createUser();
    $no_author = $this->createUser();

    $expected_result = [];
    // Create one node, with the author as the node author.
    $node = $this->createNode(['uid' => $author->id()]);
    $expected_result[] = ['nid' => $node->id()];
    // Create one node of which an additional revision author will be the
    // author.
    $node = $this->createNode(['revision_uid' => $no_author->id()]);
    $expected_result[] = ['nid' => $node->id()];
    $revision = clone $node;
    // Force to add a new revision.
    $revision->set('vid', NULL);
    $revision->set('revision_uid', $author->id());
    $revision->save();

    // Create one  node on which the author has neither authorship of revisions
    // or the main node.
    $this->createNode(['uid' => $no_author->id()]);

    $view = Views::getView('test_filter_node_uid_revision');
    $view->initHandlers();
    $view->filter['uid_revision']->value = [$author->id()];

    $view->preview();
    $this->assertIdenticalResultset($view, $expected_result, ['nid' => 'nid'], 'Make sure that the view only returns nodes which match either the node or the revision author.');
  }

}
