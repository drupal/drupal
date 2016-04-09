<?php

namespace Drupal\Core\Asset;

/**
 * Groups CSS assets.
 */
class CssCollectionGrouper implements AssetCollectionGrouperInterface {

  /**
   * {@inheritdoc}
   *
   * Puts multiple items into the same group if they are groupable and if they
   * are for the same 'media' and 'browsers'. Items of the 'file' type are
   * groupable if their 'preprocess' flag is TRUE, items of the 'inline' type
   * are always groupable, and items of the 'external' type are never groupable.
   *
   * Also ensures that the process of grouping items does not change their
   * relative order. This requirement may result in multiple groups for the same
   * type, media, and browsers, if needed to accommodate other items in between.
   */
  public function group(array $css_assets) {
    $groups = array();
    // If a group can contain multiple items, we track the information that must
    // be the same for each item in the group, so that when we iterate the next
    // item, we can determine if it can be put into the current group, or if a
    // new group needs to be made for it.
    $current_group_keys = NULL;
    // When creating a new group, we pre-increment $i, so by initializing it to
    // -1, the first group will have index 0.
    $i = -1;
    foreach ($css_assets as $item) {
      // The browsers for which the CSS item needs to be loaded is part of the
      // information that determines when a new group is needed, but the order
      // of keys in the array doesn't matter, and we don't want a new group if
      // all that's different is that order.
      ksort($item['browsers']);

      // If the item can be grouped with other items, set $group_keys to an
      // array of information that must be the same for all items in its group.
      // If the item can't be grouped with other items, set $group_keys to
      // FALSE. We put items into a group that can be aggregated together:
      // whether they will be aggregated is up to the _drupal_css_aggregate()
      // function or an
      // override of that function specified in hook_css_alter(), but regardless
      // of the details of that function, a group represents items that can be
      // aggregated. Since a group may be rendered with a single HTML tag, all
      // items in the group must share the same information that would need to
      // be part of that HTML tag.
      switch ($item['type']) {
        case 'file':
          // Group file items if their 'preprocess' flag is TRUE.
          // Help ensure maximum reuse of aggregate files by only grouping
          // together items that share the same 'group' value.
          $group_keys = $item['preprocess'] ? array($item['type'], $item['group'], $item['media'], $item['browsers']) : FALSE;
          break;

        case 'inline':
          // Always group inline items.
          $group_keys = array($item['type'], $item['media'], $item['browsers']);
          break;

        case 'external':
          // Do not group external items.
          $group_keys = FALSE;
          break;
      }

      // If the group keys don't match the most recent group we're working with,
      // then a new group must be made.
      if ($group_keys !== $current_group_keys) {
        $i++;
        // Initialize the new group with the same properties as the first item
        // being placed into it. The item's 'data', 'weight' and 'basename'
        // properties are unique to the item and should not be carried over to
        // the group.
        $groups[$i] = $item;
        unset($groups[$i]['data'], $groups[$i]['weight'], $groups[$i]['basename']);
        $groups[$i]['items'] = array();
        $current_group_keys = $group_keys ? $group_keys : NULL;
      }

      // Add the item to the current group.
      $groups[$i]['items'][] = $item;
    }

    return $groups;
  }

}
