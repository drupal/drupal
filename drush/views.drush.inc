<?php

/**
 * @file
 * Drush integration for views.
 */

use Drupal\views\Analyzer;
use Drupal\views\ViewStorage;

/**
 * Implements hook_drush_help().
 */
function views_drush_help($section) {
  switch ($section) {
    case 'meta:views:title':
      return dt('Views commands');
    case 'meta:views:summary':
      return dt('Views drush commands.');
    case 'drush:views-list':
      return dt('Show a list of available views with information about them.');
    case 'drush:views-enable':
      return dt('Enable the specified views. Follow the command with a space delimited list of view names');
    case 'drush:views-disable':
      return dt('Disable the specified views. Follow the command with a space delimited list of view names');
  }
}

/**
 * Implements hook_drush_command().
 */
function views_drush_command() {
  $items = array();

  $items['views-dev'] = array(
    'callback' => 'views_development_settings',
    'drupal dependencies' => array('views'),
    'description' => 'Set the Views settings to more developer-oriented values.',
    'aliases' => array('vd'),
  );

  $items['views-list'] = array(
    'drupal dependencies' => array('views'),
    'description' => 'Get a list of all views in the system.',
    'aliases' => array('vl'),
    'options' => array(
      'name' => 'String contained in view\'s name by which filter the results.',
      'tags' => 'A comma-separated list of views tags by which to filter the results.',
      'status' => 'Status of the views by which to filter the results. Choices: enabled, disabled.',
      ),
    'examples' => array(
      'drush vl' => 'Show a list of all available views.',
      'drush vl --name=blog' => 'Show a list of views which names contain "blog".',
      'drush vl --tags=tag1,tag2' => 'Show a list of views tagged with "tag1" or "tag2".',
      'drush vl --status=enabled' => 'Show a list of enabled views.',
    ),
  );

  $items['views-analyze'] = array(
    'drupal dependencies' => array('views', 'views_ui'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'description' => 'Get a list of all Views analyze warnings',
    'aliases' => array('va'),
  );

  $items['views-enable'] = array(
    'drupal dependencies' => array('views'),
    'description' => 'Enable the specified views.',
    'arguments' => array(
      'views' => 'A space delimited list of view names.',
    ),
    'aliases' => array('ven'),
    'examples' => array(
      'drush ven frontpage taxonomy_term' => 'Enable the frontpage and taxonomy_term views.',
    ),
  );

  $items['views-disable'] = array(
    'drupal dependencies' => array('views'),
    'description' => 'Disable the specified views.',
    'arguments' => array(
      'views' => 'A space delimited list of view names.',
    ),
    'aliases' => array('vdis'),
    'examples' => array(
      'drush vdis frontpage taxonomy_term' => 'Disable the frontpage and taxonomy_term views.',
    ),
  );

  return $items;
}

/**
 * Drush views dev command.
 *
 * Changes the settings to more developer oriented values.
 */
function views_development_settings() {
  config('views.settings')
    ->set('ui.show.listing_filters', TRUE)
    ->set('ui.show.master_display', TRUE)
    ->set('ui.show.advanced_column', TRUE)
    ->set('ui.always_live_preview', FALSE)
    ->set('ui.always_live_preview_button', TRUE)
    ->set('ui.show.preview_information', TRUE)
    ->set('ui.show.sql_query.enabled', TRUE)
    ->set('ui.show.sql_query.where', 'above')
    ->set('ui.show.performance_statistics', TRUE)
    ->set('ui.show.additional_queries', TRUE)
    ->set('debug.output', TRUE)
    ->set('debug.region', 'message')
    ->set('ui.show.display_embed', TRUE)
    ->save();

  drush_log(dt('New views configuration saved.'), 'success');
}

/**
 * Callback function for views-list command.
 */
function drush_views_list() {
  // Initialize stuf
  $rows = array();
  $disabled_views = array();
  $enabled_views = array();

  $views = views_get_all_views();

  // Get the --name option.
  $name = array_filter(drush_get_option_list('name'));
  $with_name = !empty($name) ? TRUE : FALSE;

  // Get the --tags option.
  $tags = array_filter(drush_get_option_list('tags'));
  $with_tags = !empty($tags) ? TRUE : FALSE;

  // Get the --status option. Store user input appart to reuse it after.
  $status = drush_get_option('status', FALSE);

  // Throw an error if it's an invalid status.
  if ($status && !in_array($status, array('enabled', 'disabled'))) {
    return drush_set_error(dt('Invalid status: @status. Available options are "enabled" or "disabled"', array('@status' => $status)));
  }

  // Set the table headers.
  $header = array(
    dt('Machine name'),
    dt('Human name'),
    dt('Description'),
    dt('Status'),
    dt('Tag'),
  );

  // Setup a row for each view.
  foreach ($views as $id => $view) {
    // If options were specified, check that first mismatch push the loop to the
    // next view.
    if ($with_name && !stristr($view->name, $name[0])) {
      continue;
    }
    if ($with_tags && !in_array($view->tag, $tags)) {
      continue;
    }

    $status_bool = $status == 'enabled';
    if ($status && ($view->isEnabled() !== $status_bool)) {
      continue;
    }

    $row = array();

    $row[] = $view->id();
    $row[] = $view->label();
    $row[] = $view->description;
    $row[] = $view->isEnabled() ? dt('Enabled') : dt('Disabled');
    $row[] = $view->tag;

    // Place the row in the appropiate array, so we can have disabled views at
    // the bottom.
    if ($view->isEnabled()) {
      $enabled_views[] = $row;
      }
    else{
      $disabled_views[] = $row;
    }

    unset($row);
  }

  $disabled = count($disabled_views);

  // Sort alphabeticaly.
  asort($disabled_views);
  asort($enabled_views);

  // If options were used.
  $summary = '';
  if ($with_name || $with_tags || $status) {
    $summary = dt('Views');

    if ($with_name) {
      $summary .= ' ' . dt('named "@name"', array('@name' => $name[0]));
    }

    if ($with_tags) {
      $tags = implode(' or ', $tags);
      $summary .= ' ' . dt('tagged with "@tags"', array('@tags' => $tags));
    }

    if ($status) {
      $summary .= ' ' . dt('with a status of "@status"', array('@status' => $status));
    }

    drush_print($summary . ":\n");
  }

  // Print all rows as a table.
  if (count($enabled_views) || count($disabled_views)) {
    $rows = array_merge($enabled_views, $disabled_views);
    $total = count($rows);
    // Put the headers as first row.
    array_unshift($rows, $header);

    drush_print_table($rows, TRUE);

    // Print the statistics messages.
    drush_print(dt('A total of @total views were found in this Drupal installation:', array('@total' => $total)));
    drush_print('  ' . dt('@dis views are disabled', array('@dis' => $disabled)) . "\n");
  }
  else {
    drush_set_error(dt('No views found.'));
  }

}

/**
 * Drush views analyze command.
 */
function drush_views_analyze() {
  $messages_count = 0;

  $views = views_get_all_views();

  if (!empty($views)) {
    $analyzer = new Analyzer();
    foreach ($views as $view_name => $view) {
      $view = $view->getExecutable();
      $analyzer->setView($view);
      if ($messages = $analyzer->getMessages($view)) {
        drush_print($view_name);
        foreach ($messages as $message) {
          $messages_count++;
          drush_print($message['type'] .': '. $message['message'], 2);
        }
      }
    }
    return drush_log(dt('A total of @total views were analyzed and @messages problems were found.', array('@total' => count($views), '@messages' => $messages_count)), 'ok');
  }
  else {
    return drush_set_error(dt('There are no views to analyze'));
  }
}

/**
 * Drush views enable command.
 */
function drush_views_enable() {
  $view_names = func_get_args();
  // Return early if no view names were specified.
  if (empty($view_names)) {
    return drush_set_error(dt('Please specify a list of view names to enable'));
  }
  _views_drush_op('enable', $view_names);
}

/**
 * Drush views disable command.
 */
function drush_views_disable() {
  $view_names = func_get_args();
  // Return early if no view names were specified.
  if (empty($view_names)) {
    return drush_set_error(dt('Please specify a list of view names to disable'));
  }
  _views_drush_op('disable', $view_names);
}

/**
 * Perform operations on view objects.
 *
 * @param string $op
 *   The operation to perform.
 * @param array $view_names
 *   An array of view names to load and perform this operation on.
 */
function _views_drush_op($op = '', array $view_names = array()) {
  $op_types = _views_drush_op_types();
  if (!in_array($op, array_keys($op_types))) {
    return drush_set_error(dt('Invalid op type'));
  }

  $view_names = drupal_map_assoc($view_names);

  if ($views = entity_load_multiple('view', $view_names)) {
    foreach ($views as $view) {
      $tokens = array('@view' => $view->id(), '@action' => $op_types[$op]['action']);

      if ($op_types[$op]['validate']($view)) {
        $view->$op();
        drush_log(dt('View: @view has been @action', $tokens), 'success');
      }
      else {
        drush_log(dt('View: @view is already @action', $tokens), 'notice');
      }
      // Remove this view from the viewnames input list.
      unset($view_names[$view->id()]);
    }
  }
  else {
    drush_set_error(dt('No views have been loaded'));
  }

  // If we have some unmatched/leftover view names that weren't loaded.
  if (!empty($view_names)) {
    foreach ($view_names as $viewname) {
      drush_log(dt('View: @view could not be found.', array('@view' => $viewname)), 'error');
    }
  }

}

/**
 * Returns an array of op types that can be performed on views.
 *
 * @return array
 *   An associative array keyed by op type => action name.
 */
function _views_drush_op_types() {
  return array(
    'enable' => array(
      'action' => dt('enabled'),
      'validate' => '_views_drush_view_is_disabled',
    ),
    'disable' => array(
      'action' => dt('disabled'),
      'validate' => '_views_drush_view_is_enabled',
    ),
  );
}

/**
 * Returns whether a view is enabled.
 *
 * @param Drupal\views\ViewStorage $view
 *   The view object to check.
 *
 * @return bool
 *   TRUE if the View is enabled, FALSE otherwise.
 */
function _views_drush_view_is_enabled(ViewStorage $view) {
  return $view->isEnabled();
}

/**
 * Returns whether a view is disabled.
 *
 * @param Drupal\views\ViewStorage $view
 *   The view object to check.
 *
 * @return bool
 *   TRUE if the View is disabled, FALSE otherwise.
 */
function _views_drush_view_is_disabled(ViewStorage $view) {
  return !$view->isEnabled();
}

/**
 * Adds a cache clear option for views.
 */
function views_drush_cache_clear(&$types) {
  $types['views'] = 'views_invalidate_cache';
}
