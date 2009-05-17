<?php

/**
 * @file
 * Drush integration of views.
 *
 * drush cache-clear views - Clears the views specific caches.
 * views-revert - Drush command to revert views overridden in the system.
 */

/**
 * Implement hook_drush_help().
 */
function views_drush_help($section) {
  switch ($section) {
    case 'drush:views-revert':
      $help = dt('Reverts views in the drupal installation that have been overriden. ');
      $help .= dt('If no view names are specified, you will be presented with a list of overridden views to choose from. ');
      $help .= dt('To revert all views, do not specify any view names, and choose the option "All" from the options presented.');
      return $help;
    case 'drush:views-list':
      return dt('Show a list of available views with information about them.');
    case 'drush:views-enable':
      return dt('Enable the specified views. Follow the command with a space delimited list of view names');
    case 'drush:views-disable':
      return dt('Disable the specified views. Follow the command with a space delimited list of view names');
  }
}

/**
 * Implement hook_drush_command().
 */
function views_drush_command() {
  $items = array();

  $items['views-revert'] = array(
    'callback' => 'views_revert_views',
    'drupal dependencies' => array('views'),
    'description' => 'Revert overridden views to their default state. Make sure to backup first.',
    'arguments' => array(
      'views' => 'A space delimited list of view names.',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'aliases' => array('vr'),
    'examples' => array(
      'drush vr archive' => 'Reverts the "archive" view.',
      'drush rln archive frontpage' => 'Reverts the "archive" and "frontpage" view.',
      'drush vr' => 'Will present you with a list of overridden views to choose from, and an option to revert all overridden views.',
    ),
  );
  $items['views-dev'] = array(
    'callback' => 'views_development_settings',
    'drupal dependencies' => array('views'),
    'description' => 'Set the Views settings to more developer-oriented values.',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'aliases' => array('vd'),
  );

  $items['views-list'] = array(
    'drupal dependencies' => array('views'),
    'description' => 'Get a list of all views in the system.',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'aliases' => array('vl'),
    'options' => array(
      'name' => 'String contained in view\'s name by which filter the results.',
      'tags' => 'A comma-separated list of views tags by which to filter the results.',
      'status' => 'Status of the views by which to filter the results. Choices: enabled, disabled.',
      'type' => 'Type of the views by which to filter the results. Choices: normal, default or overridden.',
      ),
    'examples' => array(
      'drush vl' => 'Show a list of all available views.',
      'drush vl --name=blog' => 'Show a list of views which names contain "blog".',
      'drush vl --tags=tag1,tag2' => 'Show a list of views tagged with "tag1" or "tag2".',
      'drush vl --status=enabled' => 'Show a list of enabled views.',
      'drush vl --type=overridden' => 'Show a list of overridden views.',
    ),
  );
  $items['views-analyze'] = array(
    'drupal dependencies' => array('views', 'views_ui'),
    'description' => 'Get a list of all Views analyze warnings',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
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
 * Callback function for views-revert command.
 */
function views_revert_views() {
  $views = views_get_all_views();
  $i = 0;
  // The provided views names specified in the command.
  $viewnames = _convert_csv_to_array(func_get_args());

  // Find all overridden views.
  foreach ($views as $view) {
    if ($view->disabled) {
      continue;
    }
    if ($view->type == dt('Overridden')) {
      $overridden[$view->name] = $view->name;
    }
  }

  // Return early if there are no overridden views in the system.
  if (empty($overridden)) {
    return drush_set_error(dt('There are no overridden views in the system.'));
  }

  // If the user specified in the command the views to be overridden.
  if (!empty($viewnames)) {
    foreach ($viewnames as $key => $viewname) {
      $is_overridden = key_exists($viewname, $overridden);
      // Check if the provided view name is in the system
      if ($viewname && !key_exists($viewname, $views)) {
        drush_set_error(dt("'@viewname' view is not present in the system.", array('@viewname' => $viewname)));
      }
      // Check if the provided view is overridden.
      elseif (!$is_overridden) {
        drush_set_error(dt("The view specified '@viewname' is not overridden.", array('@viewname' => $viewname)));
      }
      // If the view is overriden, revert it.
      elseif ($is_overridden){
        views_revert_view($views[$viewname]);
        $i++;
      }
      // We should never get here but well...
      else {
        drush_set_error(dt("The view specified '@viewname' is not provided in code, and thus cannot be reverted.", array('@viewname' => $viewname)));
      }
    }
  }

  // The user did not specify any views in the command, prompt the user
  else {
    // list of choices for the user
    $overridden['all'] = dt('Revert all overridden views'); // add a choice at the end
    $choice = drush_choice($overridden, 'Enter a number to choose which view to revert.', '!key'); // prompt the user

    if ($choice !== FALSE) {
      // revert all views option
      if ($choice == 'all') {
        $i = views_revert_allviews($views);
      }
      // else the user specified a single view
      else {
        views_revert_view($views[$choice]);
        $i++;
      }
    }

  }

  // final results output
  if ($i == 0) {
    drush_log(dt('No views were reverted.'), 'ok');
  }
  else {
    drush_log(dt('Reverted a total of @count views.', array('@count' => $i)), 'ok');
  }
}

/**
 * Reverts all views
 * @param $views
 * All views in the system as provided by views_get_all_views().
 */
function views_revert_allviews($views) {
  $i = 0;
  foreach ($views as $view) {
    if ($view->disabled) {
      continue;
    }

    if ($view->type == t('Overridden')) {
      views_revert_view($view);
      $i++;
    }
  }
  return $i;
}

/**
 * Revert a specified view
 * @param $view
 * The view object to be reverted
 *
 * Checks on wether or not the view is overridden is handled in views_revert_views_revert()
 * We perform a check here anyway in case someone somehow calls this function on their own...
 */
function views_revert_view($view) {
  // check anyway just in case
  if ($view->type == t('Overridden')) {
    // Revert the view.
    $view->delete();
    // Clear its cache.
    ctools_include('object-cache');
    ctools_object_cache_clear('view', $view->name);
    // Give feedback.
    $message = dt("Reverted the view '@viewname'", array('@viewname' => $view->name));
    drush_log($message, 'success');
    // Reverted one more view.
  }
  else {
    drush_set_error(dt("The view '@viewname' is not overridden.", array('@viewname' => $view->name)));
  }
}

/**
 * Change the settings to a more developer oriented value.
 */
function views_development_settings() {
  variable_set('views_ui_show_listing_filters', TRUE);
  variable_set('views_ui_show_master_display', TRUE);
  variable_set('views_ui_show_advanced_column', TRUE);
  variable_set('views_ui_always_live_preview', FALSE);
  variable_set('views_ui_always_live_preview_button', TRUE);
  variable_set('views_ui_show_preview_information', TRUE);
  variable_set('views_ui_show_sql_query', TRUE);
  variable_set('views_ui_show_performance_statistics', TRUE);
  variable_set('views_show_additional_queries', TRUE);
  variable_set('views_devel_output', TRUE);
  variable_set('views_devel_region', 'message');
  variable_set('views_ui_display_embed', TRUE);
  $message = dt("Setup the new views settings.");
  drush_log($message, 'success');
}


/**
 * Callback function for views-list command.
 */
function drush_views_list() {
  // Initialize stuf
  $rows = array();
  $disabled_views = array();
  $enabled_views = array();
  $overridden = 0;
  $indb = 0;
  $incode = 0;
  $disabled = 0;
  $total = 0;

  $views = views_get_all_views();

  // get the --name option
  // TODO : take into account the case off a comma-separated list of names
  $name = drush_get_option_list('name');
  $with_name = !empty($name) ? TRUE : FALSE;

  // get the --tags option
  $tags = drush_get_option_list('tags');
  $with_tags = !empty($tags) ? TRUE : FALSE;

  // get the --status option
  // store user input appart to reuse it after
  $status_opt = drush_get_option_list('status');
  // use the same logic than $view->disabled
  if (in_array('disabled', $status_opt)) {
    $status = TRUE;
    $with_status = TRUE;
  }
  elseif (in_array('enabled', $status_opt)) {
    $status = FALSE;
    $with_status = TRUE;
  }
  else {
    $status = NULL;
    // wrong or empty --status option
    $with_status = FALSE;
  }

  // get the --type option
  $type = drush_get_option_list('type');
  // use the same logic than $view->type
  $with_type = FALSE;
  if (in_array('normal', $type) || in_array('default', $type)|| in_array('overridden', $type)) {
    $with_type = TRUE;
  }

  // set the table headers
  $header = array(
    dt('Machine name'),
    dt('Description'),
    dt('Type'),
    dt('Status'),
    dt('Tag'),
  );

  // setup a row for each view
  foreach($views as $id => $view){
    // if options were specified, check that first
    // mismatch push the loop to the next view
    if ($with_tags && !in_array($view->tag, $tags)) {
      continue;
    }
    if ($with_status && !$view->disabled == $status) {
      continue;
    }
    if ($with_type && strtolower($view->type) !== $type[0]) {
      continue;
    }
    if ($with_name && !stristr($view->name, $name[0])) {
      continue;
    }

    $row = array();
    // each row entry should be in the same order as the header
    $row[] = $view->name;
    $row[] = $view->description;
    $row[] = $view->type;
    $row[] = $view->disabled ? dt('Disabled') : dt('Enabled');
    $row[] = $view->tag;

    // place the row in the appropiate array,
    // so we can have disabled views at the bottom
    if($view->disabled) {
      $disabled_views[] = $row;
      }
    else{
      $enabled_views[] = $row;
    }
    unset($row);

    // gather some statistics
    switch($view->type) {
      case dt('Normal'):
        $indb++;
        break;

      case dt('Overridden'):
        $overridden++;
        break;

      case dt('Default'):
        $incode++;
        break;
    }
    $total++;
  }

  $disabled = count($disabled_views);

  // sort alphabeticaly
  asort($disabled_views);
  asort($enabled_views);

  // if options were used
  $summary = "";
  if ($with_name || $with_tags || $with_status || $with_type) {
    $summary = "Views";

    if ($with_name) {
      $summary .= " named $name[0]";
    }

    if ($with_tags) {
      $tags = implode(" or ", $tags);
      $summary .= " tagged $tags";
    }

    if ($with_status) {
      $status_opt = implode("", $status_opt);
      $summary .= " which status is '$status_opt'";
    }

    if ($with_type) {
      $type = ucfirst($type[0]);
      $summary .= " of type '$type'";
    }
  }

  if (!empty($summary)) {
    drush_print($summary . "\n");
  }

  // print all rows as a table
  if ($total > 0) {
    $rows = array_merge($enabled_views, $disabled_views);
    // put the headers as first row
    array_unshift($rows, $header);

    drush_print_table($rows, TRUE);
  }

  // print the statistics messages
  drush_print(dt("A total of @total views were found in this Drupal installation:", array('@total' => $total)));
  drush_print(dt("  @indb views reside only in the database", array('@indb' => $indb )));
  drush_print(dt("  @over views are overridden", array('@over' => $overridden)));
  drush_print(dt("  @incode views are in their default state", array('@incode' => $incode)));
  drush_print(dt("  @dis views are disabled\n", array('@dis' => $disabled)));
}

function drush_views_analyze() {
  views_include('analyze');
  $messages_count = 0;
  $total = 0;

  foreach (views_get_all_views() as $view_name => $view) {
    $total++;
    if ($messages = views_analyze_view($view)) {
      drush_print($view_name);
      foreach ($messages as $message) {
        $messages_count++;
        drush_print($message['type'] .': '. $message['message'], 2);
      }
    }
  }
  drush_log(dt('A total of @total views were analyzed and @messages problems were found.', array('@total' => $total, '@messages' => $messages_count)), 'ok');
}

/**
 * Enables views
 */
function drush_views_enable() {
  $viewnames = _convert_csv_to_array(func_get_args());
  // Return early if no view names were specified.
  if (empty($viewnames)) {
    return drush_set_error(dt('Please specify a space delimited list of view names to enable'));
  }
  _views_drush_changestatus($viewnames, FALSE);
}

/**
 * Disables views
 */
function drush_views_disable() {
  $viewnames = _convert_csv_to_array(func_get_args());
  // Return early if no view names were specified.
  if (empty($viewnames)) {
    return drush_set_error(dt('Please specify a space delimited list of view names to disable'));
  }
  _views_drush_changestatus($viewnames, TRUE);
}

/**
 * Helper function to enable / disable views
 * @param $viewnames: array of viewnames to process
 * @param $status: TRUE to disable or FALSE to enable the view
 */
function _views_drush_changestatus($viewnames = array(), $status = NULL) {
  if ($status !== NULL && !empty($viewnames)) {
    $changed = FALSE;
    $processed = $status ? dt('disabled') : dt('enabled');
    $views_status = variable_get('views_defaults', array());

    foreach ($viewnames as $key => $viewname) {
      if ($views_status[$viewname] !== $status) {
        $views_status[$viewname] = $status;
        $changed = TRUE;
        drush_log(dt("The view '!name' has been !processed", array('!name' => $viewname, '!processed' => $processed)), 'success');
      }
      else {
        drush_set_error(dt("The view '!name' is already !processed", array('!name' => $viewname, '!processed' => $processed)));
      }
    }
    // If we made changes to views status, save them and clear caches
    if ($changed) {
      variable_set('views_defaults', $views_status);
      views_invalidate_cache();
      drush_log(dt("Views cache was cleared"), 'ok');
      menu_rebuild();
      drush_log(dt("Menu cache was cleared"), 'ok');
    }
  }
}

/**
 * Adds a cache clear option for views.
 */
function views_drush_cache_clear(&$types) {
  $types['views'] = 'views_invalidate_cache';
}
