<?php

/**
 * @file
 * Documentation for aggregator API.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Create an alternative fetcher for aggregator.module.
 *
 * A fetcher downloads feed data to a Drupal site. The fetcher is called at the
 * first of the three aggregation stages: first, data is downloaded by the
 * active fetcher; second, it is converted to a common format by the active
 * parser; and finally, it is passed to all active processors, which manipulate
 * or store the data.
 *
 * Modules that define this hook can be set as the active fetcher within the
 * configuration page. Only one fetcher can be active at a time.
 *
 * @param $feed
 *   A feed object representing the resource to be downloaded. $feed->url
 *   contains the link to the feed. Download the data at the URL and expose it
 *   to other modules by attaching it to $feed->source_string.
 *
 * @return
 *   TRUE if fetching was successful, FALSE otherwise.
 *
 * @see hook_aggregator_fetch_info()
 * @see hook_aggregator_parse()
 * @see hook_aggregator_process()
 *
 * @ingroup aggregator
 */
function hook_aggregator_fetch($feed) {
  $feed->source_string = mymodule_fetch($feed->url);
}

/**
 * Specify the title and short description of your fetcher.
 *
 * The title and the description provided are shown within the configuration
 * page. Use as title the human readable name of the fetcher and as description
 * a brief (40 to 80 characters) explanation of the fetcher's functionality.
 *
 * This hook is only called if your module implements hook_aggregator_fetch().
 * If this hook is not implemented aggregator will use your module's file name
 * as title and there will be no description.
 *
 * @return
 *   An associative array defining a title and a description string.
 *
 * @see hook_aggregator_fetch()
 *
 * @ingroup aggregator
 */
function hook_aggregator_fetch_info() {
  return array(
    'title' => t('Default fetcher'),
    'description' => t('Default fetcher for resources available by URL.'),
  );
}

/**
 * Create an alternative parser for aggregator module.
 *
 * A parser converts feed item data to a common format. The parser is called
 * at the second of the three aggregation stages: first, data is downloaded
 * by the active fetcher; second, it is converted to a common format by the
 * active parser; and finally, it is passed to all active processors which
 * manipulate or store the data.
 *
 * Modules that define this hook can be set as the active parser within the
 * configuration page. Only one parser can be active at a time.
 *
 * @param $feed
 *   An object describing the resource to be parsed. $feed->source_string
 *   contains the raw feed data. The hook implementation should parse this data
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
 * @return
 *   TRUE if parsing was successful, FALSE otherwise.
 *
 * @see hook_aggregator_parse_info()
 * @see hook_aggregator_fetch()
 * @see hook_aggregator_process()
 *
 * @ingroup aggregator
 */
function hook_aggregator_parse($feed) {
  if ($items = mymodule_parse($feed->source_string)) {
    $feed->items = $items;
    return TRUE;
  }
  return FALSE;
}

/**
 * Specify the title and short description of your parser.
 *
 * The title and the description provided are shown within the configuration
 * page. Use as title the human readable name of the parser and as description
 * a brief (40 to 80 characters) explanation of the parser's functionality.
 *
 * This hook is only called if your module implements hook_aggregator_parse().
 * If this hook is not implemented aggregator will use your module's file name
 * as title and there will be no description.
 *
 * @return
 *   An associative array defining a title and a description string.
 *
 * @see hook_aggregator_parse()
 *
 * @ingroup aggregator
 */
function hook_aggregator_parse_info() {
  return array(
    'title' => t('Default parser'),
    'description' => t('Default parser for RSS, Atom and RDF feeds.'),
  );
}

/**
 * Create a processor for aggregator.module.
 *
 * A processor acts on parsed feed data. Active processors are called at the
 * third and last of the aggregation stages: first, data is downloaded by the
 * active fetcher; second, it is converted to a common format by the active
 * parser; and finally, it is passed to all active processors that manipulate or
 * store the data.
 *
 * Modules that define this hook can be activated as a processor within the
 * configuration page.
 *
 * @param $feed
 *   A feed object representing the resource to be processed. $feed->items
 *   contains an array of feed items downloaded and parsed at the parsing stage.
 *   See hook_aggregator_parse() for the basic format of a single item in the
 *   $feed->items array. For the exact format refer to the particular parser in
 *   use.
 *
 * @see hook_aggregator_process_info()
 * @see hook_aggregator_fetch()
 * @see hook_aggregator_parse()
 *
 * @ingroup aggregator
 */
function hook_aggregator_process($feed) {
  foreach ($feed->items as $item) {
    mymodule_save($item);
  }
}

/**
 * Specify the title and short description of your processor.
 *
 * The title and the description provided are shown within the configuration
 * page. Use as title the natural name of the processor and as description a
 * brief (40 to 80 characters) explanation of the functionality.
 *
 * This hook is only called if your module implements hook_aggregator_process().
 * If this hook is not implemented aggregator will use your module's file name
 * as title and there will be no description.
 *
 * @return
 *   An associative array defining a title and a description string.
 *
 * @see hook_aggregator_process()
 *
 * @ingroup aggregator
 */
function hook_aggregator_process_info($feed) {
  return array(
    'title' => t('Default processor'),
    'description' => t('Creates lightweight records of feed items.'),
  );
}

/**
 * Remove stored feed data.
 *
 * Aggregator calls this hook if either a feed is deleted or a user clicks on
 * "remove items".
 *
 * If your module stores feed items for example on hook_aggregator_process() it
 * is recommended to implement this hook and to remove data related to $feed
 * when called.
 *
 * @param $feed
 *   The $feed object whose items are being removed.
 *
 * @ingroup aggregator
 */
function hook_aggregator_remove($feed) {
  mymodule_remove_items($feed->fid);
}

/**
 * @} End of "addtogroup hooks".
 */
