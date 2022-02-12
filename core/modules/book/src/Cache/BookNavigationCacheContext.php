<?php

namespace Drupal\book\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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
class BookNavigationCacheContext implements CacheContextInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new BookNavigationCacheContext service.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
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
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface && !empty($node->book['bid'])) {
      $current_bid = $node->book['bid'];
    }

    // If we're not looking at a book node, then we're not navigating a book.
    if ($current_bid === 0) {
      return 'book.none';
    }

    // If we're looking at a book node, get the trail for that node.
    $active_trail = $this->container->get('book.manager')
      ->getActiveTrailIds($node->book['bid'], $node->book);
    return implode('|', $active_trail);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    // The book active trail depends on the node and data attached to it.
    // That information is however not stored as part of the node.
    $cacheable_metadata = new CacheableMetadata();
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface) {
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
