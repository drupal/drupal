<?php

namespace Drupal\Tests\node\Kernel\Views;

use Drupal\node\Entity\Node;
use Drupal\simpletest\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the argument_node_uid_revision handler.
 *
 * @group node
 */
class ArgumentUidRevisionTest extends ViewsKernelTestBase {
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'field', 'text', 'user', 'node_test_views'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_argument_node_uid_revision'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('user');
    $this->installConfig(['node', 'field']);

    ViewTestData::createTestViews(get_class($this), ['node_test_views']);
  }

  /**
   * Tests the node_uid_revision argument.
   */
  public function testArgument() {
    $expected_result = [];

    $author = $this->createUser();
    $no_author = $this->createUser();

    // Create one node, with the author as the node author.
    $node1 = Node::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
    ]);
    $node1->setOwner($author);
    $node1->save();
    $expected_result[] = ['nid' => $node1->id()];

    // Create one node of which an additional revision author will be the
    // author.
    $node2 = Node::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
    ]);
    $node2->setRevisionAuthorId($no_author->id());
    $node2->save();
    $expected_result[] = ['nid' => $node2->id()];

    // Force to add a new revision.
    $node2->setNewRevision(TRUE);
    $node2->setRevisionAuthorId($author->id());
    $node2->save();

    // Create one  node on which the author has neither authorship of revisions
    // or the main node.
    $node3 = Node::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
    ]);
    $node3->setOwner($no_author);
    $node3->save();

    $view = Views::getView('test_argument_node_uid_revision');
    $view->initHandlers();
    $view->setArguments(['uid_revision' => $author->id()]);

    $this->executeView($view);
    $this->assertIdenticalResultset($view, $expected_result, ['nid' => 'nid']);
  }

}
