<?php
// $Id: context.menu.inc,v 1.2 2008/12/30 18:21:34 merlinofchaos Exp $

/**
 * @file
 * Contains menu item registration for the context tool.
 *
 * The menu items registered are AJAX callbacks for the context configuration
 * popups. They are kept separately for organizational purposes.
 */

function ctools_context_menu(&$items) {
  $base = array(
    'access arguments' => array('access content'),
    'type' => MENU_CALLBACK,
    'file' => 'includes/context-admin.inc',
  );
  $items['ctools/context/ajax/add'] = array(
    'page callback' => 'ctools_context_ajax_item_add',
  ) + $base;
  $items['ctools/context/ajax/configure'] = array(
    'page callback' => 'ctools_context_ajax_item_edit',
  ) + $base;
  $items['ctools/context/ajax/delete'] = array(
    'page callback' => 'ctools_context_ajax_item_delete',
  ) + $base;

  // For the access system
  $base['file'] = 'includes/context-access-admin.inc';
  $items['ctools/context/ajax/access/add'] = array(
    'page callback' => 'ctools_access_ajax_add',
  ) + $base;
  $items['ctools/context/ajax/access/configure'] = array(
    'page callback' => 'ctools_access_ajax_edit',
  ) + $base;
  $items['ctools/context/ajax/access/delete'] = array(
    'page callback' => 'ctools_access_ajax_delete',
  ) + $base;

}
