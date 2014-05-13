<?php

/**
 * @file
 * Contains \Drupal\Core\Page\FeedLinkElement.
 */

namespace Drupal\Core\Page;

/**
 * Defines a link to a feed.
 */
class FeedLinkElement extends LinkElement {

  /**
   * Creates a FeedLink instance.
   *
   * @param string $title
   *   The title of the feed.
   * @param string $href
   *   The absolute URL to the feed.
   */
  public function __construct($title, $href) {
    $rel = 'alternate';
    $attributes['title'] = $title;
    $attributes['type'] = 'application/rss+xml';

    parent::__construct($href, $rel, $attributes);
  }

}

