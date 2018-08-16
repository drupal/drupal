<?php

namespace Drupal\Tests\book\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;

/**
 * Create a book, add pages, and test book interface.
 *
 * @group book
 */
class BookTest extends BrowserTestBase {

  use BookTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['book', 'block', 'node_access_test', 'book_test'];

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
    $this->bookAuthor = $this->drupalCreateUser(['create new books', 'create book content', 'edit own book content', 'add content to books']);
    $this->webUser = $this->drupalCreateUser(['access printer-friendly version', 'node test view']);
    $this->webUserWithoutNodeAccess = $this->drupalCreateUser(['access printer-friendly version']);
    $this->adminUser = $this->drupalCreateUser(['create new books', 'create book content', 'edit any book content', 'delete any book content', 'add content to books', 'administer blocks', 'administer permissions', 'administer book outlines', 'node test view', 'administer content types', 'administer site configuration']);
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
  public function testEmptyBook() {
    // Create a new empty book.
    $this->drupalLogin($this->bookAuthor);
    $book = $this->createBookNode('new');
    $this->drupalLogout();

    // Log in as a user with access to the book outline and save the form.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/structure/book/' . $book->id(), [], t('Save book pages'));
    $this->assertText(t('Updated book @book.', ['@book' => $book->label()]));
  }

  /**
   * Tests book functionality through node interfaces.
   */
  public function testBook() {
    // Create new book.
    $nodes = $this->createBook();
    $book = $this->book;

    $this->drupalLogin($this->webUser);

    // Check that book pages display along with the correct outlines and
    // previous/next links.
    $this->checkBookNode($book, [$nodes[0], $nodes[3], $nodes[4]], FALSE, FALSE, $nodes[0], []);
    $this->checkBookNode($nodes[0], [$nodes[1], $nodes[2]], $book, $book, $nodes[1], [$book]);
    $this->checkBookNode($nodes[1], NULL, $nodes[0], $nodes[0], $nodes[2], [$book, $nodes[0]]);
    $this->checkBookNode($nodes[2], NULL, $nodes[1], $nodes[0], $nodes[3], [$book, $nodes[0]]);
    $this->checkBookNode($nodes[3], NULL, $nodes[2], $book, $nodes[4], [$book]);
    $this->checkBookNode($nodes[4], NULL, $nodes[3], $book, FALSE, [$book]);

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
    // Node 5.
    $nodes[] = $this->createBookNode($book->id(), $nodes[3]->book['nid']);
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);
    // Verify the new outline - make sure we don't get stale cached data.
    $this->checkBookNode($nodes[3], [$nodes[5]], $nodes[2], $book, $nodes[5], [$book]);
    $this->checkBookNode($nodes[4], NULL, $nodes[5], $book, FALSE, [$book]);
    $this->drupalLogout();
    // Create a second book, and move an existing book page into it.
    $this->drupalLogin($this->bookAuthor);
    $other_book = $this->createBookNode('new');
    $node = $this->createBookNode($book->id());
    $edit = ['book[bid]' => $other_book->id()];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    $this->drupalLogout();
    $this->drupalLogin($this->webUser);

    // Check that the nodes in the second book are displayed correctly.
    // First we must set $this->book to the second book, so that the
    // correct regex will be generated for testing the outline.
    $this->book = $other_book;
    $this->checkBookNode($other_book, [$node], FALSE, FALSE, $node, []);
    $this->checkBookNode($node, NULL, $other_book, $other_book, FALSE, [$other_book]);

