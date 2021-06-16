<?php

namespace Drupal\book;

/**
 * Provides handling to render the book outline.
 */
class BookOutline {

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * Constructs a new BookOutline.
   *
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The book manager.
   */
  public function __construct(BookManagerInterface $book_manager) {
    $this->bookManager = $book_manager;
  }

  /**
   * Fetches the book link for the previous page of the book.
   *
   * @param array $book_link
   *   A fully loaded book link that is part of the book hierarchy.
   *
   * @return array
   *   A fully loaded book link for the page before the one represented in
   *   $book_link.
   */
  public function prevLink(array $book_link) {
    // If the parent is zero, we are at the start of a book.
    if ($book_link['pid'] == 0) {
      return NULL;
    }
    $flat = $this->bookManager->bookTreeGetFlat($book_link);
    reset($flat);
    $curr = NULL;
    do {
      $prev = $curr;
      $key = key($flat);
      $curr = current($flat);
      next($flat);
    } while ($key && $key != $book_link['nid']);

    if ($key == $book_link['nid']) {
      // The previous page in the book may be a child of the previous visible link.
      if ($prev['depth'] == $book_link['depth']) {
        // The subtree will have only one link at the top level - get its data.
        $tree = $this->bookManager->bookSubtreeData($prev);
        $data = array_shift($tree);
        // The link of interest is the last child - iterate to find the deepest one.
        while ($data['below']) {
          $data = end($data['below']);
        }
        $this->bookManager->bookLinkTranslate($data['link']);
        return $data['link'];
      }
      else {
        $this->bookManager->bookLinkTranslate($prev);
        return $prev;
      }
    }
  }

  /**
   * Fetches the book link for the next page of the book.
   *
   * @param array $book_link
   *   A fully loaded book link that is part of the book hierarchy.
   *
   * @return array
   *   A fully loaded book link for the page after the one represented in
   *   $book_link.
   */
  public function nextLink(array $book_link) {
    $flat = $this->bookManager->bookTreeGetFlat($book_link);
    reset($flat);
    do {
      $key = key($flat);
      next($flat);
    } while ($key && $key != $book_link['nid']);

    if ($key == $book_link['nid']) {
      $next = current($flat);
      if ($next) {
        $this->bookManager->bookLinkTranslate($next);
      }
      return $next;
    }
  }

  /**
   * Formats the book links for the child pages of the current page.
   *
   * @param array $book_link
   *   A fully loaded book link that is part of the book hierarchy.
   *
   * @return array
   *   HTML for the links to the child pages of the current page.
   */
  public function childrenLinks(array $book_link) {
    $flat = $this->bookManager->bookTreeGetFlat($book_link);

    $children = [];

    if ($book_link['has_children']) {
      // Walk through the array until we find the current page.
      do {
        $link = array_shift($flat);
      } while ($link && ($link['nid'] != $book_link['nid']));
      // Continue though the array and collect the links whose parent is this page.
      while (($link = array_shift($flat)) && $link['pid'] == $book_link['nid']) {
        $data['link'] = $link;
        $data['below'] = '';
        $children[] = $data;
      }
    }

    if ($children) {
      return $this->bookManager->bookTreeOutput($children);
    }
    return '';
  }

}
