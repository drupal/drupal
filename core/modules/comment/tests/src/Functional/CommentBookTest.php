<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\CommentInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\comment\Entity\Comment;

/**
 * Tests visibility of comments on book pages.
 *
 * @group comment
 */
class CommentBookTest extends BrowserTestBase {

  use CommentTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['book', 'comment'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    // Create comment field on book.
    $this->addDefaultCommentField('node', 'book');
  }

  /**
   * Tests comments in book export.
   */
  public function testBookCommentPrint() {
    $book_node = Node::create([
      'type' => 'book',
      'title' => 'Book title',
      'body' => 'Book body',
    ]);
    $book_node->book['bid'] = 'new';
    $book_node->save();

    $comment_subject = $this->randomMachineName(8);
    $comment_body = $this->randomMachineName(8);
    $comment = Comment::create([
      'subject' => $comment_subject,
      'comment_body' => $comment_body,
      'entity_id' => $book_node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'status' => CommentInterface::PUBLISHED,
    ]);
    $comment->save();

    $commenting_user = $this->drupalCreateUser([
      'access printer-friendly version',
      'access comments',
      'post comments',
    ]);
    $this->drupalLogin($commenting_user);

    $this->drupalGet('node/' . $book_node->id());

    $this->assertText($comment_subject, 'Comment subject found');
    $this->assertText($comment_body, 'Comment body found');
    $this->assertText(t('Add new comment'), 'Comment form found');
    $this->assertField('subject[0][value]', 'Comment form subject found');

    $this->drupalGet('book/export/html/' . $book_node->id());

    $this->assertText(t('Comments'), 'Comment thread found');
    $this->assertText($comment_subject, 'Comment subject found');
    $this->assertText($comment_body, 'Comment body found');

    $this->assertNoText(t('Add new comment'), 'Comment form not found');
    $this->assertNoField('subject[0][value]', 'Comment form subject not found');
  }

}
