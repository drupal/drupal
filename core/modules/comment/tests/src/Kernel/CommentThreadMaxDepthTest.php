<?php

declare(strict_types = 1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Render\Element;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\comment\Traits\CommentCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests threaded comments with depth limitation.
 *
 * @group comment
 */
class CommentThreadMaxDepthTest extends KernelTestBase {

  use CommentCreationTrait;
  use CommentTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'entity_test',
    'field',
    'system',
    'text',
    'user',
  ];

  /**
   * Testing comments structured by thread.
   *
   * @var array
   */
  protected $comment = [];

  /**
   * Render array build containing a list of comments.
   *
   * @var array
   */
  protected $build;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['comment']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->addDefaultCommentField('entity_test', 'entity_test');
  }

  /**
   * Tests threaded comments with no depth limitation.
   *
   * @covers \Drupal\comment\CommentViewBuilder::buildComponents
   */
  public function testNoMaxDepth(): void {
    // Check that we're using not limited comment threading.
    $this->assertSame(CommentManagerInterface::COMMENT_MODE_THREADED, FieldConfig::loadByName('entity_test', 'entity_test', 'comment')->getSetting('default_mode'));

    $this->createTestComments();

    // Reply to 'deepest' comment.
    $reply = $this->createComment(['pid' => $this->comment[0][0][0]['entity']->id()]);
    // Reply to reply.
    $reply_to_reply = $this->createComment(['pid' => $reply->id()]);
    // Reply to reply of reply.
    $reply_to_reply_of_reply = $this->createComment(['pid' => $reply_to_reply->id()]);

    // The view builder is responsible to compute the indent for each comment.
    // @see \Drupal\comment\CommentViewBuilder::buildComponents()
    $this->buildComments([
      $this->comment[0]['entity'],
      $this->comment[0][0]['entity'],
      $this->comment[0][1]['entity'],
      $this->comment[0][0][0]['entity'],
      $reply,
      $reply_to_reply,
      $reply_to_reply_of_reply,
    ]);

    // Checking indents of each comment. Note that the build item
    // #comment_indent value is relative to the previous comment.
    $this->assertCommentIsNotIndented($this->comment[0]['entity']);
    $this->assertCommentIsIndented($this->comment[0][0]['entity']);
    $this->assertCommentIsNotIndented($this->comment[0][1]['entity']);
    $this->assertCommentIsIndented($this->comment[0][0][0]['entity']);
    $this->assertCommentIsIndented($reply);
    $this->assertCommentIsIndented($reply_to_reply);
    $this->assertCommentIsIndented($reply_to_reply_of_reply);
  }

  /**
   * Tests threaded comments with depth limitation when replying is allowed.
   *
   * @covers \Drupal\comment\CommentViewBuilder::buildComponents
   */
  public function testMaxDepthReplyAllowed(): void {
    FieldConfig::loadByName('entity_test', 'entity_test', 'comment')
      ->setSetting('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED_DEPTH_LIMIT)
      ->setSetting('thread_limit', [
        'depth' => 3,
        'mode' => CommentItemInterface::THREAD_DEPTH_REPLY_MODE_ALLOW,
      ])->save();
    $this->createTestComments();

    // Reply to 'deepest' comment.
    $reply = $this->createComment(['pid' => $this->comment[0][0][0]['entity']->id()]);
    // Reply to reply.
    $reply_to_reply = $this->createComment(['pid' => $reply->id()]);

    // The view builder is responsible to compute the indent for each comment.
    // @see \Drupal\comment\CommentViewBuilder::buildComponents()
    $this->buildComments([
      $this->comment[0]['entity'],
      $this->comment[0][0]['entity'],
      $this->comment[0][1]['entity'],
      $this->comment[0][0][0]['entity'],
      $reply,
      $reply_to_reply,
    ]);

    // Checking indents of each comment. Note that the build item
    // #comment_indent value is relative to the previous comment.
    $this->assertCommentIsNotIndented($this->comment[0]['entity']);
    $this->assertCommentIsIndented($this->comment[0][0]['entity']);
    $this->assertCommentIsNotIndented($this->comment[0][1]['entity']);
    $this->assertCommentIsIndented($this->comment[0][0][0]['entity']);
    // Check that the reply to deepest comment shows both with the same indent.
    $this->assertCommentIsNotIndented($reply);
    // Check that the reply to reply has the same indent.
    $this->assertCommentIsNotIndented($reply_to_reply);

    // Change the reply mode in order to test that, when reply to the deepest
    // comment is denied, the thread still shows with the limited depth.
    FieldConfig::loadByName('entity_test', 'entity_test', 'comment')
      ->setSetting('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED_DEPTH_LIMIT)
      ->setSetting('thread_limit', [
        'depth' => 3,
        'mode' => CommentItemInterface::THREAD_DEPTH_REPLY_MODE_DENY,
      ])->save();

    // Rebuild comments.
    $this->buildComments([
      $this->comment[0]['entity'],
      $this->comment[0][0]['entity'],
      $this->comment[0][1]['entity'],
      $this->comment[0][0][0]['entity'],
      $reply,
      $reply_to_reply,
    ]);

    // Checking that the indents are kept.
    $this->assertCommentIsNotIndented($this->comment[0]['entity']);
    $this->assertCommentIsIndented($this->comment[0][0]['entity']);
    $this->assertCommentIsNotIndented($this->comment[0][1]['entity']);
    $this->assertCommentIsIndented($this->comment[0][0][0]['entity']);
    // Check that the reply to deepest comment shows both with the same indent.
    $this->assertCommentIsNotIndented($reply);
    // Check that the reply to reply has the same indent.
    $this->assertCommentIsNotIndented($reply_to_reply);
  }

  /**
   * Tests threaded comments with depth limitation when replying is denied.
   *
   * @covers \Drupal\comment\CommentAccessControlHandler::checkCreateAccess
   */
  public function testMaxDepthReplyDenied(): void {
    FieldConfig::loadByName('entity_test', 'entity_test', 'comment')
      ->setSetting('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED_DEPTH_LIMIT)
      ->setSetting('thread_limit', [
        'depth' => 3,
        'mode' => CommentItemInterface::THREAD_DEPTH_REPLY_MODE_DENY,
      ])->save();
    $this->createTestComments();

    $account = $this->createUser(['view test entity', 'access comments']);
    $this->setCurrentUser($account);

    // Check that replying to comments not on the deepest level is allowed.
    $this->assertCommentHasReplyLink($this->comment[0]['entity']);
    $this->assertCommentHasReplyLink($this->comment[0][0]['entity']);
    $this->assertCommentHasReplyLink($this->comment[0][1]['entity']);
    // Check that replying to comments on the deepest level is denied.
    $this->assertCommentHasNotReplyLink($this->comment[0][0][0]['entity']);
  }

  /**
   * Asserts that a comment is indented in a build.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment to be checked.
   */
  protected function assertCommentIsIndented(CommentInterface $comment): void {
    $this->commentIndentAssertHelper($comment, 1);
  }

  /**
   * Asserts that a comment is not indented in a build.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment to be checked.
   */
  protected function assertCommentIsNotIndented(CommentInterface $comment): void {
    $this->commentIndentAssertHelper($comment, 0);
  }

  /**
   * Provides common code for comment indent assertion methods.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment to be checked.
   * @param int $indent
   *   The indent value. Allowed values: 0, 1.
   */
  protected function commentIndentAssertHelper(CommentInterface $comment, int $indent): void {
    assert(in_array($indent, [0, 1], TRUE), 'Only 0 or 1 are allowed');
    foreach (Element::children($this->build) as $delta) {
      if ($this->build[$delta]['#comment']->id() === $comment->id()) {
        $message = [
          'Comment with ID %s is indented but it should not be.',
          'Comment with ID %s is not indented but it should be.',
        ];
        $this->assertSame($indent, $this->build[$delta]['#comment_indent'], sprintf($message[$indent], $comment->id()));
        return;
      }
    }
    $this->fail("Comment with ID {$comment->id()} not found in the build.");
  }

  /**
   * Asserts that the comment reply link exists within comment links.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   */
  protected function assertCommentHasReplyLink(CommentInterface $comment): void {
    $this->assertArrayHasKey('comment-reply', $this->getCommentLinks($comment));
  }

  /**
   * Asserts that the comment reply link doesn't exists within comment links.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   */
  protected function assertCommentHasNotReplyLink(CommentInterface $comment): void {
    $this->assertArrayNotHasKey('comment-reply', $this->getCommentLinks($comment));
  }

  /**
   * Returns the built comment links given a comment entity.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   *
   * @return array
   *   A list of built comment links.
   */
  protected function getCommentLinks(CommentInterface $comment): array {
    $lazy_builders = $this->container->get('comment.lazy_builders');
    $build = $lazy_builders->renderLinks($comment->id(), 'default', 'en', FALSE);
    return $build['comment']['#links'];
  }

  /**
   * Creates a threaded structure of comments.
   */
  protected function createTestComments(): void {
    $entity = EntityTest::create();
    $entity->save();

    $this->comment[0]['entity'] = $this->createComment([
     'entity_type' => 'entity_test',
     'entity_id' => $entity->id(),
    ]);
    $this->comment[0][0]['entity'] = $this->createComment([
      'pid' => $this->comment[0]['entity']->id(),
    ]);
    $this->comment[0][1]['entity'] = $this->createComment([
      'pid' => $this->comment[0]['entity']->id(),
    ]);
    // This is the 'deepest' comment.
    $this->comment[0][0][0]['entity'] = $this->createComment([
      'pid' => $this->comment[0][0]['entity']->id(),
    ]);
  }

  /**
   * Creates a build render array given a list of comments.
   *
   * @param array $comments
   *   A list of comments to build.
   */
  protected function buildComments(array $comments): void {
    /** @var \Drupal\Core\Entity\EntityViewBuilderInterface $view_builder */
    $view_builder = $this->container->get('entity_type.manager')->getViewBuilder('comment');
    $this->build = $view_builder->buildMultiple($view_builder->viewMultiple($comments));
  }

}
