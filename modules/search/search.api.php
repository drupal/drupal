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
 * and permanent URL. See node_form_alter() for an example.
 *
 * @param $op
 *   A string defining which operation to perform:
 *   - 'name': the hook should return a translated name defining the type of
 *     items that are searched for with this module ('content', 'users', ...)
 *   - 'reset': the search index is going to be rebuilt. Modules which use
 *     hook_update_index() should update their indexing bookkeeping so that it
 *     starts from scratch the next time hook_update_index() is called.
 *   - 'search': the hook should perform a search using the keywords in $keys
 *   - 'status': if the module implements hook_update_index(), it should return
 *     an array containing the following keys:
 *     - remaining: the amount of items that still need to be indexed
 *     - total: the total amount of items (both indexed and unindexed)
 *
 * @param $keys
 *   The search keywords as entered by the user.
 *
 * @return
 *   An array of search results.
 *   Each item in the result set array may contain whatever information
 *   the module wishes to display as a search result.
 *   To use the default search result display, each item should be an
 *   array which can have the following keys:
 *   - link: the URL of the found item
 *   - type: the type of item
 *   - title: the name of the item
 *   - user: the author of the item
 *   - date: a timestamp when the item was last modified
 *   - extra: an array of optional extra information items
 *   - snippet: an excerpt or preview to show with the result
 *     (can be generated with search_excerpt())
 *   Only 'link' and 'title' are required, but it is advised to fill in
 *   as many of these fields as possible.
 *
 * The example given here is for node.module, which uses the indexed search
 * capabilities. To do this, node module also implements hook_update_index()
 * which is used to create and maintain the index.
 *
 * We call do_search() with the keys, the module name and extra SQL fragments
 * to use when searching. See hook_update_index() for more information.
 *
 * @ingroup search
 */
function hook_search($op = 'search', $keys = null) {
  switch ($op) {
    case 'name':
      return t('Content');

    case 'reset':
      db_query("UPDATE {search_dataset} SET reindex = %d WHERE type = 'node'", REQUEST_TIME);
      return;

    case 'status':
      $total = db_result(db_query('SELECT COUNT(*) FROM {node} WHERE status = 1'));
      $remaining = db_result(db_query("SELECT COUNT(*) FROM {node} n LEFT JOIN {search_dataset} d ON d.type = 'node' AND d.sid = n.nid WHERE n.status = 1 AND d.sid IS NULL OR d.reindex <> 0"));
      return array('remaining' => $remaining, 'total' => $total);

    case 'admin':
      $form = array();
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

    case 'search':
      // Build matching conditions
      list($join1, $where1) = _db_rewrite_sql();
      $arguments1 = array();
      $conditions1 = 'n.status = 1';

      if ($type = search_query_extract($keys, 'type')) {
        $types = array();
        foreach (explode(',', $type) as $t) {
          $types[] = "n.type = '%s'";
          $arguments1[] = $t;
        }
        $conditions1 .= ' AND (' . implode(' OR ', $types) . ')';
        $keys = search_query_insert($keys, 'type');
      }

      if ($category = search_query_extract($keys, 'category')) {
        $categories = array();
        foreach (explode(',', $category) as $c) {
          $categories[] = "tn.tid = %d";
          $arguments1[] = $c;
        }
        $conditions1 .= ' AND (' . implode(' OR ', $categories) . ')';
        $join1 .= ' INNER JOIN {term_node} tn ON n.vid = tn.vid';
        $keys = search_query_insert($keys, 'category');
      }

      if ($languages = search_query_extract($keys, 'language')) {
        $categories = array();
        foreach (explode(',', $languages) as $l) {
          $categories[] = "n.language = '%s'";
          $arguments1[] = $l;
        }
        $conditions1 .= ' AND (' . implode(' OR ', $categories) . ')';
        $keys = search_query_insert($keys, 'language');
      }

      // Get the ranking expressions.
      $rankings = _node_rankings();

      // When all search factors are disabled (ie they have a weight of zero),
      // The default score is based only on keyword relevance.
      if ($rankings['total'] == 0) {
        $total = 1;
        $arguments2 = array();
        $join2 = '';
        $select2 = 'i.relevance AS score';
      }
      else {
        $total = $rankings['total'];
        $arguments2 = $rankings['arguments'];
        $join2 = implode(' ', $rankings['join']);
        $select2 = '(' . implode(' + ', $rankings['score']) . ') AS score';
      }

      // Do search.
      $find = do_search($keys, 'node', 'INNER JOIN {node} n ON n.nid = i.sid ' . $join1, $conditions1 . (empty($where1) ? '' : ' AND ' . $where1), $arguments1, $select2, $join2, $arguments2);

      // Load results.
      $results = array();
      foreach ($find as $item) {
        // Build the node body.
        $node = node_load($item->sid);
        $node->build_mode = NODE_BUILD_SEARCH_RESULT;
        $node = node_build_content($node, FALSE, FALSE);
        $node->body = drupal_render($node->content);

        // Fetch comments for snippet.
        $node->body .= module_invoke('comment', 'nodeapi', $node, 'update_index');
        // Fetch terms for snippet.
        $node->body .= module_invoke('taxonomy', 'nodeapi', $node, 'update_index');

        $extra = node_invoke_nodeapi($node, 'search_result');

        $results[] = array(
          'link' => url('node/' . $item->sid, array('absolute' => TRUE)),
          'type' => check_plain(node_get_types('name', $node)),
          'title' => $node->title,
          'user' => theme('username', $node),
          'date' => $node->changed,
          'node' => $node,
          'extra' => $extra,
          'score' => $total ? ($item->score / $total) : 0,
          'snippet' => search_excerpt($keys, $node->body),
        );
      }
      return $results;
  }
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
  $last = variable_get('node_cron_last', 0);
  $limit = (int)variable_get('search_cron_limit', 100);

  $result = db_query_range('SELECT n.nid, c.last_comment_timestamp FROM {node} n LEFT JOIN {node_comment_statistics} c ON n.nid = c.nid WHERE n.status = 1 AND n.moderate = 0 AND (n.created > %d OR n.changed > %d OR c.last_comment_timestamp > %d) ORDER BY GREATEST(n.created, n.changed, c.last_comment_timestamp) ASC', $last, $last, $last, 0, $limit);

  while ($node = db_fetch_object($result)) {
    $last_comment = $node->last_comment_timestamp;
    $node = node_load(array('nid' => $node->nid));

    // We update this variable per node in case cron times out, or if the node
    // cannot be indexed (PHP nodes which call drupal_goto, for example).
    // In rare cases this can mean a node is only partially indexed, but the
    // chances of this happening are very small.
    variable_set('node_cron_last', max($last_comment, $node->changed, $node->created));

    // Get node output (filtered and with module-specific fields).
    if (node_hook($node, 'view')) {
      node_invoke($node, 'view', false, false);
    }
    else {
      $node = node_prepare($node, false);
    }
    // Allow modules to change $node->body before viewing.
    node_invoke_nodeapi($node, 'view', false, false);

    $text = '<h1>' . drupal_specialchars($node->title) . '</h1>' . $node->body;

    // Fetch extra data normally not visible
    $extra = node_invoke_nodeapi($node, 'update_index');
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
