<?php

namespace Drupal\Tests\book\Functional\Views;

use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests entity reference relationship data.
 *
 * @group book
 *
 * @see book_views_data()
 */
class BookRelationshipTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_book_view'];

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['book_test_views', 'book', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A book node.
   *
   * @var object
   */
  protected $book;

  /**
   * A user with permission to create and edit books.
   *
   * @var object
   */
  protected $bookAuthor;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    // Create users.
    $this->bookAuthor = $this->drupalCreateUser(
      [
        'create new books',
        'create book content',
        'edit own book content',
        'add content to books',
      ]
    );
    ViewTestData::createTestViews(get_class($this), ['book_test_views']);
  }

  /**
   * Creates a new book with a page hierarchy.
   */
  protected function createBook() {
    // Create new book.
    $this->drupalLogin($this->bookAuthor);

    $this->book = $this->createBookNode('new');
    $book = $this->book;

    $nodes = [];
    // Node 0.
    $nodes[] = $this->createBookNode($book->id());
    // Node 1.
    $nodes[] = $this->createBookNode($book->id(), $nodes[0]->book['nid']);
    // Node 2.
    $nodes[] = $this->createBookNode($book->id(), $nodes[1]->book['nid']);
    // Node 3.
    $nodes[] = $this->createBookNode($book->id(), $nodes[2]->book['nid']);
    // Node 4.
    $nodes[] = $this->createBookNode($book->id(), $nodes[3]->book['nid']);
    // Node 5.
    $nodes[] = $this->createBookNode($book->id(), $nodes[4]->book['nid']);
    // Node 6.
    $nodes[] = $this->createBookNode($book->id(), $nodes[5]->book['nid']);
    // Node 7.
    $nodes[] = $this->createBookNode($book->id(), $nodes[6]->book['nid']);

    $this->drupalLogout();

    return $nodes;
  }

  /**
   * Creates a book node.
   *
   * @param int|string $book_nid
   *   A book node ID or set to 'new' to create a new book.
   * @param int|null $parent
   *   (optional) Parent book reference ID. Defaults to NULL.
   *
   * @return \Drupal\node\NodeInterface
   *   The book node.
   */
  protected function createBookNode($book_nid, $parent = NULL) {
    // $number does not use drupal_static as it should not be reset
    // since it uniquely identifies each call to createBookNode().
    // Used to ensure that when sorted nodes stay in same order.
    static $number = 0;

    $edit = [];
    $edit['title[0][value]'] = $number . ' - SimpleTest test node ' . $this->randomMachineName(10);
    $edit['body[0][value]'] = 'SimpleTest test body ' . $this->randomMachineName(32) . ' ' . $this->randomMachineName(32);
    $edit['book[bid]'] = $book_nid;

    if ($parent !== NULL) {
      $this->drupalPostForm('node/add/book', $edit, t('Change book (update list of parents)'));

      $edit['book[pid]'] = $parent;
      $this->drupalPostForm(NULL, $edit, t('Save'));
      // Make sure the parent was flagged as having children.
      $parent_node = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($parent);
      $this->assertFalse(empty($parent_node->book['has_children']), 'Parent node is marked as having children');
    }
    else {
      $this->drupalPostForm('node/add/book', $edit, t('Save'));
    }

    // Check to make sure the book node was created.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotNull(($node === FALSE ? NULL : $node), 'Book node found in database.');
    $number++;

    return $node;
  }

  /**
   * Tests using the views relationship.
   */
  public function testRelationship() {

    // Create new book.
    // @var \Drupal\node\NodeInterface[] $nodes
    $nodes = $this->createBook();
    for ($i = 0; $i < 8; $i++) {
      $this->drupalGet('test-book/' . $nodes[$i]->id());

      for ($j = 0; $j < $i; $j++) {
        $this->assertLink($nodes[$j]->label());
      }
    }
  }

}
