<?php
// $Id: search.views_default.inc,v 1.6.6.4 2011/01/05 23:14:39 dereine Exp $
/**
 * @file
 * Contains default views on behalf of the search module.
 */

/**
 * Implements hook_views_default_views().
 */
function search_views_default_views() {
  $view = new view;
  $view->name = 'backlinks';
  $view->description = 'Displays a list of nodes that link to the node, using the search backlinks table.';
  $view->tag = 'default';
  $view->base_table = 'node';
  $view->api_version = 2;
  $view->version = 7;
  $view->disabled = TRUE; /* Edit this to true to make a default view disabled initially */
  $handler = $view->new_display('default', 'Defaults', 'default');
  $handler->override_option('fields', array(
    'title' => array(
      'id' => 'title',
      'table' => 'node',
      'field' => 'title',
      'label' => '',
      'relationship' => 'none',
      'link_to_node' => 1,
    ),
  ));
  $handler->override_option('arguments', array(
    'nid' => array(
      'id' => 'nid',
      'table' => 'search_node_links_to',
      'field' => 'nid',
      'default_action' => 'not found',
      'style_plugin' => 'default_summary',
      'style_options' => array(
        'count' => TRUE,
        'override' => FALSE,
        'items_per_page' => 25,
      ),
      'wildcard' => '',
      'wildcard_substitution' => '',
      'title' => 'Pages that link to %1',
      'default_argument_type' => 'fixed',
      'default_argument' => '',
      'validate_type' => 'node',
      'validate_fail' => 'not found',
      'relationship' => 'none',
      'default_argument_fixed' => '',
      'default_argument_php' => '',
      'validate_argument_node_type' => array(),
      'validate_argument_php' => '',
    ),
  ));
  $handler->override_option('filters', array(
    'status' => array(
      'id' => 'status',
      'table' => 'node',
      'field' => 'status',
      'operator' => '=',
      'value' => 1,
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
  $handler->override_option('empty', 'No backlinks found.');
  $handler->override_option('empty_format', '1');
  $handler->override_option('items_per_page', 30);
  $handler->override_option('use_pager', '1');
  $handler->override_option('style_plugin', 'list');
  $handler->override_option('style_options', array(
    'type' => 'ol',
  ));
  $handler = $view->new_display('page', 'Page', 'page');
  $handler->override_option('path', 'node/%/backlinks');
  $handler->override_option('menu', array(
    'type' => 'tab',
    'title' => 'What links here',
    'weight' => '0',
  ));
  $handler->override_option('tab_options', array(
    'type' => 'none',
    'title' => '',
    'weight' => 0,
  ));
  $handler = $view->new_display('block', 'Block', 'block');
  $handler->override_option('arguments', array(
    'nid' => array(
      'id' => 'nid',
      'table' => 'search_node_links_to',
      'field' => 'nid',
      'default_action' => 'default',
      'style_plugin' => 'default_summary',
      'style_options' => array(
        'count' => TRUE,
        'override' => FALSE,
        'items_per_page' => 25,
      ),
      'wildcard' => '',
      'wildcard_substitution' => '',
      'title' => 'What links here',
      'default_argument_type' => 'node',
      'default_argument' => '',
      'validate_type' => 'node',
      'validate_fail' => 'not found',
      'relationship' => 'none',
      'default_argument_fixed' => '',
      'default_argument_php' => 'return ($node = menu_get_object()) ? $node->nid : FALSE;',
      'validate_argument_node_type' => array(
        'album' => 0,
        'artist' => 0,
        'book' => 0,
        'page' => 0,
        'story' => 0,
        'track' => 0,
      ),
      'validate_argument_php' => '',
      'default_argument_user' => 0,
      'validate_argument_vocabulary' => array(
        '3' => 0,
        '4' => 0,
        '1' => 0,
        '5' => 0,
        '2' => 0,
      ),
      'validate_argument_type' => 'tid',
    ),
  ));
  $handler->override_option('items_per_page', 6);
  $handler->override_option('use_more', 1);
  $handler->override_option('style_plugin', 'list');
  $handler->override_option('style_options', array(
    'type' => 'ul',
  ));
  $handler->override_option('block_description', 'What links here');
  $views[$view->name] = $view;

  return $views;
}
