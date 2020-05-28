<?php

namespace Drupal\Tests\book\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Create a book, add pages, and test book interface.
 *
 * @group book
 */
class BookBreadcrumbTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['book', 'block', 'book_breadcrumb_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * A book node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $book;

  /**
   * A user with permission to create and edit books.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $bookAuthor;

  /**
   * A user without the 'node test view' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUserWithoutNodeAccess;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('page_title_block');

    // Create users.
    $this->bookAuthor = $this->drupalCreateUser(['create new books', 'create book content', 'edit own book content', 'add content to books']);
    $this->adminUser = $this->drupalCreateUser(['create new books', 'create book content', 'edit any book content', 'delete any book content', 'add content to books', 'administer blocks', 'administer permissions', 'administer book outlines', 'administer content types', 'administer site configuration']);
  }

  /**
   * Creates a new book with a page hierarchy.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The created book nodes.
   */
  protected function createBreadcrumbBook() {
    // Create new book.
    $this->drupalLogin($this->bookAuthor);

    $this->book = $this->createBookNode('new');
    $book = $this->book;

    /*
     * Add page hierarchy to book.
     * Book
     *  |- Node 0
     *   |- Node 1
     *   |- Node 2
     *    |- Node 3
     *     |- Node 4
     *      |- Node 5
     *  |- Node 6
     */
    $nodes = [];
    $nodes[0] = $this->createBookNode($book->id());
    $nodes[1] = $this->createBookNode($book->id(), $nodes[0]->id());
    $nodes[2] = $this->createBookNode($book->id(), $nodes[0]->id());
    $nodes[3] = $this->createBookNode($book->id(), $nodes[2]->id());
    $nodes[4] = $this->createBookNode($book->id(), $nodes[3]->id());
    $nodes[5] = $this->createBookNode($book->id(), $nodes[4]->id());
    $nodes[6] = $this->createBookNode($book->id());

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
   *   The created node.
   */
  protected function createBookNode($book_nid, $parent = NULL) {
    // $number does not use drupal_static as it should not be reset since it
    // uniquely identifies each call to createBookNode(). It is used to ensure
    // that when sorted nodes stay in same order.
    static $number = 0;

    $edit = [];
    $edit['title[0][value]'] = str_pad($number, 2, '0', STR_PAD_LEFT) . ' - SimpleTest test node ' . $this->randomMachineName(10);
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
   * Test that the breadcrumb is updated when book content changes.
   */
  public function testBreadcrumbTitleUpdates() {
    // Create a new book.
    $nodes = $this->createBreadcrumbBook();
    $book = $this->book;

    $this->drupalLogin($this->bookAuthor);

    $this->drupalGet($nodes[4]->toUrl());
    // Fetch each node title in the current breadcrumb.
    $links = $this->xpath('//nav[@class="breadcrumb"]/ol/li/a');
    $got_breadcrumb = [];
    foreach ($links as $link) {
      $got_breadcrumb[] = $link->getText();
    }
    // Home link and four parent book nodes should be in the breadcrumb.
    $this->assertCount(5, $got_breadcrumb);
    $this->assertEqual($nodes[3]->getTitle(), end($got_breadcrumb));
    $edit = [
      'title[0][value]' => 'Updated node5 title',
    ];
    $this->drupalPostForm($nodes[3]->toUrl('edit-form'), $edit, 'Save');
    $this->drupalGet($nodes[4]->toUrl());
    // Fetch each node title in the current breadcrumb.
    $links = $this->xpath('//nav[@class="breadcrumb"]/ol/li/a');
    $got_breadcrumb = [];
    foreach ($links as $link) {
      $got_breadcrumb[] = $link->getText();
    }
    $this->assertCount(5, $got_breadcrumb);
    $this->assertEqual($edit['title[0][value]'], end($got_breadcrumb));
  }

  /**
   * Test that the breadcrumb is updated when book access changes.
   */
  public function testBreadcrumbAccessUpdates() {
    // Create a new book.
    $nodes = $this->createBreadcrumbBook();
    $this->drupalLogin($this->bookAuthor);
    $edit = [
      'title[0][value]' => "you can't see me",
    ];
    $this->drupalPostForm($nodes[3]->toUrl('edit-form'), $edit, 'Save');
    $this->drupalGet($nodes[4]->toUrl());
    $links = $this->xpath('//nav[@class="breadcrumb"]/ol/li/a');
    $got_breadcrumb = [];
    foreach ($links as $link) {
      $got_breadcrumb[] = $link->getText();
    }
    $this->assertCount(5, $got_breadcrumb);
    $this->assertEqual($edit['title[0][value]'], end($got_breadcrumb));
    $config = $this->container->get('config.factory')->getEditable('book_breadcrumb_test.settings');
    $config->set('hide', TRUE)->save();
    $this->drupalGet($nodes[4]->toUrl());
    $links = $this->xpath('//nav[@class="breadcrumb"]/ol/li/a');
    $got_breadcrumb = [];
    foreach ($links as $link) {
      $got_breadcrumb[] = $link->getText();
    }
    $this->assertCount(4, $got_breadcrumb);
    $this->assertEqual($nodes[2]->getTitle(), end($got_breadcrumb));
    $this->drupalGet($nodes[3]->toUrl());
    $this->assertSession()->statusCodeEquals(403);
  }

}
