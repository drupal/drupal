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
 * Implement this hook to create an alternative fetcher for aggregator module.
 *
 * A fetcher downloads feed data to a Drupal site. The fetcher is called
 * at the first of the three aggregation stages: data is downloaded by the
 * active fetcher, it is converted to a common format by the active parser and
 * finally, it is passed to all active processors which manipulate or store the
 * data.
 *
 * Modules that define this hook can be set as active fetcher on
 * admin/config/services/aggregator. Only one fetcher can be active at a time.
 *
 * @param $feed
 *   The $feed object that describes the resource to be downloaded.
 *   $feed->url contains the link to the feed. Download the data at the URL
 *   and expose it to other modules by attaching it to $feed->source_string.
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
 * Implement this hook to expose the title and a short description of your
 * fetcher.
 *
 * The title and the description provided are shown on
 * admin/config/services/aggregator among other places. Use as title the human
 * readable name of the fetcher and as description a brief (40 to 80 characters)
 * explanation of the fetcher's functionality.
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
 * Implement this hook to create an alternative parser for aggregator module.
 *
 * A parser converts feed item data to a common format. The parser is called
 * at the second of the three aggregation stages: data is downloaded by the
 * active fetcher, it is converted to a common format by the active parser and
 * finally, it is passed to all active processors which manipulate or store the
 * data.
 *
 * Modules that define this hook can be set as active parser on
 * admin/config/services/aggregator. Only one parser can be active at a time.
 *
 * @param $feed
 *   The $feed object that describes the resource to be parsed.
 *   $feed->source_string contains the raw feed data as a string. Parse data
 *   from $feed->source_string and expose it to other modules as an array of
 *   data items on $feed->items.
 *
 *   Feed format:
 *   - $feed->description (string) - description of the feed
 *   - $feed->image (string) - image for the feed
 *   - $feed->etag (string) - value of feed's entity tag header field
 *   - $feed->modified (UNIX timestamp) - value of feed's last modified header
 *     field
 *   - $feed->items (Array) - array of feed items.
 *
 *   By convention, the common format for a single feed item is:
 *   $item[key-name] = value;
 *
 *   Recognized keys:
 *   TITLE (string) - the title of a feed item
 *   DESCRIPTION (string) - the description (body text) of a feed item
 *   TIMESTAMP (UNIX timestamp) - the feed item's published time as UNIX timestamp
 *   AUTHOR (string) - the feed item's author
 *   GUID (string) - RSS/Atom global unique identifier
 *   LINK (string) - the feed item's URL
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
 * Implement this hook to expose the title and a short description of your
 * parser.
 *
 * The title and the description provided are shown on
 * admin/config/services/aggregator among other places. Use as title the human
 * readable name of the parser and as description a brief (40 to 80 characters)
 * explanation of the parser's functionality.
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
 * Implement this hook to create a processor for aggregator module.
 *
 * A processor acts on parsed feed data. Active processors are called at the
 * third and last of the aggregation stages: data is downloaded by the active
 * fetcher, it is converted to a common format by the active parser and
 * finally, it is passed to all active processors which manipulate or store the
 * data.
 *
 * Modules that define this hook can be activated as processor on
 * admin/config/services/aggregator.
 *
 * @param $feed
 *   The $feed object that describes the resource to be processed. $feed->items
 *   contains an array of feed items downloaded and parsed at the parsing
 *   stage. See hook_aggregator_parse() for the basic format of a single item
 *   in the $feed->items array. For the exact format refer to the particular
 *   parser in use.
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
 * Implement this hook to expose the title and a short description of your
 * processor.
 *
 * The title and the description provided are shown most importantly on
 * admin/config/services/aggregator. Use as title the natural name of the
 * processor and as description a brief (40 to 80 characters) explanation of
 * the functionality.
 *
 * This hook is only called if your module implements
 * hook_aggregator_process(). If this hook is not implemented aggregator
 * will use your module's file name as title and there will be no description.
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
 * Implement this hook to remove stored data if a feed is being deleted or a
 * feed's items are being removed.
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
