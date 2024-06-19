<?php

declare(strict_types=1);

namespace Drupal\Tests\book\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Tests Book and Content Moderation integration.
 *
 * @group book
 * @group legacy
 */
class BookContentModerationTest extends BrowserTestBase {

  use BookTestTrait;
  use ContentModerationTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'book',
    'block',
    'book_test',
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('page_title_block');

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'book');
    $workflow->save();

    // We need a user with additional content moderation permissions.
    $this->bookAuthor = $this->drupalCreateUser([
      'create new books',
      'create book content',
      'edit own book content',
      'add content to books',
      'access printer-friendly version',
      'view any unpublished content',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
    ]);
  }

  /**
   * Tests that book drafts can not modify the book outline.
   */
  public function testBookWithPendingRevisions(): void {
    // Create two books.
    $book_1_nodes = $this->createBook(['moderation_state[0][state]' => 'published']);
    $book_1 = $this->book;

    $this->createBook(['moderation_state[0][state]' => 'published']);
    $book_2 = $this->book;

    $this->drupalLogin($this->bookAuthor);

    // Check that book pages display along with the correct outlines.
    $this->book = $book_1;
    $this->checkBookNode($book_1, [$book_1_nodes[0], $book_1_nodes[3], $book_1_nodes[4]], FALSE, FALSE, $book_1_nodes[0], []);
    $this->checkBookNode($book_1_nodes[0], [$book_1_nodes[1], $book_1_nodes[2]], $book_1, $book_1, $book_1_nodes[1], [$book_1]);

    // Create a new book page without actually attaching it to a book and create
    // a draft.
    $edit = [
      'title[0][value]' => $this->randomString(),
      'moderation_state[0][state]' => 'published',
    ];
    $this->drupalGet('node/add/book');
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotEmpty($node);

    $edit = [
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextNotContains('You can only change the book outline for the published version of this content.');

    // Create a book draft with no changes, then publish it.
    $edit = [
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalGet('node/' . $book_1->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextNotContains('You can only change the book outline for the published version of this content.');
    $edit = [
      'moderation_state[0][state]' => 'published',
    ];
    $this->drupalGet('node/' . $book_1->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Try to move Node 2 to a different parent.
    $edit = [
      'book[pid]' => $book_1_nodes[3]->id(),
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalGet('node/' . $book_1_nodes[1]->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('You can only change the book outline for the published version of this content.');

    // Check that the book outline did not change.
    $this->book = $book_1;
    $this->checkBookNode($book_1, [$book_1_nodes[0], $book_1_nodes[3], $book_1_nodes[4]], FALSE, FALSE, $book_1_nodes[0], []);
    $this->checkBookNode($book_1_nodes[0], [$book_1_nodes[1], $book_1_nodes[2]], $book_1, $book_1, $book_1_nodes[1], [$book_1]);

    // Try to move Node 2 to a different book.
    $edit = [
      'book[bid]' => $book_2->id(),
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalGet('node/' . $book_1_nodes[1]->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('You can only change the book outline for the published version of this content.');

    // Check that the book outline did not change.
    $this->book = $book_1;
    $this->checkBookNode($book_1, [$book_1_nodes[0], $book_1_nodes[3], $book_1_nodes[4]], FALSE, FALSE, $book_1_nodes[0], []);
    $this->checkBookNode($book_1_nodes[0], [$book_1_nodes[1], $book_1_nodes[2]], $book_1, $book_1, $book_1_nodes[1], [$book_1]);

    // Try to change the weight of Node 2.
    $edit = [
      'book[weight]' => 2,
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalGet('node/' . $book_1_nodes[1]->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('You can only change the book outline for the published version of this content.');

    // Check that the book outline did not change.
    $this->book = $book_1;
    $this->checkBookNode($book_1, [$book_1_nodes[0], $book_1_nodes[3], $book_1_nodes[4]], FALSE, FALSE, $book_1_nodes[0], []);
    $this->checkBookNode($book_1_nodes[0], [$book_1_nodes[1], $book_1_nodes[2]], $book_1, $book_1, $book_1_nodes[1], [$book_1]);

    // Save a new draft revision for the node without any changes and check that
    // the error message is not displayed.
    $edit = [
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalGet('node/' . $book_1_nodes[1]->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextNotContains('You can only change the book outline for the published version of this content.');
  }

}