    // Test that we can save a book programatically.
    $this->drupalLogin($this->bookAuthor);
    $book = $this->createBookNode('new');
    $book->save();
  }

  /**
   * Tests book export ("printer-friendly version") functionality.
   */
  public function testBookExport() {
    // Create a book.
    $nodes = $this->createBook();

    // Log in as web user and view printer-friendly version.
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
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access printer-friendly version']);
    $this->drupalGet('book/export/html/' . $this->book->id());
    $this->assertResponse('403', 'Anonymous user properly forbidden from seeing the printer-friendly version when denied by node access.');
  }

  /**
   * Tests the functionality of the book navigation block.
   */
  public function testBookNavigationBlock() {
    $this->drupalLogin($this->adminUser);

    // Enable the block.
    $block = $this->drupalPlaceBlock('book_navigation');

    // Give anonymous users the permission 'node test view'.
    $edit = [];
    $edit[RoleInterface::ANONYMOUS_ID . '[node test view]'] = TRUE;
    $this->drupalPostForm('admin/people/permissions/' . RoleInterface::ANONYMOUS_ID, $edit, t('Save permissions'));
    $this->assertText(t('The changes have been saved.'), "Permission 'node test view' successfully assigned to anonymous users.");

    // Test correct display of the block.
    $nodes = $this->createBook();
    $this->drupalGet('<front>');
    $this->assertText($block->label(), 'Book navigation block is displayed.');
    $this->assertText($this->book->label(), format_string('Link to book root (@title) is displayed.', ['@title' => $nodes[0]->label()]));
    $this->assertNoText($nodes[0]->label(), 'No links to individual book pages are displayed.');
  }

  /**
   * Tests BookManager::getTableOfContents().
   */
  public function testGetTableOfContents() {
    // Create new book.
    $nodes = $this->createBook();
    $book = $this->book;

    $this->drupalLogin($this->bookAuthor);

    /*
     * Add Node 5 under Node 2.
     * Add Node 6, 7, 8, 9, 10, 11 under Node 3.
     * Book
     *  |- Node 0
     *   |- Node 1
     *   |- Node 2
     *    |- Node 5
     *  |- Node 3
     *   |- Node 6
     *    |- Node 7
     *     |- Node 8
     *      |- Node 9
     *       |- Node 10
     *        |- Node 11
     *  |- Node 4
     */
    foreach ([5 => 2, 6 => 3, 7 => 6, 8 => 7, 9 => 8, 10 => 9, 11 => 10] as $child => $parent) {
      $nodes[$child] = $this->createBookNode($book->id(), $nodes[$parent]->id());
    }
    $this->drupalGet($nodes[0]->toUrl('edit-form'));
    // Snice Node 0 has children 2 levels deep, nodes 10 and 11 should not
    // appear in the selector.
    $this->assertNoOption('edit-book-pid', $nodes[10]->id());
    $this->assertNoOption('edit-book-pid', $nodes[11]->id());
    // Node 9 should be available as an option.
    $this->assertOption('edit-book-pid', $nodes[9]->id());

    // Get a shallow set of options.
    /** @var \Drupal\book\BookManagerInterface $manager */
    $manager = $this->container->get('book.manager');
    $options = $manager->getTableOfContents($book->id(), 3);
    $expected_nids = [$book->id(), $nodes[0]->id(), $nodes[1]->id(), $nodes[2]->id(), $nodes[3]->id(), $nodes[6]->id(), $nodes[4]->id()];
    $this->assertEqual(count($options), count($expected_nids));
    $diff = array_diff($expected_nids, array_keys($options));
    $this->assertTrue(empty($diff), 'Found all expected option keys');
    // Exclude Node 3.
    $options = $manager->getTableOfContents($book->id(), 3, [$nodes[3]->id()]);
    $expected_nids = [$book->id(), $nodes[0]->id(), $nodes[1]->id(), $nodes[2]->id(), $nodes[4]->id()];
    $this->assertEqual(count($options), count($expected_nids));
    $diff = array_diff($expected_nids, array_keys($options));
    $this->assertTrue(empty($diff), 'Found all expected option keys after excluding Node 3');
  }

  /**
   * Tests the book navigation block when an access module is installed.
   */
  public function testNavigationBlockOnAccessModuleInstalled() {
    $this->drupalLogin($this->adminUser);
    $block = $this->drupalPlaceBlock('book_navigation', ['block_mode' => 'book pages']);

    // Give anonymous users the permission 'node test view'.
    $edit = [];
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
  public function testBookDelete() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $nodes = $this->createBook();
    $this->drupalLogin($this->adminUser);
    $edit = [];

    // Test access to delete top-level and child book nodes.
    $this->drupalGet('node/' . $this->book->id() . '/outline/remove');
    $this->assertResponse('403', 'Deleting top-level book node properly forbidden.');
    $this->drupalPostForm('node/' . $nodes[4]->id() . '/outline/remove', $edit, t('Remove'));
    $node_storage->resetCache([$nodes[4]->id()]);
    $node4 = $node_storage->load($nodes[4]->id());
    $this->assertTrue(empty($node4->book), 'Deleting child book node properly allowed.');

    // Delete all child book nodes and retest top-level node deletion.
    foreach ($nodes as $node) {
      $nids[] = $node->id();
    }
    entity_delete_multiple('node', $nids);
    $this->drupalPostForm('node/' . $this->book->id() . '/outline/remove', $edit, t('Remove'));
    $node_storage->resetCache([$this->book->id()]);
    $node = $node_storage->load($this->book->id());
    $this->assertTrue(empty($node->book), 'Deleting childless top-level book node properly allowed.');

    // Tests directly deleting a book parent.
    $nodes = $this->createBook();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->book->urlInfo('delete-form'));
    $this->assertRaw(t('%title is part of a book outline, and has associated child pages. If you proceed with deletion, the child pages will be relocated automatically.', ['%title' => $this->book->label()]));
    // Delete parent, and visit a child page.
    $this->drupalPostForm($this->book->urlInfo('delete-form'), [], t('Delete'));
    $this->drupalGet($nodes[0]->urlInfo());
    $this->assertResponse(200);
    $this->assertText($nodes[0]->label());
    // The book parents should be updated.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node_storage->resetCache();
    $child = $node_storage->load($nodes[0]->id());
    $this->assertEqual($child->id(), $child->book['bid'], 'Child node book ID updated when parent is deleted.');
    // 3rd-level children should now be 2nd-level.
    $second = $node_storage->load($nodes[1]->id());
    $this->assertEqual($child->id(), $second->book['bid'], '3rd-level child node is now second level when top-level node is deleted.');
  }

  /**
   * Tests outline of a book.
   */
  public function testBookOutline() {
    $this->drupalLogin($this->bookAuthor);

    // Create new node not yet a book.
    $empty_book = $this->drupalCreateNode(['type' => 'book']);
    $this->drupalGet('node/' . $empty_book->id() . '/outline');
    $this->assertNoLink(t('Book outline'), 'Book Author is not allowed to outline');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $empty_book->id() . '/outline');
    $this->assertRaw(t('Book outline'));
    $this->assertOptionSelected('edit-book-bid', 0, 'Node does not belong to a book');
    $this->assertNoLink(t('Remove from book outline'));

    $edit = [];
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
    $this->clickLink(t('Remove from book outline'));
    $this->assertRaw(t('Are you sure you want to remove %title from the book hierarchy?', ['%title' => $book->label()]));

    // Create a new node and set the book after the node was created.
    $node = $this->drupalCreateNode(['type' => 'book']);
    $edit = [];
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
    $link = ['nid' => 1, 'has_children' => 0, 'original_bid' => 0, 'parent_depth_limit' => 8, 'pid' => 0, 'weight' => 0, 'bid' => 1];
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
    $this->assertEqual($elements[0]->getText(), 'View', 'View link is found from the list.');
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

    // Log in as user without access to the book node, so no 'node test view'
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

  /**
   * Tests the book navigation block when book is unpublished.
   *
   * There was a fatal error with "Show block only on book pages" block mode.
   */
  public function testBookNavigationBlockOnUnpublishedBook() {
    // Create a new book.
    $this->createBook();

    // Create administrator user.
    $administratorUser = $this->drupalCreateUser(['administer blocks', 'administer nodes', 'bypass node access']);
    $this->drupalLogin($administratorUser);

    // Enable the block with "Show block only on book pages" mode.
    $this->drupalPlaceBlock('book_navigation', ['block_mode' => 'book pages']);

    // Unpublish book node.
    $edit = ['status[value]' => FALSE];
    $this->drupalPostForm('node/' . $this->book->id() . '/edit', $edit, t('Save'));

    // Test node page.
    $this->drupalGet('node/' . $this->book->id());
    $this->assertText($this->book->label(), 'Unpublished book with "Show block only on book pages" book navigation settings.');
  }

}
