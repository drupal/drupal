<?php

namespace Drupal\Tests\book\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;
use Drupal\book\Cache\BookNavigationCacheContext;

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
  protected static $modules = [
    'content_moderation',
    'book',
    'block',
    'node_access_test',
    'book_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * A user with permission to view a book and access printer-friendly version.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * A user with permission to create and edit books and to administer blocks.
   *
   * @var \Drupal\user\UserInterface
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
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('page_title_block');

    // node_access_test requires a node_access_rebuild().
    node_access_rebuild();

    // Create users.
    $this->bookAuthor = $this->drupalCreateUser([
      'create new books',
      'create book content',
      'edit own book content',
      'add content to books',
      'view own unpublished content',
    ]);
    $this->webUser = $this->drupalCreateUser([
      'access printer-friendly version',
      'node test view',
    ]);
    $this->webUserWithoutNodeAccess = $this->drupalCreateUser([
      'access printer-friendly version',
    ]);
    $this->adminUser = $this->drupalCreateUser([
      'create new books',
      'create book content',
      'edit any book content',
      'delete any book content',
      'add content to books',
      'administer blocks',
      'administer permissions',
      'administer book outlines',
      'node test view',
      'administer content types',
      'administer site configuration',
      'view any unpublished content',
    ]);
  }

  /**
   * Test the book navigation cache context argument deprecation.
   *
   * @group legacy
   */
  public function testBookNavigationCacheContextDeprecatedParameter() {
    $this->expectDeprecation('Passing the request_stack service to Drupal\book\Cache\BookNavigationCacheContext::__construct() is deprecated in drupal:9.2.0 and will be removed before drupal:10.0.0. The parameter should be an instance of \Drupal\Core\Routing\RouteMatchInterface instead.');
    $request_stack = $this->container->get('request_stack');
    $book_navigation_cache_context = new BookNavigationCacheContext($request_stack);
    $this->assertNotNull($book_navigation_cache_context);
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
    $this->drupalGet($this->adminUser->toUrl());
    $this->assertSession()->responseContains('[route.book_navigation]=book.none');

    // On non-book node route.
    $this->drupalGet($page->toUrl());
    $this->assertSession()->responseContains('[route.book_navigation]=book.none');

    // On book node route.
    $this->drupalGet($book_nodes[0]->toUrl());
    $this->assertSession()->responseContains('[route.book_navigation]=0|2|3');
    $this->drupalGet($book_nodes[1]->toUrl());
    $this->assertSession()->responseContains('[route.book_navigation]=0|2|3|4');
    $this->drupalGet($book_nodes[2]->toUrl());
    $this->assertSession()->responseContains('[route.book_navigation]=0|2|3|5');
    $this->drupalGet($book_nodes[3]->toUrl());
    $this->assertSession()->responseContains('[route.book_navigation]=0|2|6');
    $this->drupalGet($book_nodes[4]->toUrl());
    $this->assertSession()->responseContains('[route.book_navigation]=0|2|7');
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
    $this->drupalGet('admin/structure/book/' . $book->id());
    $this->submitForm([], 'Save book pages');
    $this->assertSession()->pageTextContains('Updated book ' . $book->label() . '.');
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
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:book.settings');

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
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->drupalLogout();
    $this->drupalLogin($this->webUser);

    // Check that the nodes in the second book are displayed correctly.
    // First we must set $this->book to the second book, so that the
    // correct regex will be generated for testing the outline.
    $this->book = $other_book;
    $this->checkBookNode($other_book, [$node], FALSE, FALSE, $node, []);
    $this->checkBookNode($node, NULL, $other_book, $other_book, FALSE, [$other_book]);

    // Test that we can save a book programmatically.
    $this->drupalLogin($this->bookAuthor);
    $book = $this->createBookNode('new');
    $book->save();

    // Confirm that an unpublished book page has the 'Add child page' link.
    $this->drupalGet('node/' . $nodes[4]->id());
    $this->assertSession()->linkExists('Add child page');
    $nodes[4]->setUnPublished();
    $nodes[4]->save();
    $this->drupalGet('node/' . $nodes[4]->id());
    $this->assertSession()->linkExists('Add child page');
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
    $this->clickLink('Printer-friendly version');

    // Make sure each part of the book is there.
    foreach ($nodes as $node) {
      $this->assertSession()->pageTextContains($node->label());
      $this->assertSession()->responseContains($node->body->processed);
    }

    // Make sure we can't export an unsupported format.
    $this->drupalGet('book/export/foobar/' . $this->book->id());
    $this->assertSession()->statusCodeEquals(404);

    // Make sure we get a 404 on a non-existent book node.
    $this->drupalGet('book/export/html/123');
    $this->assertSession()->statusCodeEquals(404);

    // Make sure an anonymous user cannot view printer-friendly version.
    $this->drupalLogout();

    // Load the book and verify there is no printer-friendly version link.
    $this->drupalGet('node/' . $this->book->id());
    $this->assertSession()->linkNotExists('Printer-friendly version', 'Anonymous user is not shown link to printer-friendly version.');

    // Try getting the URL directly, and verify it fails.
    $this->drupalGet('book/export/html/' . $this->book->id());
    $this->assertSession()->statusCodeEquals(403);

    // Now grant anonymous users permission to view the printer-friendly
    // version and verify that node access restrictions still prevent them from
    // seeing it.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access printer-friendly version']);
    $this->drupalGet('book/export/html/' . $this->book->id());
    $this->assertSession()->statusCodeEquals(403);
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
    $this->drupalGet('admin/people/permissions/' . RoleInterface::ANONYMOUS_ID);
    $this->submitForm($edit, 'Save permissions');
    $this->assertSession()->pageTextContains('The changes have been saved.');

    // Test correct display of the block.
    $nodes = $this->createBook();
    $this->drupalGet('<front>');
    // Book navigation block.
    $this->assertSession()->pageTextContains($block->label());
    // Link to book root.
    $this->assertSession()->pageTextContains($this->book->label());
    // No links to individual book pages.
    $this->assertSession()->pageTextNotContains($nodes[0]->label());

    // Ensure that an unpublished node does not appear in the navigation for a
    // user without access. By unpublishing a parent page, child pages should
    // not appear in the navigation. The node_access_test module is disabled
    // since it interferes with this logic.

    /** @var \Drupal\Core\Extension\ModuleInstaller $installer */
    $installer = \Drupal::service('module_installer');
    $installer->uninstall(['node_access_test']);
    node_access_rebuild();

    $nodes[0]->setUnPublished();
    $nodes[0]->save();

    // Verify the user does not have access to the unpublished node.
    $this->assertFalse($nodes[0]->access('view', $this->webUser));

    // Verify the unpublished book page does not appear in the navigation.
    $this->drupalLogin($this->webUser);
    $this->drupalGet($nodes[0]->toUrl());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($this->book->toUrl());
    $this->assertSession()->responseNotContains($nodes[0]->getTitle());
    $this->assertSession()->responseNotContains($nodes[1]->getTitle());
    $this->assertSession()->responseNotContains($nodes[2]->getTitle());
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
    // Since Node 0 has children 2 levels deep, nodes 10 and 11 should not
    // appear in the selector.
    $this->assertSession()->optionNotExists('edit-book-pid', $nodes[10]->id());
    $this->assertSession()->optionNotExists('edit-book-pid', $nodes[11]->id());
    // Node 9 should be available as an option.
    $this->assertSession()->optionExists('edit-book-pid', $nodes[9]->id());

    // Get a shallow set of options.
    /** @var \Drupal\book\BookManagerInterface $manager */
    $manager = $this->container->get('book.manager');
    $options = $manager->getTableOfContents($book->id(), 3);
    // Verify that all expected option keys are present.
    $expected_nids = [$book->id(), $nodes[0]->id(), $nodes[1]->id(), $nodes[2]->id(), $nodes[3]->id(), $nodes[6]->id(), $nodes[4]->id()];
    $this->assertEquals($expected_nids, array_keys($options));
    // Exclude Node 3.
    $options = $manager->getTableOfContents($book->id(), 3, [$nodes[3]->id()]);
    // Verify that expected option keys are present after excluding Node 3.
    $expected_nids = [$book->id(), $nodes[0]->id(), $nodes[1]->id(), $nodes[2]->id(), $nodes[4]->id()];
    $this->assertEquals($expected_nids, array_keys($options));
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
    $this->drupalGet('admin/people/permissions/' . RoleInterface::ANONYMOUS_ID);
    $this->submitForm($edit, 'Save permissions');
    $this->assertSession()->pageTextContains('The changes have been saved.');

    // Create a book.
    $this->createBook();

    // Test correct display of the block to registered users.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $this->book->id());
    $this->assertSession()->pageTextContains($block->label());
    $this->drupalLogout();

    // Test correct display of the block to anonymous users.
    $this->drupalGet('node/' . $this->book->id());
    $this->assertSession()->pageTextContains($block->label());

    // Test the 'book pages' block_mode setting.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextNotContains($block->label());
  }

  /**
   * Tests the access for deleting top-level book nodes.
   */
  public function testBookDelete() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $nodes = $this->createBook();
    $this->drupalLogin($this->adminUser);
    $edit = [];

    // Ensure that the top-level book node cannot be deleted.
    $this->drupalGet('node/' . $this->book->id() . '/outline/remove');
    $this->assertSession()->statusCodeEquals(403);

    // Ensure that a child book node can be deleted.
    $this->drupalGet('node/' . $nodes[4]->id() . '/outline/remove');
    $this->submitForm($edit, 'Remove');
    $node_storage->resetCache([$nodes[4]->id()]);
    $node4 = $node_storage->load($nodes[4]->id());
    $this->assertEmpty($node4->book, 'Deleting child book node properly allowed.');

    // $nodes[4] is stale, trying to delete it directly will cause an error.
    $node4->delete();
    unset($nodes[4]);

    // Delete all child book nodes and retest top-level node deletion.
    $node_storage->delete($nodes);

    $this->drupalGet('node/' . $this->book->id() . '/outline/remove');
    $this->submitForm($edit, 'Remove');
    $node_storage->resetCache([$this->book->id()]);
    $node = $node_storage->load($this->book->id());
    $this->assertEmpty($node->book, 'Deleting childless top-level book node properly allowed.');

    // Tests directly deleting a book parent.
    $nodes = $this->createBook();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->book->toUrl('delete-form'));
    $this->assertSession()->pageTextContains($this->book->label() . ' is part of a book outline, and has associated child pages. If you proceed with deletion, the child pages will be relocated automatically.');
    // Delete parent, and visit a child page.
    $this->drupalGet($this->book->toUrl('delete-form'));
    $this->submitForm([], 'Delete');
    $this->drupalGet($nodes[0]->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($nodes[0]->label());
    // The book parents should be updated.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node_storage->resetCache();
    $child = $node_storage->load($nodes[0]->id());
    $this->assertEquals($child->id(), $child->book['bid'], 'Child node book ID updated when parent is deleted.');
    // 3rd-level children should now be 2nd-level.
    $second = $node_storage->load($nodes[1]->id());
    $this->assertEquals($child->id(), $second->book['bid'], '3rd-level child node is now second level when top-level node is deleted.');
  }

  /**
   * Tests outline of a book.
   */
  public function testBookOutline() {
    $this->drupalLogin($this->bookAuthor);

    // Create new node not yet a book.
    $empty_book = $this->drupalCreateNode(['type' => 'book']);
    $this->drupalGet('node/' . $empty_book->id() . '/outline');
    $this->assertSession()->linkNotExists('Book outline', 'Book Author is not allowed to outline');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $empty_book->id() . '/outline');
    $this->assertSession()->pageTextContains('Book outline');
    // Verify that the node does not belong to a book.
    $this->assertTrue($this->assertSession()->optionExists('edit-book-bid', 0)->isSelected());
    $this->assertSession()->linkNotExists('Remove from book outline');

    $edit = [];
    $edit['book[bid]'] = '1';
    $this->drupalGet('node/' . $empty_book->id() . '/outline');
    $this->submitForm($edit, 'Add to book outline');
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($empty_book->id());
    // Test the book array.
    $this->assertEquals($empty_book->id(), $node->book['nid']);
    $this->assertEquals($empty_book->id(), $node->book['bid']);
    $this->assertEquals(1, $node->book['depth']);
    $this->assertEquals($empty_book->id(), $node->book['p1']);
    $this->assertEquals('0', $node->book['pid']);

    // Create new book.
    $this->drupalLogin($this->bookAuthor);
    $book = $this->createBookNode('new');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $book->id() . '/outline');
    $this->assertSession()->pageTextContains('Book outline');
    $this->clickLink('Remove from book outline');
    $this->assertSession()->pageTextContains('Are you sure you want to remove ' . $book->label() . ' from the book hierarchy?');

    // Create a new node and set the book after the node was created.
    $node = $this->drupalCreateNode(['type' => 'book']);
    $edit = [];
    $edit['book[bid]'] = $node->id();
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($node->id());

    // Test the book array.
    $this->assertEquals($node->id(), $node->book['nid']);
    $this->assertEquals($node->id(), $node->book['bid']);
    $this->assertEquals(1, $node->book['depth']);
    $this->assertEquals($node->id(), $node->book['p1']);
    $this->assertEquals('0', $node->book['pid']);

    // Test the form itself.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertTrue($this->assertSession()->optionExists('edit-book-bid', $node->id())->isSelected());
  }

  /**
   * Tests that saveBookLink() returns something.
   */
  public function testSaveBookLink() {
    $book_manager = \Drupal::service('book.manager');

    // Mock a link for a new book.
    $link = ['nid' => 1, 'has_children' => 0, 'original_bid' => 0, 'pid' => 0, 'weight' => 0, 'bid' => 0];
    $new = TRUE;

    // Save the link.
    $return = $book_manager->saveBookLink($link, $new);

    // Add the link defaults to $link so we have something to compare to the return from saveBookLink().
    $link = $book_manager->getLinkDefaults($link['nid']);

    // Test the return from saveBookLink.
    $this->assertEquals($return, $link);
  }

  /**
   * Tests the listing of all books.
   */
  public function testBookListing() {
    // Uninstall 'node_access_test' as this interferes with the test.
    \Drupal::service('module_installer')->uninstall(['node_access_test']);

    // Create a new book.
    $nodes = $this->createBook();

    // Load the book page and assert the created book title is displayed.
    $this->drupalGet('book');
    $this->assertSession()->pageTextContains($this->book->label());

    // Unpublish the top book page and confirm that the created book title is
    // not displayed for anonymous.
    $this->book->setUnpublished();
    $this->book->save();

    $this->drupalGet('book');
    $this->assertSession()->pageTextNotContains($this->book->label());

    // Publish the top book page and unpublish a page in the book and confirm
    // that the created book title is displayed for anonymous.
    $this->book->setPublished();
    $this->book->save();
    $nodes[0]->setUnpublished();
    $nodes[0]->save();

    $this->drupalGet('book');
    $this->assertSession()->pageTextContains($this->book->label());

    // Unpublish the top book page and confirm that the created book title is
    // displayed for user which has 'view own unpublished content' permission.
    $this->drupalLogin($this->bookAuthor);
    $this->book->setUnpublished();
    $this->book->save();

    $this->drupalGet('book');
    $this->assertSession()->pageTextContains($this->book->label());

    // Ensure the user doesn't see the book if they don't own it.
    $this->book->setOwner($this->webUser)->save();
    $this->drupalGet('book');
    $this->assertSession()->pageTextNotContains($this->book->label());

    // Confirm that the created book title is displayed for user which has
    // 'view any unpublished content' permission.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('book');
    $this->assertSession()->pageTextContains($this->book->label());
  }

  /**
   * Tests the administrative listing of all books.
   */
  public function testAdminBookListing() {
    // Create a new book.
    $nodes = $this->createBook();

    // Load the book page and assert the created book title is displayed.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/book');
    $this->assertSession()->pageTextContains($this->book->label());
  }

  /**
   * Tests the administrative listing of all book pages in a book.
   */
  public function testAdminBookNodeListing() {
    // Create a new book.
    $nodes = $this->createBook();
    $this->drupalLogin($this->adminUser);

    // Load the book page list and assert the created book title is displayed
    // and action links are shown on list items.
    $this->drupalGet('admin/structure/book/' . $this->book->id());
    $this->assertSession()->pageTextContains($this->book->label());

    // Test that the view link is found from the list.
    $this->assertSession()->elementTextEquals('xpath', '//table//ul[@class="dropbutton"]/li/a', 'View');

    // Test that all the book pages are displayed on the book outline page.
    $this->assertSession()->elementsCount('xpath', '//table//ul[@class="dropbutton"]/li/a', count($nodes));

    // Unpublish a book in the hierarchy.
    $nodes[0]->setUnPublished();
    $nodes[0]->save();

    // Node should still appear on the outline for admins.
    $this->drupalGet('admin/structure/book/' . $this->book->id());
    $this->assertSession()->elementsCount('xpath', '//table//ul[@class="dropbutton"]/li/a', count($nodes));

    // Saving a book page not as the current version shouldn't effect the book.
    $old_title = $nodes[1]->getTitle();
    $new_title = $this->randomGenerator->name();
    $nodes[1]->isDefaultRevision(FALSE);
    $nodes[1]->setNewRevision(TRUE);
    $nodes[1]->setTitle($new_title);
    $nodes[1]->save();
    $this->drupalGet('admin/structure/book/' . $this->book->id());
    $this->assertSession()->elementsCount('xpath', '//table//ul[@class="dropbutton"]/li/a', count($nodes));
    $this->assertSession()->responseNotContains($new_title);
    $this->assertSession()->responseContains($old_title);
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
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node_storage->resetCache();

    // Log in as user without access to the book node, so no 'node test view'
    // permission.
    // @see node_access_test_node_grants().
    $this->drupalLogin($this->webUserWithoutNodeAccess);
    $book_node = $node_storage->load($this->book->id());
    $this->assertNotEmpty($book_node->book);
    $this->assertEquals($this->book->id(), $book_node->book['bid']);

    // Reset the internal cache to retrigger the hook_node_load() call.
    $node_storage->resetCache();

    $this->drupalLogin($this->webUser);
    $book_node = $node_storage->load($this->book->id());
    $this->assertNotEmpty($book_node->book);
    $this->assertEquals($this->book->id(), $book_node->book['bid']);
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
    $administratorUser = $this->drupalCreateUser([
      'administer blocks',
      'administer nodes',
      'bypass node access',
    ]);
    $this->drupalLogin($administratorUser);

    // Enable the block with "Show block only on book pages" mode.
    $this->drupalPlaceBlock('book_navigation', ['block_mode' => 'book pages']);

    // Unpublish book node.
    $edit = ['status[value]' => FALSE];
    $this->drupalGet('node/' . $this->book->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Test node page.
    $this->drupalGet('node/' . $this->book->id());
    // Unpublished book with "Show block only on book pages" book navigation
    // settings.
    $this->assertSession()->pageTextContains($this->book->label());
  }

  /**
   * Tests that the book settings form can be saved without error.
   */
  public function testSettingsForm() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/book/settings');
    $this->submitForm([], 'Save configuration');
  }

}
