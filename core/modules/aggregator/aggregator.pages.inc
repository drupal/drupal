<?php

/**
 * @file
 * User page callbacks for the Aggregator module.
 */

/**
 * Loads and optionally filters feed items.
 *
 * @param string $type
 *   The type of filter for the items. Possible values are:
 *   - sum: No filtering.
 *   - source: Filter the feed items, limiting the result to items from a
 *     single source.
 * @param mixed $data
 *   Feed data used for filtering. The type and value of $data depends on $type:
 *   - source: $data is an object with $data->fid identifying the feed used to
 *     as filter.
 *   The $data parameter is not used when $type is 'sum'.
 * @param int $limit
 *   (optional) The number of records to return. Defaults to 20.
 *
 * @deprecated Use \Drupal\aggregator\ItemStorageController::loadAll() for
 *   loading all feed items, \Drupal\aggregator\ItemStorageController::loadByFeed()
 *
 * @return \Drupal\aggregator\ItemInterface[]
 *   An array of the feed items.
 */
function aggregator_load_feed_items($type, $data = NULL, $limit = 20) {
  $storage_controller = \Drupal::entityManager()->getStorageController('aggregator_item');
  switch ($type) {
    case 'sum':
      return $storage_controller->loadAll($limit);

    case 'source':
      return $storage_controller->loadByFeed($data->id(), $limit);
  }
}
