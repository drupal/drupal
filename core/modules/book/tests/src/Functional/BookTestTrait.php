<?php

declare(strict_types=1);

namespace Drupal\Tests\book\Functional;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides common functionality for Book test classes.
 */
trait BookTestTrait {

  /**
   * A book node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $book;

  /**
   * A user with permission to create and edit books.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $bookAuthor;

  /**
   * Creates a new book with a page hierarchy.
   *
   * @param array $edit
   *   (optional) Field data in an associative array. Changes the current input
   *   fields (where possible) to the values indicated. Defaults to an empty
   *   array.
   *
   * @return \Drupal\node\NodeInterface[]
   */
  public function createBook($edit = []) {
    // Create new book.
    $this->drupalLogin($this->bookAuthor);

    $this->book = $this->createBookNode('new', NULL, $edit);
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
    $nodes = [];
    // Node 0.
    $nodes[] = $this->createBookNode($book->id(), NULL, $edit);
    // Node 1.
    $nodes[] = $this->createBookNode($book->id(), $nodes[0]->book['nid'], $edit);
    // Node 2.
    $nodes[] = $this->createBookNode($book->id(), $nodes[0]->book['nid'], $edit);
    // Node 3.
    $nodes[] = $this->createBookNode($book->id(), NULL, $edit);
    // Node 4.
    $nodes[] = $this->createBookNode($book->id(), NULL, $edit);

    $this->drupalLogout();

    return $nodes;
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
   *   Previous link node.
   * @param $up
   *   Up link node.
   * @param $next
   *   Next link node.
   * @param array $breadcrumb
   *   The nodes that should be displayed in the breadcrumb.
   */
  public function checkBookNode(EntityInterface $node, $nodes, $previous, $up, $next, array $breadcrumb) {
    $this->drupalGet('node/' . $node->id());
    // Check outline structure.
    if ($nodes !== NULL) {
      $book_navigation = $this->getSession()->getPage()->find('css', sprintf('nav[aria-labelledby="book-label-%s"] ul', $this->book->id()));
      $this->assertNotNull($book_navigation);
      $links = $book_navigation->findAll('css', 'a');
      $this->assertCount(count($nodes), $links);
      foreach ($nodes as $delta => $node) {
        $link = $links[$delta];
        $this->assertEquals($node->label(), $link->getText());
        $this->assertEquals($node->toUrl()->toString(), $link->getAttribute('href'));
      }
    }

    // Check previous, up, and next links.
    if ($previous) {
      $previous_element = $this->assertSession()->elementExists('named_exact', [
        'link',
        'Go to previous page',
      ]);
      $this->assertEquals($previous->toUrl()->toString(), $previous_element->getAttribute('href'));
    }

    if ($up) {
      $parent_element = $this->assertSession()->elementExists('named_exact', [
        'link',
        'Go to parent page',
      ]);
      $this->assertEquals($up->toUrl()->toString(), $parent_element->getAttribute('href'));
    }

    if ($next) {
      $next_element = $this->assertSession()->elementExists('named_exact', [
        'link',
        'Go to next page',
      ]);
      $this->assertEquals($next->toUrl()->toString(), $next_element->getAttribute('href'));
    }

    // Compute the expected breadcrumb.
    $expected_breadcrumb = [];
    $expected_breadcrumb[] = Url::fromRoute('<front>')->toString();
    foreach ($breadcrumb as $a_node) {
      $expected_breadcrumb[] = $a_node->toUrl()->toString();
    }

    // Fetch links in the current breadcrumb.
    $links = $this->xpath('//nav[@aria-labelledby="system-breadcrumb"]/ol/li/a');
    $got_breadcrumb = [];
    foreach ($links as $link) {
      $got_breadcrumb[] = $link->getAttribute('href');
    }

    // Compare expected and got breadcrumbs.
    $this->assertSame($expected_breadcrumb, $got_breadcrumb, 'The breadcrumb is correctly displayed on the page.');

    // Check printer friendly version.
    $this->drupalGet('book/export/html/' . $node->id());
    $this->assertSession()->pageTextContains($node->label());
    $this->assertSession()->responseContains($node->body->processed);
  }

  /**
   * Creates a regular expression to check for the sub-nodes in the outline.
   *
   * @param array $nodes
   *   An array of nodes to check in outline.
   *
   * @return string
   *   A regular expression that locates sub-nodes of the outline.
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use
   *   methods from \Drupal\Tests\WebAssert instead.
   *
   * @see https://www.drupal.org/node/3325904
   */
  public function generateOutlinePattern($nodes) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use methods from \Drupal\Tests\WebAssert instead. See https://www.drupal.org/node/3325904', E_USER_DEPRECATED);
    $outline = '';
    foreach ($nodes as $node) {
      $outline .= '(node\/' . $node->id() . ')(.*?)(' . $node->label() . ')(.*?)';
    }
    return '/<nav role="navigation" aria-labelledby="book-label-' . $this->book->id() . '"(.*?)<ul(.*?)' . $outline . '<\/ul>/s';
  }

  /**
   * Creates a book node.
   *
   * @param int|string $book_nid
   *   A book node ID or set to 'new' to create a new book.
   * @param int|null $parent
   *   (optional) Parent book reference ID. Defaults to NULL.
   * @param array $edit
   *   (optional) Field data in an associative array. Changes the current input
   *   fields (where possible) to the values indicated. Defaults to an empty
   *   array.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node.
   */
  public function createBookNode($book_nid, $parent = NULL, $edit = []) {
    // $number does not use drupal_static as it should not be reset
    // since it uniquely identifies each call to createBookNode().
    // Used to ensure that when sorted nodes stay in same order.
    static $number = 0;

    $edit['title[0][value]'] = str_pad((string) $number, 2, '0', STR_PAD_LEFT) . ' - test node ' . $this->randomMachineName(10);
    $edit['body[0][value]'] = 'test body ' . $this->randomMachineName(32) . ' ' . $this->randomMachineName(32);
    $edit['book[bid]'] = $book_nid;

    if ($parent !== NULL) {
      $this->drupalGet('node/add/book');
      $this->submitForm($edit, 'Change book (update list of parents)');

      $edit['book[pid]'] = $parent;
      $this->submitForm($edit, 'Save');
      // Make sure the parent was flagged as having children.
      $parent_node = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($parent);
      $this->assertNotEmpty($parent_node->book['has_children'], 'Parent node is marked as having children');
    }
    else {
      $this->drupalGet('node/add/book');
      $this->submitForm($edit, 'Save');
    }

    // Check to make sure the book node was created.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotNull(($node === FALSE ? NULL : $node), 'Book node found in database.');
    $number++;

    return $node;
  }

}
