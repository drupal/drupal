<?php

/**
 * @file
 * Provide views data and handlers for book.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function book_views_data() {
  // ----------------------------------------------------------------------
  // book table

  $data['book']['table']['group']  = t('Book');
  $data['book']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  $data['book']['bid'] = array(
    'title' => t('Top level book'),
    'help' => t('The book the node is in.'),
    'relationship' => array(
      'base' => 'node',
      'handler' => 'views_handler_relationship',
      'label' => t('Book'),
    ),
    // There is no argument here; if you need an argument, add the relationship
    // and use the node: nid argument.
  );

  // ----------------------------------------------------------------------
  // menu_links table -- this is aliased so we can get just book relations

  // Book hierarchy and weight data are now in {menu_links}.
  $data['book_menu_links']['table']['group'] = t('Book');
  $data['book_menu_links']['table']['join'] = array(
    'node' => array(
      'table' => 'menu_links',
      'left_table' => 'book',
      'left_field' => 'mlid',
      'field' => 'mlid',
    ),
  );

  $data['book_menu_links']['weight'] = array(
    'title' => t('Weight'),
    'help' => t('The weight of the book page.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['book_menu_links']['depth'] = array(
    'title' => t('Depth'),
    'help' => t('The depth of the book page in the hierarchy; top level books have a depth of 1.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
  );

  $data['book_menu_links']['p'] = array(
    'title' => t('Hierarchy'),
    'help' => t('The order of pages in the book hierarchy.'),
    'sort' => array(
      'handler' => 'views_handler_sort_menu_hierarchy',
    ),
  );

  // ----------------------------------------------------------------------
  // book_parent table -- this is an alias of the book table which
  // represents the parent book.

  // The {book} record for the parent node.
  $data['book_parent']['table']['group'] = t('Book');
  $data['book_parent']['table']['join'] = array(
    'node' => array(
      'table' => 'book',
      'left_table' => 'book_menu_links',
      'left_field' => 'plid',
      'field' => 'mlid',
    ),
  );

  $data['book_parent']['nid'] = array(
    'title' => t('Parent'),
    'help' => t('The parent book node.'),
    'relationship' => array(
      'base' => 'node',
      'base field' => 'nid',
      'handler' => 'views_handler_relationship',
      'label' => t('Book parent'),
    ),
  );

  return $data;
}

/**
 * Implements hook_views_plugins().
 */
function book_views_plugins() {
 return array(
   'module' => 'views',
   'argument default' => array(
     'book_root' => array(
       'title' => t('Book root from current node'),
       'handler' => 'views_plugin_argument_default_book_root'
     ),
   ),
 );
}
