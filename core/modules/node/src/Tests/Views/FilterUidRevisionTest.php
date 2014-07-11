<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Views\FilterUidRevisionTest.
 */

namespace Drupal\node\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the node_uid_revision handler.
 *
 * @group node
 */
class FilterUidRevisionTest extends NodeTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_filter_node_uid_revision');

  /**
   * Tests the node_uid_revision filter.
   */
  public function testFilter() {
    $author = $this->drupalCreateUser();
    $no_author = $this->drupalCreateUser();

    $expected_result = array();
    // Create one node, with the author as the node author.
    $node = $this->drupalCreateNode(array('uid' => $author->id()));
    $expected_result[] = array('nid' => $node->id());
    // Create one node of which an additional revision author will be the
    // author.
    $node = $this->drupalCreateNode(array('revision_uid' => $no_author->id()));
    $expected_result[] = array('nid' => $node->id());
    $revision = clone $node;
    // Force to add a new revision.
    $revision->set('vid', NULL);
    $revision->set('revision_uid', $author->id());
    $revision->save();

    // Create one  node on which the author has neither authorship of revisions
    // or the main node.
    $this->drupalCreateNode(array('uid' => $no_author->id()));

    $view = Views::getView('test_filter_node_uid_revision');
    $view->initHandlers();
    $view->filter['uid_revision']->value = array($author->id());

    $this->executeView($view);
    $this->assertIdenticalResultset($view, $expected_result, array('nid' => 'nid'), 'Make sure that the view only returns nodes which match either the node or the revision author.');
  }

}
