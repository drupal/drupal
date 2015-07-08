<?php

/**
 * @file
 * Contains \Drupal\book\Cache\BookNavigationCacheContext.
 */

namespace Drupal\book\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the book navigation cache context service.
 *
 * Cache context ID: 'route.book_navigation'.
 *
 * This allows for book navigation location-aware caching. It depends on:
 * - whether the current route represents a book node at all
 * - and if so, where in the book hierarchy we are
 *
 * This class is container-aware to avoid initializing the 'book.manager'
 * service when it is not necessary.
 */
class BookNavigationCacheContext extends ContainerAware implements CacheContextInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new BookNavigationCacheContext service.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Book navigation");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // Find the current book's ID.
    $current_bid = 0;
    if ($node = $this->requestStack->getCurrentRequest()->get('node')) {
      $current_bid = empty($node->book['bid']) ? 0 : $node->book['bid'];
    }

    // If we're not looking at a book node, then we're not navigating a book.
    if ($current_bid === 0) {
      return 'book.none';
    }

    // If we're looking at a book node, get the trail for that node.
    $active_trail = $this->container->get('book.manager')
      ->getActiveTrailIds($node->book['bid'], $node->book);
    return 'book.' . implode('|', $active_trail);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    // The book active trail depends on the node and data attached to it.
    // That information is however not stored as part of the node.
    $cacheable_metadata = new CacheableMetadata();
    if ($node = $this->requestStack->getCurrentRequest()->get('node')) {
      // If the node is part of a book then we can use the cache tag for that
      // book. If not, then it can't be optimized away.
      if (!empty($node->book['bid'])) {
        $cacheable_metadata->addCacheTags(['bid:' . $node->book['bid']]);
      }
      else {
        $cacheable_metadata->setCacheMaxAge(0);
      }
    }
    return $cacheable_metadata;
  }

}
