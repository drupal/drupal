<?php
// $Id: search.api.php,v 1.27 2010/06/01 17:56:46 dries Exp $

/**
 * @file
 * Hooks provided by the Search module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Define a custom search type.
 *
 * This hook allows a module to tell search.module that it wishes to perform
 * searches on content it defines (custom node types, users, or comments for
 * example) when a site search is performed.
 *
 * In order for the search to do anything, your module must also implement
 * hook_search_execute(), which is called when someone requests a search
 * on your module's type of content. If you want to have your content
 * indexed in the standard search index, your module should also implement
 * hook_update_index(). If your search type has settings, you can implement
 * hook_search_admin() to add them to the search settings page. You can also
 * alter the display of your module's search results by implementing
 * hook_search_page(). And you can use hook_form_FORM_ID_alter(), with
 * FORM_ID set to 'search', to add fields to the search form. See
 * node_form_search_form_alter() for an example.
 *
 * @return
 *   Array with the optional keys 'title' for the tab title and 'path' for
 *   the path component after 'search/'.  Both will default to the module
 *   name.
 *
 * @ingroup search
 */
function hook_search_info() {
  return array(
    'title' => 'Content',
    'path' => 'node',
  );
}

/**
 * Define access to a custom search routine.
 *
 * This hook allows a module to define permissions for a search tab.
 *
 * @ingroup search
 */
function hook_search_access() {
  return user_access('access content');
}

/**
 * Take action when the search index is going to be rebuilt.
 *
 * Modules that use hook_update_index() should update their indexing
 * bookkeeping so that it starts from scratch the next time
 * hook_update_index() is called.
 *
 * @ingroup search
 */
function hook_search_reset() {
  db_update('search_dataset')
    ->fields(array('reindex' => REQUEST_TIME))
    ->condition('type', 'node')
    ->execute();
}

/**
 * Report the status of indexing.
 *
 * @return
 *  An associative array with the key-value pairs:
 *  - 'remaining': The number of items left to index.
 *  - 'total': The total number of items to index.
 *
 * @ingroup search
 */
function hook_search_status() {
  $total = db_query('SELECT COUNT(*) FROM {node} WHERE status = 1')->fetchField();
  $remaining = db_query("SELECT COUNT(*) FROM {node} n LEFT JOIN {search_dataset} d ON d.type = 'node' AND d.sid = n.nid WHERE n.status = 1 AND d.sid IS NULL OR d.reindex <> 0")->fetchField();
  return array('remaining' => $remaining, 'total' => $total);
}

/**
 * Add elements to the search settings form.
 *
 * @return
 *   Form array for the Search settings page at admin/config/search/settings.
 *
 * @ingroup search
 */
function hook_search_admin() {
  // Output form for defining rank factor weights.
  $form['content_ranking'] = array(
    '#type' => 'fieldset',
    '#title' => t('Content ranking'),
  );
  $form['content_ranking']['#theme'] = 'node_search_admin';
  $form['content_ranking']['info'] = array(
    '#value' => '<em>' . t('The following numbers control which properties the content search should favor when ordering the results. Higher numbers mean more influence, zero means the property is ignored. Changing these numbers does not require the search index to be rebuilt. Changes take effect immediately.') . '</em>'
  );

  // Note: reversed to reflect that higher number = higher ranking.
  $options = drupal_map_assoc(range(0, 10));
  foreach (module_invoke_all('ranking') as $var => $values) {
    $form['content_ranking']['factors']['node_rank_' . $var] = array(
      '#title' => $values['title'],
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => variable_get('node_rank_' . $var, 0),
    );
  }
  return $form;
}

/**
 * Execute a search for a set of key words.
 *
 * Use database API with the 'PagerDefault' query extension to perform your
 * search.
 *
 * If your module uses hook_update_index() and search_index() to index its
 * items, use table 'search_index' aliased to 'i' as the main table in your
 * query, with the 'SearchQuery' extension. You can join to your module's table
 * using the 'i.sid' field, which will contain the $sid values you provided to
 * search_index(). Add the main keywords to the query by using method
 * searchExpression(). The functions search_expression_extract() and
 * search_expression_insert() may also be helpful for adding custom search
 * parameters to the search expression.
 *
 * See node_execute_search() for an example of a module that uses the search
 * index, and user_execute_search() for an example that doesn't ues the search
 * index.
 *
 * @param $keys
 *   The search keywords as entered by the user.
 *
 * @return
 *   An array of search results. To use the default search result
 *   display, each item should have the following keys':
 *   - 'link': Required. The URL of the found item.
 *   - 'type': The type of item.
 *   - 'title': Required. The name of the item.
 *   - 'user': The author of the item.
 *   - 'date': A timestamp when the item was last modified.
 *   - 'extra': An array of optional extra information items.
 *   - 'snippet': An excerpt or preview to show with the result (can be
 *     generated with search_excerpt()).
 *
 * @ingroup search
 */
