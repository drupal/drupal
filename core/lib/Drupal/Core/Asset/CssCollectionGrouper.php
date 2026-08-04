<?php

namespace Drupal\Core\Asset;

/**
 * Groups CSS assets.
 */
class CssCollectionGrouper implements AssetCollectionGrouperInterface {

  /**
   * {@inheritdoc}
   *
   * Puts multiple items into the same group if they are groupable. Items of the
   * 'file' type are groupable if their 'preprocess' flag is TRUE, and items of
   * the 'external' type are never groupable. Items with a media type of 'print'
   * will be put into their own group so that they are not loaded on regular
   * page requests. Items with a media type of 'all' or 'screen' will be grouped
   * together (with media queries where necessary), to minimize the number of
   * separate aggregates.
   *
   * Also ensures that the process of grouping items does not change their
   * relative order. This requirement may result in multiple groups for the same
   * type and media, if needed to accommodate other items in between.
   */
  public function group(array $css_assets) {
    $groups = [];
    // If a group can contain multiple items, we track the information that must
    // be the same for each item in the group, so that when we iterate the next
    // item, we can determine if it can be put into the current group, or if a
    // new group needs to be made for it.
    $current_group_keys = NULL;
    // When creating a new group, we pre-increment $i, so by initializing it to
    // -1, the first group will have index 0.
    $i = -1;

    // If a group fully encapsulates a library and its dependencies, in that all
    // files are contained in the group, and no files from other libraries are
    // included, then when specified, the aggregate can be served with a
    // simplified URL to improve front end cache hit rates. In order to do this,
    // we need to keep track of whether libraries span multiple groups or not.
    $first_seen_libraries = [];
    $record_libraries = TRUE;

    foreach ($css_assets as $item) {
      // If the item can be grouped with other items, set $group_keys to an
      // array of information that must be the same for all items in its group.
      // If the item can't be grouped with other items, set $group_keys to
      // FALSE. Since a group may be rendered with a single HTML tag, all
      // items in the group must share the same information that would need to
      // be part of that HTML tag.
      switch ($item['type']) {
        case 'file':
          // Group file items if their 'preprocess' flag is TRUE. The CSS
          // optimizer adds inline 'media' statements for everything except
          // 'print', so only vary groups based on that.
          $group_keys = $item['preprocess'] ? [$item['type'], $item['media'] === 'print'] : FALSE;
          if ($item['aggregate_target']['css']) {
            if ($item['aggregate_target']['css'] === TRUE) {
              $group_keys[] = $item['library'];
            }
            else {
              $group_keys[] = $item['aggregate_target']['css'];
            }
            $group_keys[] = $item['category'];
          }
          break;

        case 'external':
          // Do not group external items.
          $group_keys = FALSE;
          break;
      }

      // If the group keys don't match the most recent group we're working with,
      // then a new group must be made.
      if ($group_keys !== $current_group_keys) {
        $record_libraries = TRUE;
        $i++;
        // Initialize the new group with the same properties as the first item
        // being placed into it. The item's 'data', 'weight' and 'basename'
        // properties are unique to the item and should not be carried over to
        // the group.
        $groups[$i] = $item;
        if ($item['media'] !== 'print') {
          $groups[$i]['media'] = 'all';
        }
        unset($groups[$i]['data'], $groups[$i]['weight'], $groups[$i]['basename'], $groups[$i]['library'], $groups[$i]['group']);
        $groups[$i]['items'] = [];
        $current_group_keys = $group_keys ?: NULL;
      }
      // Alter hooks can add items without a library.
      if ($item['aggregate_target']['css']) {
        if ($record_libraries) {
          $groups[$i]['libraries'][] = $item['library'];
        }
        // The first time a library is seen, add it to the list of libraries for
        // the group and record the index.
        $seen_index = $first_seen_libraries[$item['library']][$item['category']] ?? NULL;
        if ($seen_index === NULL) {
          $first_seen_libraries[$item['library']][$item['category']] = $i;
          $seen_index = $i;
        }
        // If a library has been seen in a previous index, this means that index
        // does not contain all of the assets for that library, so unset the
        // libraries key from that group.
        if ($seen_index !== $i) {
          unset($groups[$seen_index]['libraries']);
          unset($groups[$i]['libraries']);
          unset($groups[$seen_index]['category']);
          unset($groups[$i]['category']);
          // Stop recording libraries until the next group.
          $record_libraries = FALSE;
        }
      }

      // Add the item to the current group.
      $groups[$i]['items'][] = $item;
    }

    return $groups;
  }

}
