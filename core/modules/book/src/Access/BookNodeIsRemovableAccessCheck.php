<?php

namespace Drupal\book\Access;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Access\AccessResult;
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
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(NodeInterface $node) {
    return AccessResult::allowedIf($this->bookManager->checkNodeIsRemovable($node))->addCacheableDependency($node);
  }

}