function hook_search_execute($keys = NULL) {
  // Build matching conditions
  $query = db_select('search_index', 'i', array('target' => 'slave'))->extend('SearchQuery')->extend('PagerDefault');
  $query->join('node', 'n', 'n.nid = i.sid');
  $query
    ->condition('n.status', 1)
    ->addTag('node_access')
    ->searchExpression($keys, 'node');

  // Insert special keywords.
  $query->setOption('type', 'n.type');
  $query->setOption('language', 'n.language');
  if ($query->setOption('term', 'ti.tid')) {
    $query->join('taxonomy_index', 'ti', 'n.nid = ti.nid');
  }
  // Only continue if the first pass query matches.
  if (!$query->executeFirstPass()) {
    return array();
  }

  // Add the ranking expressions.
  _node_rankings($query);

  // Load results.
  $find = $query
    ->limit(10)
    ->execute();
  $results = array();
  foreach ($find as $item) {
    // Build the node body.
    $node = node_load($item->sid);
    node_build_content($node, 'search_result');
    $node->body = drupal_render($node->content);

    // Fetch comments for snippet.
    $node->rendered .= ' ' . module_invoke('comment', 'node_update_index', $node);
    // Fetch terms for snippet.
    $node->rendered .= ' ' . module_invoke('taxonomy', 'node_update_index', $node);

    $extra = module_invoke_all('node_search_result', $node);

    $results[] = array(
      'link' => url('node/' . $item->sid, array('absolute' => TRUE)),
      'type' => check_plain(node_type_get_name($node)),
      'title' => $node->title,
      'user' => theme('username', array('account' => $node)),
      'date' => $node->changed,
      'node' => $node,
      'extra' => $extra,
      'score' => $item->calculated_score,
      'snippet' => search_excerpt($keys, $node->body),
    );
  }
  return $results;
}

/**
 * Override the rendering of search results.
 *
 * A module that implements hook_search() to define a type of search
 * may implement this hook in order to override the default theming of
 * its search results, which is otherwise themed using theme('search_results').
 *
 * Note that by default, theme('search_results') and theme('search_result')
 * work together to create an ordered list (OL). So your hook_search_page()
 * implementation should probably do this as well.
 *
 * @see search-result.tpl.php, search-results.tpl.php
 *
 * @param $results
 *   An array of search results.
 *
 * @return
 *   An HTML string containing the formatted search results, with
 *   a pager included.
 */
function hook_search_page($results) {
  $output = '<ol class="search-results">';

  foreach ($results as $entry) {
    $output .= theme('search_result', $entry, $type);
  }
  $output .= '</ol>';
  $output .= theme('pager', NULL);

  return $output;
}

/**
 * Preprocess text for search.
 *
 * This hook is called to preprocess both the text added to the search index and
 * the keywords users have submitted for searching.
 *
 * Possible uses:
 * - Adding spaces between words of Chinese or Japanese text.
 * - Stemming words down to their root words to allow matches between, for
 *   instance, walk, walked, walking, and walks in searching.
 * - Expanding abbreviations and acronymns that occur in text.
 *
 * @param $text
 *   The text to preprocess. This is a single piece of plain text extracted
 *   from between two HTML tags or from the search query. It will not contain
 *   any HTML entities or HTML tags.
 *
 * @return
 *   The text after preprocessing. Note that if your module decides not to alter
 *   the text, it should return the original text. Also, after preprocessing,
 *   words in the text should be separated by a space.
 *
 * @ingroup search
 */
function hook_search_preprocess($text) {
  // Do processing on $text
  return $text;
}

/**
 * Update the search index for this module.
 *
 * This hook is called every cron run if search.module is enabled, your
 * module has implemented hook_search_info(), and your module has been set as
 * an active search module on the Search settings page
 * (admin/config/search/settings). It allows your module to add items to the
 * built-in search index using search_index(), or to add them to your module's
 * own indexing mechanism.
 *
 * When implementing this hook, your module should index content items that
 * were modified or added since the last run. PHP has a time limit
 * for cron, though, so it is advisable to limit how many items you index
 * per run using variable_get('search_cron_limit') (see example below). Also,
 * since the cron run could time out and abort in the middle of your run, you
 * should update your module's internal bookkeeping on when items have last
 * been indexed as you go rather than waiting to the end of indexing.
 *
 * @ingroup search
 */
function hook_update_index() {
  $limit = (int)variable_get('search_cron_limit', 100);

  $result = db_query_range("SELECT n.nid FROM {node} n LEFT JOIN {search_dataset} d ON d.type = 'node' AND d.sid = n.nid WHERE d.sid IS NULL OR d.reindex <> 0 ORDER BY d.reindex ASC, n.nid ASC", 0, $limit);

  foreach ($result as $node) {
    $node = node_load($node->nid);

    // Save the changed time of the most recent indexed node, for the search
    // results half-life calculation.
    variable_set('node_cron_last', $node->changed);

    // Render the node.
    node_build_content($node, 'search_index');
    $node->rendered = drupal_render($node->content);

    $text = '<h1>' . check_plain($node->title) . '</h1>' . $node->rendered;

    // Fetch extra data normally not visible
    $extra = module_invoke_all('node_update_index', $node);
    foreach ($extra as $t) {
      $text .= $t;
    }

    // Update index
    search_index($node->nid, 'node', $text);
  }
}
/**
 * @} End of "addtogroup hooks".
 */
