<?php
// $Id$

/**
 * @file
 * Hooks provided by the Search module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Define a custom search routine.
 *
 * This hook allows a module to perform searches on content it defines
 * (custom node types, users, or comments, for example) when a site search
 * is performed. 
 *
 * Note that you can use form API to extend the search. You will need to use
 * hook_form_alter() to add any additional required form elements. You can
 * process their values on submission using a custom validation function.
 * You will need to merge any custom search values into the search keys
 * using a key:value syntax. This allows all search queries to have a clean
 * and permanent URL. See node_form_search_form_alter() for an example.
 *
 * You can also alter the display of your module's search results
 * by implementing hook_search_page().
 *
 * The example given here is for node.module, which uses the indexed search
 * capabilities. To do this, node module also implements hook_update_index()
 * which is used to create and maintain the index.
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
 * This hook allows a module to deny access to a user to a search tab.
 *
 * @ingroup search
 */
function hook_search_access() {
  return user_access('access content');
}

/**
 * The search index is going to be rebuilt.
 *
 * Modules which use  hook_update_index() should update their indexing
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
 * Report the stutus of indexing.
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
 * Add elements to the search administration form.
 *
 * @return
 *   The form array for the Search settings page at admin/config/search/settings.
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
 * We call do_search() with the keys, the module name, and extra SQL fragments
 * to use when searching. See hook_update_index() for more information.
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
  $query = db_search()->extend('PagerDefault');
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

  // Add a count query.
  $inner_query = clone $query;
  $count_query = db_select($inner_query->fields('i', array('sid')));
  $count_query->addExpression('COUNT(*)');
  $query->setCountQuery($count_query);
  $find = $query
    ->limit(10)
    ->execute();

  // Load results.
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
      'title' => $node->title[LANGUAGE_NONE][0]['value'],
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
 * its search results, which is otherwise themed using
 * theme('search_results').
 *
 * Note that by default, theme('search_results') and
 * theme('search_result') work together to create a definition
 * list. So your hook_search_page() implementation should probably do
 * this as well.
 *
 * @see search-result.tpl.php, search-results.tpl.php
 *
 * @param $results
 *   An array of search results.
 * @return
 *   An HTML string containing the formatted search results, with
 *   a pager included.
 */
function hook_search_page($results) {
  $output = '<dl class="search-results">';

  foreach ($results as $entry) {
    $output .= theme('search_result', $entry, $type);
  }
  $output .= '</dl>';
  $output .= theme('pager', NULL);

  return $output;
}

/**
 * Preprocess text for the search index.
 *
 * This hook is called both for text added to the search index, as well as
 * the keywords users have submitted for searching.
 *
 * This is required for example to allow Japanese or Chinese text to be
 * searched. As these languages do not use spaces, it needs to be split into
 * separate words before it can be indexed. There are various external
 * libraries for this.
 *
 * @param $text
 *   The text to split. This is a single piece of plain-text that was
 *   extracted from between two HTML tags. Will not contain any HTML entities.
 * @return
 *   The text after processing.
 *
 * @ingroup search
 */
function hook_search_preprocess($text) {
  // Do processing on $text
  return $text;
}

/**
 * Update Drupal's full-text index for this module.
 *
 * Modules can implement this hook if they want to use the full-text indexing
 * mechanism in Drupal.
 *
 * This hook is called every cron run if search.module is enabled. A module
 * should check which of its items were modified or added since the last
 * run. It is advised that you implement a throttling mechanism which indexes
 * at most 'search_cron_limit' items per run (see example below).
 *
 * You should also be aware that indexing may take too long and be aborted if
 * there is a PHP time limit. That's why you should update your internal
 * bookkeeping multiple times per run, preferably after every item that
 * is indexed.
 *
 * Per item that needs to be indexed, you should call search_index() with
 * its content as a single HTML string. The search indexer will analyse the
 * HTML and use it to assign higher weights to important words (such as
 * titles). It will also check for links that point to nodes, and use them to
 * boost the ranking of the target nodes.
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

    $text = '<h1>' . check_plain($node->title[LANGUAGE_NONE][0]['value']) . '</h1>' . $node->rendered;

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
