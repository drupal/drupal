<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Functional\Views;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\views\Views;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests results for the Recent Comments view shipped with the module.
 *
 * @group comment
 */
class DefaultViewRecentCommentsTest extends ViewTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'comment', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Number of results for the Default display.
   *
   * @var int
   */
  protected $defaultDisplayResults = 5;

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
  protected $commentsCreated = [];

  /**
   * Contains the node object used for comments of this test.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views, $modules);

    // Create a new content type
    $content_type = $this->drupalCreateContentType();

    // Add a node of the new content type.
    $node_data = [
      'type' => $content_type->id(),
    ];

    $this->addDefaultCommentField('node', $content_type->id());
    $this->node = $this->drupalCreateNode($node_data);

    // Force a flush of the in-memory storage.
    $this->container->get('views.views_data')->clear();

    // Create some comments and attach them to the created node.
    for ($i = 0; $i < $this->defaultDisplayResults; $i++) {
      /** @var \Drupal\comment\CommentInterface $comment */
      $comment = Comment::create([
        'status' => CommentInterface::PUBLISHED,
        'field_name' => 'comment',
        'entity_type' => 'node',
        'entity_id' => $this->node->id(),
      ]);
      $comment->setOwnerId(0);
      $comment->setSubject('Test comment ' . $i);
      $comment->comment_body->value = 'Test body ' . $i;
      $comment->comment_body->format = 'full_html';

      // Ensure comments are sorted in ascending order.
      $time = \Drupal::time()->getRequestTime() + ($this->defaultDisplayResults - $i);
      $comment->setCreatedTime($time);
      $comment->changed->value = $time;

      $comment->save();
    }

    // Store all the nodes just created to access their properties on the tests.
    $this->commentsCreated = Comment::loadMultiple();

    // Sort created comments in descending order.
    ksort($this->commentsCreated, SORT_NUMERIC);
  }

  /**
   * Tests the block defined by the comments_recent view.
   */
  public function testBlockDisplay(): void {
    $user = $this->drupalCreateUser(['access comments']);
    $this->drupalLogin($user);

    $view = Views::getView('comments_recent');
    $view->setDisplay('block_1');
    $this->executeView($view);

    $map = [
      'subject' => 'subject',
      'cid' => 'cid',
      'comment_field_data_created' => 'created',
    ];
    $expected_result = [];
    foreach (array_values($this->commentsCreated) as $key => $comment) {
      $expected_result[$key]['subject'] = $comment->getSubject();
      $expected_result[$key]['cid'] = $comment->id();
      $expected_result[$key]['created'] = $comment->getCreatedTime();
    }
    $this->assertIdenticalResultset($view, $expected_result, $map);

    // Check the number of results given by the display is the expected.
    $this->assertCount($this->blockDisplayResults, $view->result,
      'There are exactly ' . count($view->result) . ' comments. Expected ' . $this->blockDisplayResults
    );
  }

}
