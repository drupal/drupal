<?php
// $Id: page-wizard.menu.inc,v 1.2 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Contains menu item registration for the page manager page wizards tool.
 */

function ctools_page_wizard_menu(&$items) {
  if (!module_exists('page_manager')) {
    return;
  }

  $base = array(
    'access arguments' => array('use page manager'),
    'file' => 'includes/page-wizard.inc',
    'type' => MENU_CALLBACK,
  );

  $items['admin/structure/pages/wizard'] = array(
    'title' => 'Wizards',
    'page callback' => 'page_manager_page_wizard_list',
    'page arguments' => array(4),
    'weight' => -5,
    'type' => MENU_LOCAL_TASK,
  ) + $base;

  $items['admin/structure/pages/wizard/%'] = array(
    'title' => 'Wizard',
    'page callback' => 'page_manager_page_wizard',
    'page arguments' => array(4),
  ) + $base;
}
