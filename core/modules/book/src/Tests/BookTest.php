<?php

/**
 * @file
 * Contains \Drupal\book\Tests\BookTest.
 */

namespace Drupal\book\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\user\RoleInterface;

/**
 * Create a book, add pages, and test book interface.
 *
 * @group book
 */
class BookTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('book', 'block', 'node_access_test', 'book_test');

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
   * A user with permission to view a book and access printer-friendly version.
   *
   * @var object
   */
  protected $webUser;

  /**
   * A user with permission to create and edit books and to administer blocks.
   *
   * @var object
   */
  protected $adminUser;

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

    // node_access_test requires a node_access_rebuild().
    node_access_rebuild();

    // Create users.
    $this->bookAuthor = $this->drupalCreateUser(array('create new books', 'create book content', 'edit own book content', 'add content to books'));
    $this->webUser = $this->drupalCreateUser(array('access printer-friendly version', 'node test view'));
    $this->webUserWithoutNodeAccess = $this->drupalCreateUser(array('access printer-friendly version'));
    $this->adminUser = $this->drupalCreateUser(array('create new books', 'create book content', 'edit own book content', 'add content to books', 'administer blocks', 'administer permissions', 'administer book outlines', 'node test view', 'administer content types', 'administer site configuration'));
  }

  /**
   * Creates a new book with a page hierarchy.
   */
  function createBook() {
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
     *  |- Node 3
     *  |- Node 4
     */
    $nodes = array();
    $nodes[] = $this->createBookNode($book->id()); // Node 0.
    $nodes[] = $this->createBookNode($book->id(), $nodes[0]->book['nid']); // Node 1.
    $nodes[] = $this->createBookNode($book->id(), $nodes[0]->book['nid']); // Node 2.
    $nodes[] = $this->createBookNode($book->id()); // Node 3.
    $nodes[] = $this->createBookNode($book->id()); // Node 4.

    $this->drupalLogout();

    return $nodes;
  }

  /**
   * Tests the book navigation cache context.
   *
   * @see \Drupal\book\Cache\BookNavigationCacheContext
   */
  public function testBookNavigationCacheContext() {
    // Create a page node.
    $this->drupalCreateContentType(['type' => 'page']);
    $page = $this->drupalCreateNode();

    // Create a book, consisting of book nodes.
    $book_nodes = $this->createBook();

    // Enable the debug output.
    \Drupal::state()->set('book_test.debug_book_navigation_cache_context', TRUE);
    Cache::invalidateTags(['book_test.debug_book_navigation_cache_context']);

    $this->drupalLogin($this->bookAuthor);

    // On non-node route.
    $this->drupalGet($this->adminUser->urlInfo());
    $this->assertRaw('[route.book_navigation]=book.none');

    // On non-book node route.
    $this->drupalGet($page->urlInfo());
    $this->assertRaw('[route.book_navigation]=book.none');

    // On book node route.
    $this->drupalGet($book_nodes[0]->urlInfo());
    $this->assertRaw('[route.book_navigation]=0|2|3');
    $this->drupalGet($book_nodes[1]->urlInfo());
    $this->assertRaw('[route.book_navigation]=0|2|3|4');
    $this->drupalGet($book_nodes[2]->urlInfo());
    $this->assertRaw('[route.book_navigation]=0|2|3|5');
    $this->drupalGet($book_nodes[3]->urlInfo());
    $this->assertRaw('[route.book_navigation]=0|2|6');
    $this->drupalGet($book_nodes[4]->urlInfo());
    $this->assertRaw('[route.book_navigation]=0|2|7');
  }

  /**
   * Tests saving the book outline on an empty book.
   */
  function testEmptyBook() {
    // Create a new empty book.
    $this->drupalLogin($this->bookAuthor);
    $book = $this->createBookNode('new');
    $this->drupalLogout();

    // Log in as a user with access to the book outline and save the form.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/structure/book/' . $book->id(), array(), t('Save book pages'));
    $this->assertText(t('Updated book @book.', array('@book' => $book->label())));
  }

  /**
   * Tests book functionality through node interfaces.
   */
  function testBook() {
    // Create new book.
    $nodes = $this->createBook();
    $book = $this->book;

    $this->drupalLogin($this->webUser);

    // Check that book pages display along with the correct outlines and
    // previous/next links.
    $this->checkBookNode($book, array($nodes[0], $nodes[3], $nodes[4]), FALSE, FALSE, $nodes[0], array());
    $this->checkBookNode($nodes[0], array($nodes[1], $nodes[2]), $book, $book, $nodes[1], array($book));
    $this->checkBookNode($nodes[1], NULL, $nodes[0], $nodes[0], $nodes[2], array($book, $nodes[0]));
    $this->checkBookNode($nodes[2], NULL, $nodes[1], $nodes[0], $nodes[3], array($book, $nodes[0]));
    $this->checkBookNode($nodes[3], NULL, $nodes[2], $book, $nodes[4], array($book));
    $this->checkBookNode($nodes[4], NULL, $nodes[3], $book, FALSE, array($book));

    $this->drupalLogout();
    $this->drupalLogin($this->bookAuthor);

    // Check the presence of expected cache tags.
    $this->drupalGet('node/add/book');
    $this->assertCacheTag('config:book.settings');

    /*
     * Add Node 5 under Node 3.
     * Book
     *  |- Node 0
     *   |- Node 1
     *   |- Node 2
     *  |- Node 3
     *   |- Node 5
     *  |- Node 4
     */
    $nodes[] = $this->createBookNode($book->id(), $nodes[3]->book['nid']); // Node 5.
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);
    // Verify the new outline - make sure we don't get stale cached data.
    $this->checkBookNode($nodes[3], array($nodes[5]), $nodes[2], $book, $nodes[5], array($book));
    $this->checkBookNode($nodes[4], NULL, $nodes[5], $book, FALSE, array($book));
    $this->drupalLogout();
    // Create a second book, and move an existing book page into it.
    $this->drupalLogin($this->bookAuthor);
    $other_book = $this->createBookNode('new');
    $node = $this->createBookNode($book->id());
    $edit = array('book[bid]' => $other_book->id());
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    $this->drupalLogout();
    $this->drupalLogin($this->webUser);

    // Check that the nodes in the second book are displayed correctly.
    // First we must set $this->book to the second book, so that the
    // correct regex will be generated for testing the outline.
    $this->book = $other_book;
    $this->checkBookNode($other_book, array($node), FALSE, FALSE, $node, array());
    $this->checkBookNode($node, NULL, $other_book, $other_book, FALSE, array($other_book));

    // Test that we can save a book programatically.
    $this->drupalLogin($this->bookAuthor);
    $book = $this->createBookNode('new');
    $book->save();
  }

  /**
   * Checks the outline of sub-pages; previous, up, and next.
   *
   * Also checks the printer friendly version of the outline.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   Node to check.
   * @param $nodes
   *   Nodes that should be in outline.
   * @param $previous
   *   (optional) Previous link node. Defaults to FALSE.
   * @param $up
   *   (optional) Up link node. Defaults to FALSE.
   * @param $next
   *   (optional) Next link node. Defaults to FALSE.
   * @param array $breadcrumb
   *   The nodes that should be displayed in the breadcrumb.
   */
  function checkBookNode(EntityInterface $node, $nodes, $previous = FALSE, $up = FALSE, $next = FALSE, array $breadcrumb) {
    // $number does not use drupal_static as it should not be reset
    // since it uniquely identifies each call to checkBookNode().
    static $number = 0;
    $this->drupalGet('node/' . $node->id());

    // Check outline structure.
    if ($nodes !== NULL) {
      $this->assertPattern($this->generateOutlinePattern($nodes), format_string('Node @number outline confirmed.', array('@number' => $number)));
    }
    else {
      $this->pass(format_string('Node %number does not have outline.', array('%number' => $number)));
    }

    // Check previous, up, and next links.
    if ($previous) {
      /** @var \Drupal\Core\Url $url */
      $url = $previous->urlInfo();
      $url->setOptions(array('attributes' => array('rel' => array('prev'), 'title' => t('Go to previous page'))));
      $text = SafeMarkup::format('<b>‹</b> @label', array('@label' => $previous->label()));
      $this->assertRaw(\Drupal::l($text, $url), 'Previous page link found.');
    }

    if ($up) {
      /** @var \Drupal\Core\Url $url */
      $url = $up->urlInfo();
      $url->setOptions(array('attributes' => array('title' => t('Go to parent page'))));
      $this->assertRaw(\Drupal::l('Up', $url), 'Up page link found.');
    }

    if ($next) {
      /** @var \Drupal\Core\Url $url */
      $url = $next->urlInfo();
      $url->setOptions(array('attributes' => array('rel' => array('next'), 'title' => t('Go to next page'))));
      $text = SafeMarkup::format('@label <b>›</b>', array('@label' => $next->label()));
      $this->assertRaw(\Drupal::l($text, $url), 'Next page link found.');
    }

    // Compute the expected breadcrumb.
    $expected_breadcrumb = array();
    $expected_breadcrumb[] = \Drupal::url('<front>');
    foreach ($breadcrumb as $a_node) {
      $expected_breadcrumb[] = $a_node->url();
    }

    // Fetch links in the current breadcrumb.
    $links = $this->xpath('//nav[@class="breadcrumb"]/ol/li/a');
    $got_breadcrumb = array();
    foreach ($links as $link) {
      $got_breadcrumb[] = (string) $link['href'];
    }

    // Compare expected and got breadcrumbs.
    $this->assertIdentical($expected_breadcrumb, $got_breadcrumb, 'The breadcrumb is correctly displayed on the page.');

    // Check printer friendly version.
    $this->drupalGet('book/export/html/' . $node->id());
    $this->assertText($node->label(), 'Printer friendly title found.');
    $this->assertRaw($node->body->processed, 'Printer friendly body found.');

    $number++;
  }

  /**
   * Creates a regular expression to check for the sub-nodes in the outline.
   *
   * @param array $nodes
   *   An array of nodes to check in outline.
   *
   * @return string
   *   A regular expression that locates sub-nodes of the outline.
   */
  function generateOutlinePattern($nodes) {
    $outline = '';
    foreach ($nodes as $node) {
      $outline .= '(node\/' . $node->id() . ')(.*?)(' . $node->label() . ')(.*?)';
    }

    return '/<nav id="book-navigation-' . $this->book->id() . '"(.*?)<ul(.*?)' . $outline . '<\/ul>/s';
  }

  /**
   * Creates a book node.
   *
   * @param int|string $book_nid
   *   A book node ID or set to 'new' to create a new book.
   * @param int|null $parent
   *   (optional) Parent book reference ID. Defaults to NULL.
   */
  function createBookNode($book_nid, $parent = NULL) {
    // $number does not use drupal_static as it should not be reset
    // since it uniquely identifies each call to createBookNode().
    static $number = 0; // Used to ensure that when sorted nodes stay in same order.

    $edit = array();
    $edit['title[0][value]'] = str_pad($number, 2, '0', STR_PAD_LEFT) . ' - SimpleTest test node ' . $this->randomMachineName(10);
    $edit['body[0][value]'] = 'SimpleTest test body ' . $this->randomMachineName(32) . ' ' . $this->randomMachineName(32);
    $edit['book[bid]'] = $book_nid;

    if ($parent !== NULL) {
      $this->drupalPostForm('node/add/book', $edit, t('Change book (update list of parents)'));

      $edit['book[pid]'] = $parent;
      $this->drupalPostForm(NULL, $edit, t('Save'));
      // Make sure the parent was flagged as having children.
      $parent_node = \Drupal::entityManager()->getStorage('node')->loadUnchanged($parent);
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
   * Tests book export ("printer-friendly version") functionality.
   */
  function testBookExport() {
    // Create a book.
    $nodes = $this->createBook();

    // Login as web user and view printer-friendly version.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $this->book->id());
    $this->clickLink(t('Printer-friendly version'));

    // Make sure each part of the book is there.
    foreach ($nodes as $node) {
      $this->assertText($node->label(), 'Node title found in printer friendly version.');
      $this->assertRaw($node->body->processed, 'Node body found in printer friendly version.');
    }

    // Make sure we can't export an unsupported format.
    $this->drupalGet('book/export/foobar/' . $this->book->id());
    $this->assertResponse('404', 'Unsupported export format returned "not found".');

    // Make sure we get a 404 on a not existing book node.
    $this->drupalGet('book/export/html/123');
    $this->assertResponse('404', 'Not existing book node returned "not found".');

    // Make sure an anonymous user cannot view printer-friendly version.
    $this->drupalLogout();

    // Load the book and verify there is no printer-friendly version link.
    $this->drupalGet('node/' . $this->book->id());
    $this->assertNoLink(t('Printer-friendly version'), 'Anonymous user is not shown link to printer-friendly version.');

    // Try getting the URL directly, and verify it fails.
    $this->drupalGet('book/export/html/' . $this->book->id());
    $this->assertResponse('403', 'Anonymous user properly forbidden.');

    // Now grant anonymous users permission to view the printer-friendly
    // version and verify that node access restrictions still prevent them from
    // seeing it.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, array('access printer-friendly version'));
    $this->drupalGet('book/export/html/' . $this->book->id());
    $this->assertResponse('403', 'Anonymous user properly forbidden from seeing the printer-friendly version when denied by node access.');
  }

  /**
   * Tests the functionality of the book navigation block.
   */
  function testBookNavigationBlock() {
    $this->drupalLogin($this->adminUser);

    // Enable the block.
    $block = $this->drupalPlaceBlock('book_navigation');

    // Give anonymous users the permission 'node test view'.
    $edit = array();
    $edit[RoleInterface::ANONYMOUS_ID . '[node test view]'] = TRUE;
    $this->drupalPostForm('admin/people/permissions/' . RoleInterface::ANONYMOUS_ID, $edit, t('Save permissions'));
    $this->assertText(t('The changes have been saved.'), "Permission 'node test view' successfully assigned to anonymous users.");

    // Test correct display of the block.
    $nodes = $this->createBook();
    $this->drupalGet('<front>');
    $this->assertText($block->label(), 'Book navigation block is displayed.');
    $this->assertText($this->book->label(), format_string('Link to book root (@title) is displayed.', array('@title' => $nodes[0]->label())));
    $this->assertNoText($nodes[0]->label(), 'No links to individual book pages are displayed.');
  }

  /**
   * Tests the book navigation block when an access module is installed.
   */
  function testNavigationBlockOnAccessModuleInstalled() {
    $this->drupalLogin($this->adminUser);
    $block = $this->drupalPlaceBlock('book_navigation', array('block_mode' => 'book pages'));

    // Give anonymous users the permission 'node test view'.
    $edit = array();
    $edit[RoleInterface::ANONYMOUS_ID . '[node test view]'] = TRUE;
    $this->drupalPostForm('admin/people/permissions/' . RoleInterface::ANONYMOUS_ID, $edit, t('Save permissions'));
    $this->assertText(t('The changes have been saved.'), "Permission 'node test view' successfully assigned to anonymous users.");

    // Create a book.
    $this->createBook();

    // Test correct display of the block to registered users.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $this->book->id());
    $this->assertText($block->label(), 'Book navigation block is displayed to registered users.');
    $this->drupalLogout();

    // Test correct display of the block to anonymous users.
    $this->drupalGet('node/' . $this->book->id());
    $this->assertText($block->label(), 'Book navigation block is displayed to anonymous users.');

    // Test the 'book pages' block_mode setting.
    $this->drupalGet('<front>');
    $this->assertNoText($block->label(), 'Book navigation block is not shown on non-book pages.');
  }

  /**
   * Tests the access for deleting top-level book nodes.
   */
   function testBookDelete() {
     $node_storage = $this->container->get('entity.manager')->getStorage('node');
     $nodes = $this->createBook();
     $this->drupalLogin($this->adminUser);
     $edit = array();

     // Test access to delete top-level and child book nodes.
     $this->drupalGet('node/' . $this->book->id() . '/outline/remove');
     $this->assertResponse('403', 'Deleting top-level book node properly forbidden.');
     $this->drupalPostForm('node/' . $nodes[4]->id() . '/outline/remove', $edit, t('Remove'));
     $node_storage->resetCache(array($nodes[4]->id()));
     $node4 = $node_storage->load($nodes[4]->id());
     $this->assertTrue(empty($node4->book), 'Deleting child book node properly allowed.');

     // Delete all child book nodes and retest top-level node deletion.
     foreach ($nodes as $node) {
       $nids[] = $node->id();
     }
     entity_delete_multiple('node', $nids);
     $this->drupalPostForm('node/' . $this->book->id() . '/outline/remove', $edit, t('Remove'));
     $node_storage->resetCache(array($this->book->id()));
     $node = $node_storage->load($this->book->id());
     $this->assertTrue(empty($node->book), 'Deleting childless top-level book node properly allowed.');
   }

  /*
   * Tests node type changing machine name when type is a book allowed type.
   */
  function testBookNodeTypeChange() {
    $this->drupalLogin($this->adminUser);
    // Change the name, machine name and description.
    $edit = array(
      'name' => 'Bar',
      'type' => 'bar',
    );
    $this->drupalPostForm('admin/structure/types/manage/book', $edit, t('Save content type'));

    // Ensure that the config book.settings:allowed_types has been updated with
    // the new machine and the old one has been removed.
    $this->assertTrue(book_type_is_allowed('bar'), 'Config book.settings:allowed_types contains the updated node type machine name "bar".');
    $this->assertFalse(book_type_is_allowed('book'), 'Config book.settings:allowed_types does not contain the old node type machine name "book".');

    $edit = array(
      'name' => 'Basic page',
      'title_label' => 'Title for basic page',
      'type' => 'page',
    );
    $this->drupalPostForm('admin/structure/types/add', $edit, t('Save content type'));

    // Add page to the allowed node types.
    $edit = array(
      'book_allowed_types[page]' => 'page',
      'book_allowed_types[bar]' => 'bar',
    );

    $this->drupalPostForm('admin/structure/book/settings', $edit, t('Save configuration'));
    $this->assertTrue(book_type_is_allowed('bar'), 'Config book.settings:allowed_types contains the bar node type.');
    $this->assertTrue(book_type_is_allowed('page'), 'Config book.settings:allowed_types contains the page node type.');

    // Test the order of the book.settings::allowed_types configuration is as
    // expected. The point of this test is to prove that after changing a node
    // type going to admin/structure/book/settings and pressing save without
    // changing anything should not alter the book.settings configuration. The
    // order will be:
    // @code
    // array(
    //   'bar',
    //   'page',
    // );
    // @endcode
    $current_config = $this->config('book.settings')->get();
    $this->drupalPostForm('admin/structure/book/settings', array(), t('Save configuration'));
    $this->assertIdentical($current_config, $this->config('book.settings')->get());

    // Change the name, machine name and description.
    $edit = array(
      'name' => 'Zebra book',
      'type' => 'zebra',
    );
    $this->drupalPostForm('admin/structure/types/manage/bar', $edit, t('Save content type'));
    $this->assertTrue(book_type_is_allowed('zebra'), 'Config book.settings:allowed_types contains the zebra node type.');
    $this->assertTrue(book_type_is_allowed('page'), 'Config book.settings:allowed_types contains the page node type.');

    // Test the order of the book.settings::allowed_types configuration is as
    // expected. The order should be:
    // @code
    // array(
    //   'page',
    //   'zebra',
    // );
    // @endcode
    $current_config = $this->config('book.settings')->get();
    $this->drupalPostForm('admin/structure/book/settings', array(), t('Save configuration'));
    $this->assertIdentical($current_config, $this->config('book.settings')->get());

    $edit = array(
      'name' => 'Animal book',
      'type' => 'zebra',
    );
    $this->drupalPostForm('admin/structure/types/manage/zebra', $edit, t('Save content type'));

    // Test the order of the book.settings::allowed_types configuration is as
    // expected. The order should be:
    // @code
    // array(
    //   'page',
    //   'zebra',
    // );
    // @endcode
    $current_config = $this->config('book.settings')->get();
    $this->drupalPostForm('admin/structure/book/settings', array(), t('Save configuration'));
    $this->assertIdentical($current_config, $this->config('book.settings')->get());

    // Ensure that after all the node type changes book.settings:child_type has
    // the expected value.
    $this->assertEqual($this->config('book.settings')->get('child_type'), 'zebra');
  }

  /**
   * Tests re-ordering of books.
   */
  public function testBookOrdering() {
    // Create new book.
    $this->createBook();
    $book = $this->book;

    $this->drupalLogin($this->adminUser);
    $node1 = $this->createBookNode($book->id());
    $node2 = $this->createBookNode($book->id());
    $pid = $node1->book['nid'];

    // Head to admin screen and attempt to re-order.
    $this->drupalGet('admin/structure/book/' . $book->id());
    $edit = array(
      "table[book-admin-{$node1->id()}][weight]" => 1,
      "table[book-admin-{$node2->id()}][weight]" => 2,
      // Put node 2 under node 1.
      "table[book-admin-{$node2->id()}][pid]" => $pid,
    );
    $this->drupalPostForm(NULL, $edit, t('Save book pages'));
    // Verify weight was updated.
    $this->assertFieldByName("table[book-admin-{$node1->id()}][weight]", 1);
    $this->assertFieldByName("table[book-admin-{$node2->id()}][weight]", 2);
    $this->assertFieldByName("table[book-admin-{$node2->id()}][pid]", $pid);
  }

  /**
   * Tests outline of a book.
   */
  public function testBookOutline() {
    $this->drupalLogin($this->bookAuthor);

    // Create new node not yet a book.
    $empty_book = $this->drupalCreateNode(array('type' => 'book'));
    $this->drupalGet('node/' . $empty_book->id() . '/outline');
    $this->assertNoLink(t('Book outline'), 'Book Author is not allowed to outline');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $empty_book->id() . '/outline');
    $this->assertRaw(t('Book outline'));
    $this->assertOptionSelected('edit-book-bid', 0, 'Node does not belong to a book');

    $edit = array();
    $edit['book[bid]'] = '1';
    $this->drupalPostForm('node/' . $empty_book->id() . '/outline', $edit, t('Add to book outline'));
    $node = \Drupal::entityManager()->getStorage('node')->load($empty_book->id());
    // Test the book array.
    $this->assertEqual($node->book['nid'], $empty_book->id());
    $this->assertEqual($node->book['bid'], $empty_book->id());
    $this->assertEqual($node->book['depth'], 1);
    $this->assertEqual($node->book['p1'], $empty_book->id());
    $this->assertEqual($node->book['pid'], '0');

    // Create new book.
    $this->drupalLogin($this->bookAuthor);
    $book = $this->createBookNode('new');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $book->id() . '/outline');
    $this->assertRaw(t('Book outline'));

    // Create a new node and set the book after the node was created.
    $node = $this->drupalCreateNode(array('type' => 'book'));
    $edit = array();
    $edit['book[bid]'] = $node->id();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $node = \Drupal::entityManager()->getStorage('node')->load($node->id());

    // Test the book array.
    $this->assertEqual($node->book['nid'], $node->id());
    $this->assertEqual($node->book['bid'], $node->id());
    $this->assertEqual($node->book['depth'], 1);
    $this->assertEqual($node->book['p1'], $node->id());
    $this->assertEqual($node->book['pid'], '0');

    // Test the form itself.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertOptionSelected('edit-book-bid', $node->id());
  }

  /**
   * Tests that saveBookLink() returns something.
   */
  public function testSaveBookLink() {
    $book_manager = \Drupal::service('book.manager');

    // Mock a link for a new book.
    $link = array('nid' => 1, 'has_children' => 0, 'original_bid' => 0, 'parent_depth_limit' => 8, 'pid' => 0, 'weight' => 0, 'bid' => 1);
    $new = TRUE;

    // Save the link.
    $return = $book_manager->saveBookLink($link, $new);

    // Add the link defaults to $link so we have something to compare to the return from saveBookLink().
    $link += $book_manager->getLinkDefaults($link['nid']);

    // Test the return from saveBookLink.
    $this->assertEqual($return, $link);
  }

  /**
   * Tests the listing of all books.
   */
  public function testBookListing() {
    // Create a new book.
    $this->createBook();

    // Must be a user with 'node test view' permission since node_access_test is installed.
    $this->drupalLogin($this->webUser);

    // Load the book page and assert the created book title is displayed.
    $this->drupalGet('book');

    $this->assertText($this->book->label(), 'The book title is displayed on the book listing page.');
  }

  /**
   * Tests the administrative listing of all books.
   */
  public function testAdminBookListing() {
    // Create a new book.
    $this->createBook();

    // Load the book page and assert the created book title is displayed.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/book');
    $this->assertText($this->book->label(), 'The book title is displayed on the administrative book listing page.');
  }

  /**
   * Tests the administrative listing of all book pages in a book.
   */
  public function testAdminBookNodeListing() {
    // Create a new book.
    $this->createBook();
    $this->drupalLogin($this->adminUser);

    // Load the book page list and assert the created book title is displayed
    // and action links are shown on list items.
    $this->drupalGet('admin/structure/book/' . $this->book->id());
    $this->assertText($this->book->label(), 'The book title is displayed on the administrative book listing page.');

    $elements = $this->xpath('//table//ul[@class="dropbutton"]/li/a');
    $this->assertEqual((string) $elements[0], 'View', 'View link is found from the list.');
  }

  /**
   * Ensure the loaded book in hook_node_load() does not depend on the user.
   */
  public function testHookNodeLoadAccess() {
    \Drupal::service('module_installer')->install(['node_access_test']);

    // Ensure that the loaded book in hook_node_load() does NOT depend on the
    // current user.
    $this->drupalLogin($this->bookAuthor);
    $this->book = $this->createBookNode('new');
    // Reset any internal static caching.
    $node_storage = \Drupal::entityManager()->getStorage('node');
    $node_storage->resetCache();

    // Login as user without access to the book node, so no 'node test view'
    // permission.
    // @see node_access_test_node_grants().
    $this->drupalLogin($this->webUserWithoutNodeAccess);
    $book_node = $node_storage->load($this->book->id());
    $this->assertTrue(!empty($book_node->book));
    $this->assertEqual($book_node->book['bid'], $this->book->id());

    // Reset the internal cache to retrigger the hook_node_load() call.
    $node_storage->resetCache();

    $this->drupalLogin($this->webUser);
    $book_node = $node_storage->load($this->book->id());
    $this->assertTrue(!empty($book_node->book));
    $this->assertEqual($book_node->book['bid'], $this->book->id());
  }

}
