<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentBookTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\CommentInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests visibility of comments on book pages.
 *
 * @group comment
 */
class CommentBookTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('book', 'comment');

  protected function setUp() {
    parent::setUp();

    // Create comment field on book.
    \Drupal::service('comment.manager')->addDefaultField('node', 'book');
  }

  /**
   * Tests comments in book export.
   */
  public function testBookCommentPrint() {
    $book_node = entity_create('node', array(
      'type' => 'book',
      'title' => 'Book title',
      'body' => 'Book body',
    ));
    $book_node->book['bid'] = 'new';
    $book_node->save();

    $comment_subject = $this->randomMachineName(8);
    $comment_body = $this->randomMachineName(8);
    $comment = entity_create('comment', array(
      'subject' => $comment_subject,
      'comment_body' => $comment_body,
      'entity_id' => $book_node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'status' => CommentInterface::PUBLISHED,
    ));
    $comment->save();

    $commenting_user = $this->drupalCreateUser(array('access printer-friendly version', 'access comments', 'post comments'));
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
