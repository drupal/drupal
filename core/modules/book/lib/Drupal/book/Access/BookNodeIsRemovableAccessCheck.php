<?php

/**
 * @file
 * Contains Drupal\book\Access\BookNodeIsRemovableAccessCheck.
 */

namespace Drupal\book\Access;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

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
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $node = $request->attributes->get('node');
    if (!empty($node)) {
      return $this->bookManager->checkNodeIsRemovable($node) ? static::ALLOW : static::DENY;
    }
    return static::DENY;
  }

}
