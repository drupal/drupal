<?php

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
 * This hook allows a module to tell the Search module that it wishes to
 * perform searches on content it defines (custom node types, users, or
 * comments for example) when a site search is performed.
 *
 * In order for the search to do anything, your module must also implement
 * hook_search_execute(), which is called when someone requests a search on
 * your module's type of content. If you want to have your content indexed
 * in the standard search index, your module should also implement
 * hook_update_index(). If your search type has settings, you can implement
 * hook_search_admin() to add them to the search settings page. You can use
 * hook_form_FORM_ID_alter(), with FORM_ID set to 'search_form', to add fields
 * to the search form (see node_form_search_form_alter() for an example).
 * You can use hook_search_access() to limit access to searching, and
 * hook_search_page() to override how search results are displayed.
 *
 * @return
 *   Array with optional keys:
 *   - title: Title for the tab on the search page for this module. Defaults to
 *     the module name if not given.
 *   - path: Path component after 'search/' for searching with this module.
 *     Defaults to the module name if not given.
 *   - conditions_callback: An implementation of callback_search_conditions().
 *
 * @ingroup search
 */
function hook_search_info() {
  return array(
    'title' => 'Content',
    'path' => 'node',
    'conditions_callback' => 'callback_search_conditions',
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
 * bookkeeping so that it starts from scratch the next time hook_update_index()
 * is called.
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
 * The core search module only invokes this hook on active modules.
 * Implementing modules do not need to check whether they are active when
 * calculating their return values.
 *
 * @return
 *  An associative array with the key-value pairs:
 *  - remaining: The number of items left to index.
 *  - total: The total number of items to index.
 *
 * @ingroup search
 */
function hook_search_status() {
  $total = db_query('SELECT COUNT(DISTINCT nid) FROM {node_field_data} WHERE status = 1')->fetchField();
  $remaining = db_query("SELECT COUNT(DISTINCT nid) FROM {node_field_data} n LEFT JOIN {search_dataset} d ON d.type = 'node' AND d.sid = n.nid WHERE n.status = 1 AND d.sid IS NULL OR d.reindex <> 0")->fetchField();
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
    '#type' => 'details',
    '#title' => t('Content ranking'),
  );
  $form['content_ranking']['#theme'] = 'node_search_admin';
  $form['content_ranking']['#tree'] = TRUE;
  $form['content_ranking']['info'] = array(
    '#value' => '<em>' . t('The following numbers control which properties the content search should favor when ordering the results. Higher numbers mean more influence, zero means the property is ignored. Changing these numbers does not require the search index to be rebuilt. Changes take effect immediately.') . '</em>'
  );

  // Note: reversed to reflect that higher number = higher ranking.
  $options = drupal_map_assoc(range(0, 10));
  $ranks = Drupal::config('node.settings')->get('search_rank');
  foreach (Drupal::moduleHandler()->invokeAll('ranking') as $var => $values) {
    $form['content_ranking']['factors'][$var] = array(
      '#title' => $values['title'],
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => isset($ranks[$var]) ? $ranks[$var] : 0,
    );
  }

  $form['#submit'][] = 'node_search_admin_submit';

  return $form;
}

/**
 * Execute a search for a set of key words.
 *
 * Use database API with the 'Drupal\Core\Database\Query\PagerSelectExtender'
 * query extension to perform your search.
 *
 * If your module uses hook_update_index() and search_index() to index its
 * items, use table 'search_index' aliased to 'i' as the main table in your
 * query, with the 'Drupal\search\SearchQuery' extension. You can join to your
 * module's table using the 'i.sid' field, which will contain the $sid values
 * you provided to search_index(). Add the main keywords to the query by using
 * method searchExpression(). The functions search_expression_extract() and
 * search_expression_insert() may also be helpful for adding custom search
 * parameters to the search expression.
 *
 * See node_search_execute() for an example of a module that uses the search
 * index, and user_search_execute() for an example that doesn't use the search
 * index.
 *
 * @param $keys
 *   The search keywords as entered by the user. Defaults to NULL.
 * @param $conditions
 *   (optional) An array of additional conditions, such as filters. Defaults to
 *   NULL.
 *
 * @return
 *   An array of search results. To use the default search result display, each
 *   item should have the following keys':
 *   - link: (required) The URL of the found item.
 *   - type: The type of item (such as the content type).
 *   - title: (required) The name of the item.
 *   - user: The author of the item.
 *   - date: A timestamp when the item was last modified.
 *   - extra: An array of optional extra information items.
 *   - snippet: An excerpt or preview to show with the result (can be generated
 *     with search_excerpt()).
 *   - language: Language code for the item (usually two characters).
 *
 * @ingroup search
 */
function hook_search_execute($keys = NULL, $conditions = NULL) {
  // Build matching conditions
  $query = db_select('search_index', 'i', array('target' => 'slave'))
    ->extend('Drupal\search\SearchQuery')
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
  $query->join('node_field_data', 'n', 'n.nid = i.sid');
  $query
    ->condition('n.status', 1)
    ->addTag('node_access')
    ->searchExpression($keys, 'node');

  // Insert special keywords.
  $query->setOption('type', 'n.type');
  $query->setOption('langcode', 'n.langcode');
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
    // Add the language code of the indexed item to the result of the query,
    // since the node will be rendered using the respective language.
    ->fields('i', array('langcode'))
    ->limit(10)
    ->execute();
  $results = array();
  foreach ($find as $item) {
    // Render the node.
    $node = node_load($item->sid);
    $build = node_view($node, 'search_result', $item->langcode);
    unset($build['#theme']);
    $node->rendered = drupal_render($build);

    // Fetch comments for snippet.
    $node->rendered .= ' ' . module_invoke('comment', 'node_update_index', $node, $item->langcode);

    $extra = Drupal::moduleHandler()->invokeAll('node_search_result', array($node, $item->langcode));

    $language = language_load($item->langcode);
    $uri = $node->uri();
    $username = array(
      '#theme' => 'username',
      '#account' => $node,
    );
    $results[] = array(
      'link' => url($uri['path'], array_merge($uri['options'], array('absolute' => TRUE, 'language' => $language))),
      'type' => check_plain(node_get_type_label($node)),
      'title' => $node->label($item->langcode),
      'user' => drupal_render($username),
      'date' => $node->getChangedTime(),
      'node' => $node,
      'extra' => $extra,
      'score' => $item->calculated_score,
      'snippet' => search_excerpt($keys, $node->rendered, $item->langcode),
      'langcode' => $node->language()->id,
    );
  }
  return $results;
}

/**
 * Override the rendering of search results.
 *
 * A module that implements hook_search_info() to define a type of search may
 * implement this hook in order to override the default theming of its search
 * results, which is otherwise themed using theme('search_results').
 *
 * Note that by default, theme('search_results') and theme('search_result')
 * work together to create an ordered list (OL). So your hook_search_page()
 * implementation should probably do this as well.
 *
 * @param $results
 *   An array of search results.
 *
 * @return
 *   A renderable array, which will render the formatted search results with a
 *   pager included.
 *
 * @see search-result.tpl.php
 * @see search-results.tpl.php
 */
function hook_search_page($results) {
  $output['prefix']['#markup'] = '<ol class="search-results">';

  foreach ($results as $entry) {
    $output[] = array(
      '#theme' => 'search_result',
      '#result' => $entry,
      '#module' => 'my_module_name',
    );
  }
  $pager = array(
    '#theme' => 'pager',
  );
  $output['suffix']['#markup'] = '</ol>' . drupal_render($pager);

  return $output;
}

/**
 * Preprocess text for search.
 *
 * This hook is called to preprocess both the text added to the search index
 * and the keywords users have submitted for searching.
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
 * @param $langcode
 *   The language code of the entity that has been found.
 *
 * @return
 *   The text after preprocessing. Note that if your module decides not to
 *   alter the text, it should return the original text. Also, after
 *   preprocessing, words in the text should be separated by a space.
 *
 * @ingroup search
 */
function hook_search_preprocess($text, $langcode = NULL) {
  // If the langcode is set to 'en' then add variations of the word "testing"
  // which can also be found during English language searches.
  if (isset($langcode) && $langcode == 'en') {
    // Add the alternate verb forms for the word "testing".
    if ($text == 'we are testing') {
      $text .= ' test tested';
    }
  }

  return $text;
}

/**
 * Update the search index for this module.
 *
 * This hook is called every cron run if the Search module is enabled, your
 * module has implemented hook_search_info(), and your module has been set as
 * an active search module on the Search settings page
 * (admin/config/search/settings). It allows your module to add items to the
 * built-in search index using search_index(), or to add them to your module's
 * own indexing mechanism.
 *
 * When implementing this hook, your module should index content items that
 * were modified or added since the last run. PHP has a time limit
 * for cron, though, so it is advisable to limit how many items you index
 * per run using Drupal::config('search.settings')->get('index.cron_limit') (see
 * example below). Also, since the cron run could time out and abort in the
 * middle of your run, you should update your module's internal bookkeeping on
 * when items have last been indexed as you go rather than waiting to the end
 * of indexing.
 *
 * @ingroup search
 */
function hook_update_index() {
  $limit = (int) Drupal::config('search.settings')->get('index.cron_limit');

  $result = db_query_range("SELECT n.nid FROM {node} n LEFT JOIN {search_dataset} d ON d.type = 'node' AND d.sid = n.nid WHERE d.sid IS NULL OR d.reindex <> 0 ORDER BY d.reindex ASC, n.nid ASC", 0, $limit);

  foreach ($result as $node) {
    $node = node_load($node->id());

    // Save the changed time of the most recent indexed node, for the search
    // results half-life calculation.
    \Drupal::state()->set('node.cron_last', $node->getChangedTime());

    // Render the node.
    $build = node_view($node, 'search_index');
    $node->rendered = drupal_render($node->content);

    $text = '<h1>' . check_plain($node->label()) . '</h1>' . $node->rendered;

    // Fetch extra data normally not visible
    $extra = Drupal::moduleHandler()->invokeAll('node_update_index', array($node));
    foreach ($extra as $t) {
      $text .= $t;
    }

    // Update index
    search_index($node->id(), 'node', $text);
  }
}

/**
 * @} End of "addtogroup hooks".
 */

/**
 * Provide search query conditions.
 *
 * Callback for hook_search_info().
 *
 * This callback is invoked by search_view() to get an array of additional
 * search conditions to pass to search_data(). For example, a search module
 * may get additional keywords, filters, or modifiers for the search from
 * the query string.
 *
 * This example pulls additional search keywords out of the $_REQUEST variable,
 * (i.e. from the query string of the request). The conditions may also be
 * generated internally - for example based on a module's settings.
 *
 * @param $keys
 *   The search keywords string.
 *
 * @return
 *   An array of additional conditions, such as filters.
 *
 * @ingroup callbacks
 * @ingroup search
 */
function callback_search_conditions($keys) {
  $conditions = array();

  if (!empty($_REQUEST['keys'])) {
    $conditions['keys'] = $_REQUEST['keys'];
  }
  if (!empty($_REQUEST['sample_search_keys'])) {
    $conditions['sample_search_keys'] = $_REQUEST['sample_search_keys'];
  }
  if ($force_keys = Drupal::config('sample_search.settings')->get('force_keywords')) {
    $conditions['sample_search_force_keywords'] = $force_keys;
  }
  return $conditions;
}

