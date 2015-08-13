<?php

/**
 * @file
 * Contains \Drupal\Core\Breadcrumb\Breadcrumb.
 */

namespace Drupal\Core\Breadcrumb;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;

/**
 * Used to return generated breadcrumbs with associated cacheability metadata.
 *
 * @todo implement RenderableInterface once https://www.drupal.org/node/2529560 lands.
 */
class Breadcrumb extends CacheableMetadata {

  /**
   * An ordered list of links for the breadcrumb.
   *
   * @var \Drupal\Core\Link[]
   */
  protected $links = [];

  /**
   * Gets the breadcrumb links.
   *
   * @return \Drupal\Core\Link[]
   */
  public function getLinks() {
    return $this->links;
  }

  /**
   * Sets the breadcrumb links.
   *
   * @param \Drupal\Core\Link[] $links
   *   The breadcrumb links.
   *
   * @return $this
   *
   * @throws \LogicException
   *   Thrown when setting breadcrumb links after they've already been set.
   */
  public function setLinks(array $links) {
    if (!empty($this->links)) {
      throw new \LogicException('Once breadcrumb links are set, only additional breadcrumb links can be added.');
    }

    $this->links = $links;

    return $this;
  }

  /**
   * Appends a link to the end of the ordered list of breadcrumb links.
   *
   * @param \Drupal\Core\Link $link
   *   The link appended to the breadcrumb.
   *
   * @return $this
   */
  public function addLink(Link $link) {
    $this->links[] = $link;

    return $this;
  }

}
