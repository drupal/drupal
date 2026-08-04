<?php

namespace Drupal\Core\Asset;

/**
 * Groups JavaScript assets.
 */
class JsCollectionGrouper implements AssetCollectionGrouperInterface {

  /**
   * {@inheritdoc}
   *
   * Puts multiple items into the same group if they are groupable. Items of
   * the 'file' type are groupable if their 'preprocess' flag is TRUE. Items of
   * the 'external' type are not groupable.
   *
   * Also ensures that the process of grouping items does not change their
   * relative order. This requirement may result in multiple groups for the same
   * type, if needed to accommodate other items in between.
   */
  public function group(array $js_assets) {
    $groups = [];
    // If a group can contain multiple items, we track the information that must
    // be the same for each item in the group, so that when we iterate the next
    // item, we can determine if it can be put into the current group, or if a
    // new group needs to be made for it.
    $current_group_keys = NULL;

    // If a group encapsulates one or more libraries, e.g. all relevant files
    // from each library are contained in the group, instead of across groups
    // due to weight etc. it is possible to generate an aggregate based purely
    // on the libraries represented in the group rather than the full context of
    // the request. In order to do this, we need to keep track of whether
    // libraries span multiple groups or not.
    $first_seen_libraries = [];
    $record_libraries = TRUE;

    $index = -1;
    foreach ($js_assets as $item) {
      switch ($item['type']) {
        case 'file':
          // Group file items if their 'preprocess' flag is TRUE.
          // Help ensure maximum reuse of aggregate files by only grouping
          // together items that share the same 'group' value.
          $group_keys = $item['preprocess'] ? [$item['type'], $item['group']] : FALSE;
          if ($item['aggregate_target']['js'] === TRUE) {
            $group_keys[] = $item['library'];
          }
          elseif ($item['aggregate_target']['js']) {
            $group_keys[] = $item['aggregate_target']['js'];
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
        $index++;
        // Initialize the new group with the same properties as the first item
        // being placed into it. The item's 'data' and 'weight' properties are
        // unique to the item and should not be carried over to the group.
        $groups[$index] = $item;
        unset($groups[$index]['data'], $groups[$index]['weight'], $groups[$index]['library']);
        $groups[$index]['items'] = [];
        $current_group_keys = $group_keys ?: NULL;
      }
      $seen_index = $first_seen_libraries[$item['library']] ?? NULL;

      // The first time a library is seen, add it to the list of libraries for
      // the group and record the index.
      if ($seen_index === NULL) {
        $first_seen_libraries[$item['library']] = $index;
        if ($record_libraries) {
          $groups[$index]['libraries'][] = $item['library'];
        }
      }

      // If a library has been seen in a previous index, this means that index
      // does not contain all of the assets for that library, so unset the
      // libraries key from that group and this one.
      if ($seen_index !== NULL && $seen_index !== $index) {
        unset($groups[$seen_index]['libraries']);
        unset($groups[$index]['libraries']);
        $record_libraries = FALSE;
      }

      // Add the item to the current group.
      $groups[$index]['items'][] = $item;
    }
    // Ensure libraries only appear once for each group and in the same order
    // every time.
    foreach ($groups as $index => $group) {
      if (isset($group['libraries'])) {
        $groups[$index]['libraries'] = array_values(array_unique($group['libraries']));
      }
    }

    return $groups;
  }

}
