<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Views\DefaultViewRecentComments.
 */

namespace Drupal\comment\Tests\Views;

use Drupal\entity\DatabaseStorageController;
use  Drupal\views\Tests\ViewTestBase;

class DefaultViewRecentComments extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'block');

  /**
   * Number of results for the Master display.
   *
   * @var int
   */
  protected $masterDisplayResults = 5;

  /**
   * Number of results for the Block display.
   *
   * @var int
   */
  protected $blockDisplayResults = 5;

  /**
   * Number of results for the Page display.
   *
   * @var int
   */
  protected $pageDisplayResults = 5;

  /**
   * Will hold the comments created for testing.
   *
   * @var array
   */
  protected $commentsCreated = array();

  /**
   * Contains the node object used for comments of this test.
   *
   * @var Drupal\node\Node
   */
  public $node;

  public static function getInfo() {
    return array(
      'name' => 'Default View - Recent Comments',
      'description' => 'Test results for the Recent Comments view shipped with the module',
      'group' => 'Views Config',
    );
  }

  public function setUp() {
    parent::setUp();

    // Create a new content type
    $content_type = $this->drupalCreateContentType();

    // Add a node of the new content type.
    $node_data = array(
      'type' => $content_type->type,
    );

    $this->node = $this->drupalCreateNode($node_data);

    // Create some comments and attach them to the created node.
    for ($i = 0; $i < $this->masterDisplayResults; $i++) {
      $comment = entity_create('comment', array('node_type' => 'comment_node_' . $this->node->getType()));
      $comment->uid->target_id = 0;
      $comment->nid->target_id = $this->node->id();
      $comment->subject->value = 'Test comment ' . $i;
      $comment->comment_body->value = 'Test body ' . $i;
      $comment->comment_body->format = 'full_html';

      // Ensure comments are sorted in ascending order.
      $time = REQUEST_TIME + ($this->masterDisplayResults - $i);
      $comment->created->value = $time;
      $comment->changed->value = $time;

      $comment->save();
    }

    // Store all the nodes just created to access their properties on the tests.
    $this->commentsCreated = entity_load_multiple('comment');

    // Sort created comments in ascending order.
    ksort($this->commentsCreated, SORT_NUMERIC);
  }

  /**
   * Tests the block defined by the comments_recent view.
   */
  public function testBlockDisplay() {
    $view = views_get_view('comments_recent');
    $view->setDisplay('block_1');
    $this->executeView($view);

    $map = array(
      'comment_nid' => 'nid',
      'comment_subject' => 'subject',
      'cid' => 'cid',
      'comment_changed' => 'changed'
    );
    $expected_result = array();
    foreach (array_values($this->commentsCreated) as $key => $comment) {
      $expected_result[$key]['nid'] = $comment->nid->target_id;
      $expected_result[$key]['subject'] = $comment->subject->value;
      $expected_result[$key]['cid'] = $comment->id();
      $expected_result[$key]['changed'] = $comment->changed->value;
    }
    $this->assertIdenticalResultset($view, $expected_result, $map);

    // Check the number of results given by the display is the expected.
    $this->assertEqual(sizeof($view->result), $this->blockDisplayResults,
      format_string('There are exactly @results comments. Expected @expected',
        array('@results' => count($view->result), '@expected' => $this->blockDisplayResults)
      )
    );
  }

  /**
   * Tests the page defined by the comments_recent view.
   */
  public function testPageDisplay() {
    $view = views_get_view('comments_recent');
    $view->setDisplay('page_1');
    $this->executeView($view);

    $map = array(
      'comment_nid' => 'nid',
      'comment_subject' => 'subject',
      'comment_changed' => 'changed',
      'comment_changed' => 'created',
      'cid' => 'cid'
    );
    $expected_result = array();
    foreach (array_values($this->commentsCreated) as $key => $comment) {
      $expected_result[$key]['nid'] = $comment->nid->target_id;
      $expected_result[$key]['subject'] = $comment->subject->value;
      $expected_result[$key]['changed'] = $comment->changed->value;
      $expected_result[$key]['created'] = $comment->created->value;
      $expected_result[$key]['cid'] = $comment->id();
    }
    $this->assertIdenticalResultset($view, $expected_result, $map);

    // Check the number of results given by the display is the expected.
    $this->assertEqual(count($view->result), $this->pageDisplayResults,
      format_string('There are exactly @results comments. Expected @expected',
        array('@results' => count($view->result), '@expected' => $this->pageDisplayResults)
      )
    );
  }
}
