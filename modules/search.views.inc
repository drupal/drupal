<?php

/**
 * @file
 * Provide views data and handlers for search.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
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

  // Fields

  // score
  $data['search_index']['score'] = array(
    'title' => t('Score'),
    'help' => t('The score of the search item. This will not be used if the search filter is not also present.'),
    'field' => array(
      'id' => 'search_score',
      'click sortable' => TRUE,
      'float' => TRUE,
      'no group by' => TRUE,
    ),
    // Information for sorting on a search score.
    'sort' => array(
      'id' => 'search_score',
      'no group by' => TRUE,
    ),
  );

  // Search node links: forward links.
  $data['search_node_links_from']['table']['group'] = t('Search');
  $data['search_node_links_from']['table']['join'] = array(
    'node' => array(
      'arguments' => array(
        'table' => 'search_node_links',
        'left_table' => 'node',
        'field' => 'nid',
        'left_field' => 'nid',
        'type' => 'INNER'
      ),
    ),
  );
  $data['search_node_links_from']['sid'] = array(
    'title' => t('Links from'),
    'help' => t('Other nodes that are linked from the node.'),
    'argument' => array(
      'id' => 'node_nid',
    ),
    'filter' => array(
      'id' => 'equality',
    ),
  );

  // Search node links: backlinks.
  $data['search_node_links_to']['table']['group'] = t('Search');
  $data['search_node_links_to']['table']['join'] = array(
    'node' => array(
      'arguments' => array(
        'table' => 'search_node_links',
        'left_table' => 'node',
        'field' => 'sid',
        'left_field' => 'nid',
        'type' => 'INNER'
      ),
    ),
  );
  $data['search_node_links_to']['nid'] = array(
    'title' => t('Links to'),
    'help' => t('Other nodes that link to the node.'),
    'argument' => array(
      'id' => 'node_nid',
    ),
    'filter' => array(
      'id' => 'equality',
    ),
  );

  // search filter
  $data['search_index']['keys'] = array(
    'title' => t('Search Terms'), // The item it appears as on the UI,
    'help' => t('The terms to search for.'), // The help that appears on the UI,
    // Information for searching terms using the full search syntax
    'filter' => array(
      'id' => 'search',
      'no group by' => TRUE,
    ),
    'argument' => array(
      'id' => 'search',
      'no group by' => TRUE,
    ),
  );

  return $data;
}
