<?php

/**
 * @file
 * Contains the view analyze tool code.
 */

use Drupal\views\Analyzer;

/**
 * Implements hook_views_analyze().
 *
 * This is the basic views analysis that checks for very minimal problems.
 * There are other analysis tools in core specific sections, such as
 * node.views.inc as well.
 */
function views_ui_views_analyze($view) {
  $ret = array();
  // Check for something other than the default display:
  if (count($view->display) < 2) {
    $ret[] = Analyzer::formatMessage(t('This view has only a default display and therefore will not be placed anywhere on your site; perhaps you want to add a page or a block display.'), 'warning');
  }
  // You can give a page display the same path as an alias existing in the
  // system, so the alias will not work anymore. Report this to the user,
  // because he probably wanted something else.
  foreach ($view->display as $id => $display) {
    if (empty($display->handler)) {
      continue;
    }
    if ($display->handler->hasPath() && $path = $display->handler->getOption('path')) {
      $normal_path = drupal_get_normal_path($path);
      if ($path != $normal_path) {
        $ret[] = Analyzer::formatMessage(t('You have configured display %display with a path which is an path alias as well. This might lead to unwanted effects so better use an internal path.', array('%display' => $display->display_title)), 'warning');
      }
    }
  }

  return $ret;
}
