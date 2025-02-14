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
    foreach ($css_assets as $item) {

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
          // together items that share the same 'group' value. The CSS optimizer
          // adds inline 'media' statements for everything except 'print', so
          // only vary groups based on that.
          $group_keys = $item['preprocess'] ? [$item['type'], $item['group'], $item['media'] === 'print'] : FALSE;
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
        if ($item['media'] !== 'print') {
          $groups[$i]['media'] = 'all';
        }
        unset($groups[$i]['data'], $groups[$i]['weight'], $groups[$i]['basename']);
        $groups[$i]['items'] = [];
        $current_group_keys = $group_keys ?: NULL;
      }

      // Add the item to the current group.
      $groups[$i]['items'][] = $item;
    }

    return $groups;
  }

}
