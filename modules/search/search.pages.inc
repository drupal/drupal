<?php
// $Id: search.pages.inc,v 1.1 2007/09/05 08:39:57 dries Exp $

/**
 * @file
 * User page callbacks for the search module.
 */

/**
 * Menu callback; presents the search form and/or search results.
 */
function search_view($type = 'node') {
  // Search form submits with POST but redirects to GET. This way we can keep
  // the search query URL clean as a whistle:
  // search/type/keyword+keyword
  if (!isset($_POST['form_id'])) {
    if ($type == '') {
      // Note: search/node can not be a default tab because it would take on the
      // path of its parent (search). It would prevent remembering keywords when
      // switching tabs. This is why we drupal_goto to it from the parent instead.
      drupal_goto('search/node');
    }

    $keys = search_get_keys();
    // Only perform search if there is non-whitespace search term:
    $results = '';
    if (trim($keys)) {
      // Log the search keys:
      watchdog('search', '%keys (@type).', array('%keys' => $keys, '@type' => module_invoke($type, 'search', 'name')), WATCHDOG_NOTICE, l(t('results'), 'search/'. $type .'/'. $keys));

      // Collect the search results:
      $results = search_data($keys, $type);

      if ($results) {
        $results = theme('box', t('Search results'), $results);
      }
      else {
        $results = theme('box', t('Your search yielded no results'), search_help('search#noresults', drupal_help_arg()));
      }
    }

    // Construct the search form.
    $output = drupal_get_form('search_form', NULL, $keys, $type);
    $output .= $results;

    return $output;
  }

  return drupal_get_form('search_form', NULL, empty($keys) ? array() : $keys, $type);
}

/**
 * Format the result page of a search query.
 *
 * Modules may implement hook_search_page() in order to override this default
 * function to display search results. In that case it is expected they provide
 * their own themeable functions.
 *
 * @param $results
 *   All search result as returned by hook_search().
 * @param $type
 *   The type of item found, such as "user" or "node".
 *
 * @ingroup themeable
 */
function theme_search_page($results, $type) {
  $output = '<dl class="search-results">';

  foreach ($results as $entry) {
    $output .= theme('search_item', $entry, $type);
  }
  $output .= '</dl>';
  $output .= theme('pager', NULL, 10, 0);

  return $output;
}


/**
 * Format a single result entry of a search query. This function is normally
 * called by theme_search_page() or hook_search_page().
 *
 * @param $item
 *   A single search result as returned by hook_search(). The result should be
 *   an array with keys "link", "title", "type", "user", "date", and "snippet".
 *   Optionally, "extra" can be an array of extra info to show along with the
 *   result.
 * @param $type
 *   The type of item found, such as "user" or "node".
 *
 * @ingroup themeable
 */
function theme_search_item($item, $type) {
  $output = ' <dt class="title"><a href="'. check_url($item['link']) .'">'. check_plain($item['title']) .'</a></dt>';
  $info = array();
  if (!empty($item['type'])) {
    $info[] = $item['type'];
  }
  if (!empty($item['user'])) {
    $info[] = $item['user'];
  }
  if (!empty($item['date'])) {
    $info[] = format_date($item['date'], 'small');
  }
  if (isset($item['extra']) && is_array($item['extra'])) {
    $info = array_merge($info, $item['extra']);
  }
  $output .= ' <dd>'. (!empty($item['snippet']) ? '<p>'. $item['snippet'] .'</p>' : '') .'<p class="search-info">'. implode(' - ', $info) .'</p></dd>';
  return $output;
}

/**
 * As the search form collates keys from other modules hooked in via
 * hook_form_alter, the validation takes place in _submit.
 * search_form_validate() is used solely to set the 'processed_keys' form
 * value for the basic search form.
 */
function search_form_validate($form, &$form_state) {
  form_set_value($form['basic']['inline']['processed_keys'], trim($form_state['values']['keys']), $form_state);
}

/**
 * Process a search form submission.
 */
function search_form_submit($form, &$form_state) {
  $keys = $form_state['values']['processed_keys'];
  if ($keys == '') {
    form_set_error('keys', t('Please enter some keywords.'));
    // Fall through to the drupal_goto() call.
  }

  $type = $form_state['values']['module'] ? $form_state['values']['module'] : 'node';
  $form_state['redirect'] = 'search/'. $type .'/'. $keys;
  return;
}

