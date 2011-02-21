<?php
// $Id: menu.inc,v 1.7 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * General menu helper functions.
 */

/**
 * Dynamically add a tab to the current path.
 *
 * This function is a simplified interface for adding tabs to the current path.
 * Some considerations when doing this:
 *
 * - First, if there is only 1 tab, Drupal will not show it. Therefore, if
 *   you are only adding one tab, you should find a way to make sure there is
 *   already tab, or instead add 2.
 * - Second, the caller is responsible for providing access control to these
 *   links.
 *
 * @param $link
 *   An array describing this link. It must contain:
 *   - 'title': The printed title of the link.
 *   - 'href': The path of the link. This is an argument to l() so it has all
 *     of those features and limitations.
 *   - 'options': Any options that go to l, including query, fragment and html
 *     options necessary.
 *   - 'weight': The weight to use in ordering the tabs.
 *   - 'type': Optional. If set to MENU_DEFAULT_LOCAL_TASK this can be used to
 *     add a fake 'default' local task, which is useful if you have to add
 *     tabs to a page that has noen.
 */
function ctools_menu_add_tab($link = NULL) {
  $links = &drupal_static(__FUNCTION__, array());
  if (isset($link)) {
    $links[$link['href']] = $link;
  }

  return $links;
}

/**
 * Re-sort menu items after we have modified them.
 */
function ctools_menu_sort($a, $b) {
  $a_weight = (is_array($a) && isset($a['#link']['weight'])) ? $a['#link']['weight'] : 0;
  $b_weight = (is_array($b) && isset($b['#link']['weight'])) ? $b['#link']['weight'] : 0;
  if ($a_weight == $b_weight) {
    $a_title = (is_array($a) && isset($a['#link']['title'])) ? $a['#link']['title'] : 0;
    $b_title = (is_array($b) && isset($b['#link']['title'])) ? $b['#link']['title'] : 0;
    if ($a_title == $b_title) {
      return 0;
    }

    return ($a_title < $b_title) ? -1 : 1;
  }

  return ($a_weight < $b_weight) ? -1 : 1;
}

function _ctools_menu_add_dynamic_items(&$data, &$router_item, &$root_path) {
  if ($additions = ctools_menu_add_tab()) {
    // If none of the static local tasks are active allow one of the dynamic
    // active tasks to be marked as such.
    $has_active = FALSE;
    if (!empty($data['tabs'][0]['output'])) {
      foreach ($data['tabs'][0]['output'] as $element) {
        if (!empty($element['#link']['#active'])) {
          $has_active = TRUE;
        }
      }
    }
    foreach ($additions as $addition) {
      $addition['localized_options'] = isset($addition['options']) ? $addition['options'] : array();
      if (isset($addition['type']) && $addition['type'] == MENU_LOCAL_ACTION) {
        $data['actions']['output'][] = array(
          '#theme' => 'menu_local_action',
          '#link' => $addition,
        );
      }
      else {
        $data['tabs'][0]['output'][] = array(
          '#theme' => 'menu_local_task',
          '#link' => $addition,
          '#active' => (!$has_active && $root_path === $addition['href']),
        );
      }
    }
    if (!empty($data['tabs'][0]['output'])) {
      uasort($data['tabs'][0]['output'], 'ctools_menu_sort');
      $data['tabs'][0]['count'] = count($data['tabs'][0]['output']);
    }

    if (!empty($data['actions']['output'])) {
      uasort($data['actions']['output'], 'ctools_menu_sort');
      $data['actions']['count'] = count($data['actions']['output']);
    }
  }
}
