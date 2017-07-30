<?php

namespace Drupal\Tests\book\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests Book and Content Moderation integration.
 *
 * @group book
 */
class BookContentModerationTest extends BrowserTestBase {

  use BookTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['book', 'block', 'book_test', 'content_moderation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('page_title_block');

    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'book');
    $workflow->save();

    // We need a user with additional content moderation permissions.
    $this->bookAuthor = $this->drupalCreateUser(['create new books', 'create book content', 'edit own book content', 'add content to books', 'access printer-friendly version', 'view any unpublished content', 'use editorial transition create_new_draft', 'use editorial transition publish']);
  }

  /**
   * Tests that book drafts can not modify the book outline.
   */
  public function testBookWithPendingRevisions() {
    // Create two books.
    $book_1_nodes = $this->createBook(t('Save and Publish'));
    $book_1 = $this->book;

    $this->createBook(t('Save and Publish'));
    $book_2 = $this->book;

    $this->drupalLogin($this->bookAuthor);

    // Check that book pages display along with the correct outlines.
    $this->book = $book_1;
    $this->checkBookNode($book_1, [$book_1_nodes[0], $book_1_nodes[3], $book_1_nodes[4]], FALSE, FALSE, $book_1_nodes[0], []);
    $this->checkBookNode($book_1_nodes[0], [$book_1_nodes[1], $book_1_nodes[2]], $book_1, $book_1, $book_1_nodes[1], [$book_1]);

    // Create a new book page without actually attaching it to a book and create
    // a draft.
    $edit = ['title[0][value]' => $this->randomString()];
    $this->drupalPostForm('node/add/book', $edit, t('Save and Publish'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($node);
    $this->drupalPostForm('node/' . $node->id() . '/edit', [], t('Save and Create New Draft'));
    $this->assertSession()->pageTextNotContains('You can only change the book outline for the published version of this content.');

    // Create a book draft with no changes, then publish it.
    $this->drupalPostForm('node/' . $book_1->id() . '/edit', [], t('Save and Create New Draft'));
    $this->assertSession()->pageTextNotContains('You can only change the book outline for the published version of this content.');
    $this->drupalPostForm('node/' . $book_1->id() . '/edit', [], t('Save and Publish'));

    // Try to move Node 2 to a different parent.
    $edit['book[pid]'] = $book_1_nodes[3]->id();
    $this->drupalPostForm('node/' . $book_1_nodes[1]->id() . '/edit', $edit, t('Save and Create New Draft'));

    $this->assertSession()->pageTextContains('You can only change the book outline for the published version of this content.');

    // Check that the book outline did not change.
    $this->book = $book_1;
    $this->checkBookNode($book_1, [$book_1_nodes[0], $book_1_nodes[3], $book_1_nodes[4]], FALSE, FALSE, $book_1_nodes[0], []);
    $this->checkBookNode($book_1_nodes[0], [$book_1_nodes[1], $book_1_nodes[2]], $book_1, $book_1, $book_1_nodes[1], [$book_1]);

    // Try to move Node 2 to a different book.
    $edit['book[bid]'] = $book_2->id();
    $this->drupalPostForm('node/' . $book_1_nodes[1]->id() . '/edit', $edit, t('Save and Create New Draft'));

    $this->assertSession()->pageTextContains('You can only change the book outline for the published version of this content.');

    // Check that the book outline did not change.
    $this->book = $book_1;
    $this->checkBookNode($book_1, [$book_1_nodes[0], $book_1_nodes[3], $book_1_nodes[4]], FALSE, FALSE, $book_1_nodes[0], []);
    $this->checkBookNode($book_1_nodes[0], [$book_1_nodes[1], $book_1_nodes[2]], $book_1, $book_1, $book_1_nodes[1], [$book_1]);

    // Try to change the weight of Node 2.
    $edit['book[weight]'] = 2;
    $this->drupalPostForm('node/' . $book_1_nodes[1]->id() . '/edit', $edit, t('Save and Create New Draft'));

    $this->assertSession()->pageTextContains('You can only change the book outline for the published version of this content.');

    // Check that the book outline did not change.
    $this->book = $book_1;
    $this->checkBookNode($book_1, [$book_1_nodes[0], $book_1_nodes[3], $book_1_nodes[4]], FALSE, FALSE, $book_1_nodes[0], []);
    $this->checkBookNode($book_1_nodes[0], [$book_1_nodes[1], $book_1_nodes[2]], $book_1, $book_1, $book_1_nodes[1], [$book_1]);

    // Save a new draft revision for the node without any changes and check that
    // the error message is not displayed.
    $this->drupalPostForm('node/' . $book_1_nodes[1]->id() . '/edit', [], t('Save and Create New Draft'));

    $this->assertSession()->pageTextNotContains('You can only change the book outline for the published version of this content.');
  }

}
