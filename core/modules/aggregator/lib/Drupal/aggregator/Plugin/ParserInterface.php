<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\FetcherInterface.
 */

namespace Drupal\aggregator\Plugin;

use Drupal\aggregator\FeedInterface;

/**
 * Defines an interface for aggregator parser implementations.
 *
 * A parser converts feed item data to a common format. The parser is called
 * at the second of the three aggregation stages: first, data is downloaded
 * by the active fetcher; second, it is converted to a common format by the
 * active parser; and finally, it is passed to all active processors which
 * manipulate or store the data.
 *
 */
interface ParserInterface {

  /**
   * Parses feed data.
   *
   * @param \Drupal\aggregator\FeedInterface $feed
   *   An object describing the resource to be parsed.
   *   $feed->source_string->value contains the raw feed data. Parse the data
   *   and add the following properties to the $feed object:
   *   - description: The human-readable description of the feed.
   *   - link: A full URL that directly relates to the feed.
   *   - image: An image URL used to display an image of the feed.
   *   - etag: An entity tag from the HTTP header used for cache validation to
   *     determine if the content has been changed.
   *   - modified: The UNIX timestamp when the feed was last modified.
   *   - items: An array of feed items. The common format for a single feed item
   *     is an associative array containing:
   *     - title: The human-readable title of the feed item.
   *     - description: The full body text of the item or a summary.
   *     - timestamp: The UNIX timestamp when the feed item was last published.
   *     - author: The author of the feed item.
   *     - guid: The global unique identifier (GUID) string that uniquely
   *       identifies the item. If not available, the link is used to identify
   *       the item.
   *     - link: A full URL to the individual feed item.
   *
   * @return bool
   *   TRUE if parsing was successful, FALSE otherwise.
   */
  public function parse(FeedInterface $feed);

}
