<?php

/**
 * @file
 * Contains Drupal\book\Access\BookNodeIsRemovableAccessCheck.
 */

namespace Drupal\book\Access;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\node\NodeInterface;

/**
 * Determines whether the requested node can be removed from its book.
 */
class BookNodeIsRemovableAccessCheck implements AccessInterface {

  /**
   * Book Manager Service.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * Constructs a BookNodeIsRemovableAccessCheck object.
   *
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   Book Manager Service.
   */
  public function __construct(BookManagerInterface $book_manager) {
    $this->bookManager = $book_manager;
  }

  /**
   * Checks access for removing the node from its book.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node requested to be removed from its book.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(NodeInterface $node) {
    return $this->bookManager->checkNodeIsRemovable($node) ? static::ALLOW : static::DENY;
  }

}
