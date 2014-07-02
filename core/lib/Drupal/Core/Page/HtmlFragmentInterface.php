<?php

/**
 * @file
 * Contains \Drupal\Core\Page\HtmlFragmentInterface.
 */

namespace Drupal\Core\Page;

/**
 * A domain object for a portion of an HTML page, including related data.
 *
 * Related data includes any additional information relevant to a fragment of
 * HTML that would not be part of the HTML string itself. That includes, for
 * example, required CSS files, Javascript files, link tags, meta tags, and the
 * title of a page or page section.
 *
 * @ingroup menu
 */
interface HtmlFragmentInterface {

  /**
   * Indicates whether or not this HtmlFragment has a title.
   *
   * @return bool
   */
  public function hasTitle();

  /**
   * Gets the title for this HtmlFragment, if any.
   *
   * @return string
   *   The title.
   */
  public function getTitle();

  /**
   * Gets the main content of this HtmlFragment.
   *
   * @return string
   *   The content for this fragment.
   */
  public function getContent();

  /**
   * Returns an array of all enqueued links.
   *
   * @return \Drupal\Core\Page\LinkElement[]
   */
  public function getLinkElements();

  /**
   * Returns all feed link elements.
   *
   * @return \Drupal\Core\Page\FeedLinkElement[]
   */
  public function getFeedLinkElements();

  /**
   * Returns an array of all enqueued meta elements.
   *
   * @return \Drupal\Core\Page\MetaElement[]
   */
  public function getMetaElements();

}
