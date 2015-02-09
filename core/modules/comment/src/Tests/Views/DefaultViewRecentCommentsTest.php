<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Views\DefaultViewRecentCommentsTest.
 */

namespace Drupal\comment\Tests\Views;

use Drupal\comment\CommentInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestBase;

/**
 * Tests results for the Recent Comments view shipped with the module.
 *
 * @group comment
 */
class DefaultViewRecentCommentsTest extends ViewTestBase {

  use CommentTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('node', 'comment', 'block');

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
   * @var \Drupal\node\Node
   */
  public $node;

  protected function setUp() {
    parent::setUp();

    // Create a new content type
    $content_type = $this->drupalCreateContentType();

    // Add a node of the new content type.
    $node_data = array(
      'type' => $content_type->id(),
    );

    $this->addDefaultCommentField('node', $content_type->id());
    $this->node = $this->drupalCreateNode($node_data);

    // Force a flush of the in-memory storage.
    $this->container->get('views.views_data')->clear();

    // Create some comments and attach them to the created node.
    for ($i = 0; $i < $this->masterDisplayResults; $i++) {
      /** @var \Drupal\comment\CommentInterface $comment */
      $comment = entity_create('comment', array(
        'status' => CommentInterface::PUBLISHED,
        'field_name' => 'comment',
        'entity_type' => 'node',
        'entity_id' => $this->node->id(),
      ));
      $comment->setOwnerId(0);
      $comment->setSubject('Test comment ' . $i);
      $comment->comment_body->value = 'Test body ' . $i;
      $comment->comment_body->format = 'full_html';

      // Ensure comments are sorted in ascending order.
      $time = REQUEST_TIME + ($this->masterDisplayResults - $i);
      $comment->setCreatedTime($time);
      $comment->changed->value = $time;

      $comment->save();
    }

    // Store all the nodes just created to access their properties on the tests.
    $this->commentsCreated = entity_load_multiple('comment');

    // Sort created comments in descending order.
    ksort($this->commentsCreated, SORT_NUMERIC);
  }

  /**
   * Tests the block defined by the comments_recent view.
   */
  public function testBlockDisplay() {
    $view = Views::getView('comments_recent');
    $view->setDisplay('block_1');
    $this->executeView($view);

    $map = array(
      'comment_field_data_entity_id' => 'entity_id',
      'comment_field_data_subject' => 'subject',
      'cid' => 'cid',
      'comment_field_data_created' => 'created'
    );
    $expected_result = array();
    foreach (array_values($this->commentsCreated) as $key => $comment) {
      $expected_result[$key]['entity_id'] = $comment->getCommentedEntityId();
      $expected_result[$key]['subject'] = $comment->getSubject();
      $expected_result[$key]['cid'] = $comment->id();
      $expected_result[$key]['created'] = $comment->getCreatedTime();
    }
    $this->assertIdenticalResultset($view, $expected_result, $map);

    // Check the number of results given by the display is the expected.
    $this->assertEqual(sizeof($view->result), $this->blockDisplayResults,
      format_string('There are exactly @results comments. Expected @expected',
        array('@results' => count($view->result), '@expected' => $this->blockDisplayResults)
      )
    );
  }

}
