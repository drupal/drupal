<?php

/**
 * @file
 * Contains the view analyze tool code.
 *
 * This tool is a small plugin manager to perform analysis on a view and
 * report results to the user. This tool is meant to let modules that
 * provide data to Views also help users properly use that data by
 * detecting invalid configurations. Views itself comes with only a
 * small amount of analysis tools, but more could easily be added either
 * by modules or as patches to Views itself.
 */

/**
 * Analyze a review and return the results.
 *
 * @return
 *   An array of analyze results organized into arrays keyed by 'ok',
 *   'warning' and 'error'.
 */
function views_analyze_view(&$view) {
  $view->init_display();
  $messages = module_invoke_all('views_analyze', $view);

  return $messages;
}

/**
 * Format the analyze result into a message string.
 *
 * This is based upon the format of drupal_set_message which uses separate
 * boxes for "ok", "warning" and "error".
 */
function views_analyze_format_result($view, $messages) {
  if (empty($messages)) {
    $messages = array(views_ui_analysis(t('View analysis can find nothing to report.'), 'ok'));
  }

  $types = array('ok' => array(), 'warning' => array(), 'error' => array());
  foreach ($messages as $message) {
    if (empty($types[$message['type']])) {
      $types[$message['type']] = array();
    }
    $types[$message['type']][] = $message['message'];
  }

  $output = '';
  foreach ($types as $type => $messages) {
    $type .= ' messages';
    $message = '';
    if (count($messages) > 1) {
      $message = theme('item_list', array('items' => $messages));
    }
    elseif ($messages) {
      $message = array_shift($messages);
    }

    if ($message) {
      $output .= "<div class=\"$type\">$message</div>";
    }
  }

  return $output;
}

/**
 * Format an analysis message.
 *
 * This tool should be called by any module responding to the analyze hook
 * to properly format the message. It is usually used in the form:
 * @code
 *   $ret[] = views_ui_analysis(t('This is the message'), 'ok');
 * @endcode
 *
 * The 'ok' status should be used to provide information about things
 * that are acceptable. In general analysis isn't interested in 'ok'
 * messages, but instead the 'warning', which is a category for items
 * that may be broken unless the user knows what he or she is doing,
 * and 'error' for items that are definitely broken are much more useful.
 *
 * @param $messages
 *   The message to report.
 * @param $type
 *   The type of message. This should be "ok", "warning" or "error". Other
 *   values can be used but how they are treated by the output routine
 *   is undefined.
 */
function views_ui_analysis($message, $type = 'error') {
  return array('message' => $message, 'type' => $type);
}

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
    $ret[] = views_ui_analysis(t('This view has only a default display and therefore will not be placed anywhere on your site; perhaps you want to add a page or a block display.'), 'warning');
  }
  // You can give a page display the same path as an alias existing in the
  // system, so the alias will not work anymore. Report this to the user,
  // because he probably wanted something else.
  foreach ($view->display as $id => $display) {
    if (empty($display->handler)) {
      continue;
    }
    if ($display->handler->has_path() && $path = $display->handler->get_option('path')) {
      $normal_path = drupal_get_normal_path($path);
      if ($path != $normal_path) {
        $ret[] = views_ui_analysis(t('You have configured display %display with a path which is an path alias as well. This might lead to unwanted effects so better use an internal path.', array('%display' => $display->display_title)), 'warning');
      }
    }
  }

  return $ret;
}
