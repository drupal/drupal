<?php
// $Id: statistics.views_default.inc,v 1.5.6.5 2011/01/05 23:14:39 dereine Exp $
/**
 * @file
 * Contains default views on behalf of the statistics module.
 */

/**
 * Implements hook_views_default_views().
 */
function statistics_views_default_views() {
  $view = new view;
  $view->name = 'popular';
  $view->description = 'Shows the most-viewed nodes on the site. This requires the statistics to be enabled at administer >> reports >> access log settings.';
  $view->tag = 'default';
  $view->base_table = 'node';
  $view->api_version = 2;
  $view->version = 7;
  $view->disabled = TRUE; /* Edit this to true to make a default view disabled initially */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->override_option('fields', array(
    'type' => array(
      'id' => 'type',
      'table' => 'node',
      'field' => 'type',
      'label' => 'Type',
    ),
    'title' => array(
      'id' => 'title',
      'table' => 'node',
      'field' => 'title',
      'label' => 'Title',
      'link_to_node' => TRUE,
    ),
    'name' => array(
      'id' => 'name',
      'table' => 'users',
      'field' => 'name',
      'label' => 'Author',
      'link_to_user' => TRUE,
    ),
    'timestamp' => array(
      'id' => 'timestamp',
      'table' => 'history',
      'field' => 'timestamp',
      'label' => '',
      'comments' => 1,
      'relationship' => 'none',
      'link_to_node' => 0,
      'comment' => 1,
    ),
  ));
  $handler->override_option('sorts', array(
    'totalcount' => array(
      'id' => 'totalcount',
      'table' => 'node_counter',
      'field' => 'totalcount',
      'order' => 'DESC',
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('filters', array(
    'status' => array(
      'id' => 'status',
      'table' => 'node',
      'field' => 'status',
      'operator' => '=',
      'value' => '1',
      'group' => 0,
      'exposed' => FALSE,
      'expose' => array(
        'operator' => FALSE,
        'label' => '',
      ),
    ),
    'totalcount' => array(
      'id' => 'totalcount',
      'table' => 'node_counter',
      'field' => 'totalcount',
      'operator' => '>',
      'value' => array(
        'value' => '0',
        'min' => '',
        'max' => '',
      ),
      'group' => 0,
      'exposed' => FALSE,
      'expose' => array(
        'operator' => FALSE,
        'label' => '',
      ),
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('access', array(
    'type' => 'none',
    'role' => array(),
    'perm' => '',
  ));
  $handler->override_option('title', 'Popular content');
  $handler->override_option('items_per_page', '25');
  $handler->override_option('use_pager', TRUE);
  $handler->override_option('use_more', 1);
  $handler->override_option('style_plugin', 'table');
  $handler->override_option('style_options', array(
    'override' => 0,
    'order' => 'desc',
    'columns' => array(
      'type' => 'type',
      'title' => 'title',
      'name' => 'name',
      'timestamp' => 'title',
      'totalcount' => 'totalcount',
    ),
    'info' => array(
      'type' => array(
        'sortable' => 0,
        'separator' => '',
      ),
      'title' => array(
        'sortable' => 0,
        'separator' => '',
      ),
      'name' => array(
        'sortable' => 0,
        'separator' => '',
      ),
      'timestamp' => array(
        'separator' => '',
      ),
      'totalcount' => array(
        'sortable' => 0,
        'separator' => '',
      ),
    ),
    'default' => '-1',
  ));
  $handler = $view->new_display('page', 'Popular (page)', 'page');
  $handler->override_option('path', 'popular/all');
  $handler->override_option('menu', array(
    'type' => 'default tab',
    'title' => 'Popular content',
    'weight' => '-1',
  ));
  $handler->override_option('tab_options', array(
    'type' => 'normal',
    'title' => 'Popular content',
    'weight' => '',
  ));
  $handler = $view->new_display('page', 'Today (page)', 'page_1');
  $handler->override_option('fields', array(
    'type' => array(
      'id' => 'type',
      'table' => 'node',
      'field' => 'type',
      'label' => 'Type',
    ),
    'title' => array(
      'id' => 'title',
      'table' => 'node',
      'field' => 'title',
      'label' => 'Title',
      'link_to_node' => TRUE,
    ),
    'name' => array(
      'id' => 'name',
      'table' => 'users',
      'field' => 'name',
      'label' => 'Author',
      'link_to_user' => TRUE,
    ),
    'timestamp' => array(
      'id' => 'timestamp',
      'table' => 'history',
      'field' => 'timestamp',
      'label' => '',
      'comments' => 1,
      'relationship' => 'none',
      'link_to_node' => 0,
      'comment' => 1,
    ),
    'daycount' => array(
      'id' => 'daycount',
      'table' => 'node_counter',
      'field' => 'daycount',
      'label' => 'Views today',
      'set_precision' => FALSE,
      'precision' => 0,
      'decimal' => '.',
      'separator' => ',',
      'prefix' => '',
      'suffix' => '',
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('sorts', array(
    'daycount' => array(
      'id' => 'daycount',
      'table' => 'node_counter',
      'field' => 'daycount',
      'order' => 'DESC',
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('path', 'popular/today');
  $handler->override_option('menu', array(
    'type' => 'tab',
    'title' => 'Today\'s popular content',
    'weight' => '0',
  ));
  $handler->override_option('tab_options', array(
    'type' => 'normal',
    'title' => 'Popular content',
    'weight' => '0',
  ));
  $handler = $view->new_display('block', 'Popular (block)', 'block');
  $handler->override_option('fields', array(
    'title' => array(
      'id' => 'title',
      'table' => 'node',
      'field' => 'title',
      'label' => '',
      'link_to_node' => 1,
      'relationship' => 'none',
    ),
    'totalcount' => array(
      'id' => 'totalcount',
      'table' => 'node_counter',
      'field' => 'totalcount',
      'label' => '',
      'set_precision' => FALSE,
      'precision' => 0,
      'decimal' => '.',
      'separator' => ',',
      'prefix' => ' (',
      'suffix' => ')',
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('items_per_page', 5);
  $handler->override_option('style_plugin', 'list');
  $handler->override_option('style_options', array(
    'type' => 'ul',
  ));
  $handler->override_option('row_options', array(
    'inline' => array(
      'title' => 'title',
      'totalcount' => 'totalcount',
    ),
    'separator' => '',
  ));
  $handler->override_option('block_description', 'Popular content');
  $handler = $view->new_display('block', 'Today (block)', 'block_1');
  $handler->override_option('fields', array(
    'title' => array(
      'id' => 'title',
      'table' => 'node',
      'field' => 'title',
      'label' => '',
      'link_to_node' => 1,
      'relationship' => 'none',
    ),
    'daycount' => array(
      'id' => 'daycount',
      'table' => 'node_counter',
      'field' => 'daycount',
      'label' => '',
      'set_precision' => FALSE,
      'precision' => 0,
      'decimal' => '.',
      'separator' => ',',
      'prefix' => ' (',
      'suffix' => ')',
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('sorts', array(
    'daycount' => array(
      'id' => 'daycount',
      'table' => 'node_counter',
      'field' => 'daycount',
      'order' => 'DESC',
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('title', 'Today\'s popular content');
  $handler->override_option('items_per_page', 5);
  $handler->override_option('link_display', 'page_1');
  $handler->override_option('style_plugin', 'list');
  $handler->override_option('style_options', array(
    'type' => 'ul',
  ));
  $handler->override_option('row_options', array(
    'inline' => array(
      'title' => 'title',
      'daycount' => 'daycount',
    ),
    'separator' => '',
  ));
  $handler->override_option('block_description', 'Today\'s popular content');
  $views[$view->name] = $view;

  return $views;
}
