<?php

namespace Drupal\Core\Asset;

/**
 * Groups JavaScript assets.
 */
class JsCollectionGrouper implements AssetCollectionGrouperInterface {

  /**
   * {@inheritdoc}
   *
   * Puts multiple items into the same group if they are groupable and if they
   * are for the same browsers. Items of the 'file' type are groupable if their
   * 'preprocess' flag is TRUE. Items of the 'external' type are not groupable.
   *
   * Also ensures that the process of grouping items does not change their
   * relative order. This requirement may result in multiple groups for the same
   * type and browsers, if needed to accommodate other items in between.
   */
  public function group(array $js_assets) {
    $groups = array();
    // If a group can contain multiple items, we track the information that must
    // be the same for each item in the group, so that when we iterate the next
    // item, we can determine if it can be put into the current group, or if a
    // new group needs to be made for it.
    $current_group_keys = NULL;
    $index = -1;
    foreach ($js_assets as $item) {
      // The browsers for which the JavaScript item needs to be loaded is part
      // of the information that determines when a new group is needed, but the
      // order of keys in the array doesn't matter, and we don't want a new
      // group if all that's different is that order.
      ksort($item['browsers']);

      switch ($item['type']) {
        case 'file':
          // Group file items if their 'preprocess' flag is TRUE.
          // Help ensure maximum reuse of aggregate files by only grouping
          // together items that share the same 'group' value.
          $group_keys = $item['preprocess'] ? array($item['type'], $item['group'], $item['browsers']) : FALSE;
          break;

        case 'external':
          // Do not group external items.
          $group_keys = FALSE;
          break;
      }

      // If the group keys don't match the most recent group we're working with,
      // then a new group must be made.
      if ($group_keys !== $current_group_keys) {
        $index++;
        // Initialize the new group with the same properties as the first item
        // being placed into it. The item's 'data' and 'weight' properties are
        // unique to the item and should not be carried over to the group.
        $groups[$index] = $item;
        unset($groups[$index]['data'], $groups[$index]['weight']);
        $groups[$index]['items'] = array();
        $current_group_keys = $group_keys ? $group_keys : NULL;
      }

      // Add the item to the current group.
      $groups[$index]['items'][] = $item;
    }

    return $groups;
  }

}
