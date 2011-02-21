<?php
// $Id: search.views.inc,v 1.18.4.3 2010/07/19 09:18:42 dereine Exp $
/**
 * @file
 * Provide views data and handlers for search.module
 */

/**
 * @defgroup views_search_module search.module handlers
 *
 * Includes the tables 'search_index'
 * @{
 */

/**
 * Implements hook_views_data()
 */
function search_views_data() {
  // Basic table information.

  // Define the base group of this table. Fields that don't
  // have a group defined will go into this field by default.
  $data['search_index']['table']['group']  = t('Search');

  // For other base tables, explain how we join
  $data['search_index']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'sid',
    ),
  );

  $data['search_total']['table']['join'] = array(
    'node' => array(
      'left_table' => 'search_index',
      'left_field' => 'word',
      'field' => 'word',
    ),
    'users' => array(
      'left_table' => 'search_index',
      'left_field' => 'word',
      'field' => 'word',
    )
  );

  $data['search_dataset']['table']['join'] = array(
    'node' => array(
      'left_table' => 'search_index',
      'left_field' => 'sid',
      'field' => 'sid',
      'extra' => 'search_index.type = search_dataset.type',
      'type' => 'INNER',
    ),
    'users' => array(
      'left_table' => 'search_index',
      'left_field' => 'sid',
      'field' => 'sid',
      'extra' => 'search_index.type = search_dataset.type',
      'type' => 'INNER',
    ),
  );

  // ----------------------------------------------------------------
  // Fields

  // score
  $data['search_index']['score'] = array(
    'title' => t('Score'),
    'help' => t('The score of the search item. This will not be used if the search filter is not also present.'),
    'field' => array(
      'handler' => 'views_handler_field_search_score',
      'click sortable' => TRUE,
      'float' => TRUE,
    ),
    // Information for sorting on a search score.
    'sort' => array(
      'handler' => 'views_handler_sort_search_score',
    ),
  );

  // Search node links: forward links.
  $data['search_node_links_from']['table']['group'] = t('Search');
  $data['search_node_links_from']['table']['join'] = array(
    'node' => array(
      'arguments' => array('search_node_links', 'node', 'nid', 'nid', NULL, 'INNER'),
    ),
  );
  $data['search_node_links_from']['sid'] = array(
    'title' => t('Links from'),
    'help' => t('Other nodes that are linked from the node.'),
    'argument' => array(
      'handler' => 'views_handler_argument_node_nid',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_equality',
    ),
  );

  // Search node links: backlinks.
  $data['search_node_links_to']['table']['group'] = t('Search');
  $data['search_node_links_to']['table']['join'] = array(
    'node' => array(
      'arguments' => array('search_node_links', 'node', 'nid', 'sid', NULL, 'INNER'),
    ),
  );
  $data['search_node_links_to']['nid'] = array(
    'title' => t('Links to'),
    'help' => t('Other nodes that link to the node.'),
    'argument' => array(
      'handler' => 'views_handler_argument_node_nid',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_equality',
    ),
  );

  // search filter
  $data['search_index']['keys'] = array(
    'title' => t('Search Terms'), // The item it appears as on the UI,
    'help' => t('The terms to search for.'), // The help that appears on the UI,
    // Information for searching terms using the full search syntax
    'filter' => array(
      'handler' => 'views_handler_filter_search',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_search',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_plugins
 */
function search_views_plugins() {
  return;
  // DISABLED. This currently doesn't work.
  return array(
    'module' => 'views', // This just tells our themes are elsewhere.
    'row' => array(
      'search' => array(
        'title' => t('Search'),
        'help' => t('Display the results with standard search view.'),
        'handler' => 'views_plugin_row_search_view',
        'theme' => 'views_view_row_search',
        'path' => drupal_get_path('module', 'views') . '/modules/search', // not necessary for most modules
        'base' => array('node'), // only works with 'node' as base.
        'type' => 'normal',
      ),
      'views_handler_argument_search' => array(
        'parent' => 'views_handler_argument',
      ),
    ),
  );
}

/**
 * Template helper for theme_views_view_row_search
 */
function template_preprocess_views_view_row_search(&$vars) {
  $vars['node'] = ''; // make sure var is defined.
  $nid = $vars['row']->nid;
  if (!is_numeric($nid)) {
    return;
  }

  $node = node_load($nid);

  if (empty($node)) {
    return;
  }

  // Build the node body.
  $node = node_build_content($node, FALSE, FALSE);
  $node->body = drupal_render($node->content);

  // Fetch comments for snippet
  $node->body .= module_invoke('comment', 'nodeapi', $node, 'update index');

  // Fetch terms for snippet
  $node->body .= module_invoke('taxonomy', 'nodeapi', $node, 'update index');

  $vars['url'] = url('node/' . $nid);
  $vars['title'] = check_plain($node->title);

  $info = array();
  $info['type'] = node_type_get_name($node);
  $info['user'] = theme('username', array('acccount' => $node));
  $info['date'] = format_date($node->changed, 'small');
  $extra = module_invoke_all('node_search_result', $node);
  if (isset($extra) && is_array($extra)) {
    $info = array_merge($info, $extra);
  }
  $vars['info_split'] = $info;
  $vars['info'] = implode(' - ', $info);

  $vars['node'] = $node;
  // @todo: get score from ???
//$vars['score'] = $item->score;
  $vars['snippet'] = search_excerpt($vars['view']->value, $node->body);
}


/**
 * @}
 */
