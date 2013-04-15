<?php

/**
 * @file
 * Provide views runtime hooks for node.module.
 */

use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 */
function node_views_query_substitutions(ViewExecutable $view) {
  return array(
    '***ADMINISTER_NODES***' => intval(user_access('administer nodes')),
    '***VIEW_OWN_UNPUBLISHED_NODES***' => intval(user_access('view own unpublished content')),
    '***BYPASS_NODE_ACCESS***' =>  intval(user_access('bypass node access')),
  );
}

/**
 * Implements hook_views_analyze().
 */
function node_views_analyze(ViewExecutable $view) {
  $ret = array();
  // Check for something other than the default display:
  if ($view->storage->get('base_table') == 'node') {
    foreach ($view->displayHandlers as $id => $display) {
      if (!$display->isDefaulted('access') || !$display->isDefaulted('filters')) {
        // check for no access control
        $access = $display->getOption('access');
        if (empty($access['type']) || $access['type'] == 'none') {
          $result = db_select('role_permission', 'p')
            ->fields('p', array('rid', 'permission'))
            ->condition('p.rid', array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID), 'IN')
            ->condition('p.permission', 'access content')
            ->execute();

          foreach ($result as $role) {
            $role->safe = TRUE;
            $roles[$role->rid] = $role;
          }
          if (!($roles[DRUPAL_ANONYMOUS_RID]->safe && $roles[DRUPAL_AUTHENTICATED_RID]->safe)) {
            $ret[] = Analyzer::formatMessage(t('Some roles lack permission to access content, but display %display has no access control.', array('%display' => $display->display['display_title'])), 'warning');
          }
          $filters = $display->getOption('filters');
          foreach ($filters as $filter) {
            if ($filter['table'] == 'node' && ($filter['field'] == 'status' || $filter['field'] == 'status_extra')) {
              continue 2;
            }
          }
          $ret[] = Analyzer::formatMessage(t('Display %display has no access control but does not contain a filter for published nodes.', array('%display' => $display->display['display_title'])), 'warning');
        }
      }
    }
  }
  foreach ($view->displayHandlers as $display) {
    if ($display->getPluginId() == 'page') {
      if ($display->getOption('path') == 'node/%') {
        $ret[] = Analyzer::formatMessage(t('Display %display has set node/% as path. This will not produce what you want. If you want to have multiple versions of the node view, use panels.', array('%display' => $display->display['display_title'])), 'warning');
      }
    }
  }

  return $ret;
}
