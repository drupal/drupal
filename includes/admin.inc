<?php

/**
 * @file
 * Provides the Views' administrative interface.
 */

/**
 * Create an array of Views admin CSS for adding or attaching.
 *
 * This returns an array of arrays. Each array represents a single
 * file. The array format is:
 * - file: The fully qualified name of the file to send to drupal_add_css
 * - options: An array of options to pass to drupal_add_css.
 */
function views_ui_get_admin_css() {
  $module_path = drupal_get_path('module', 'views_ui');
  $list = array();
  $list[$module_path . '/css/views-admin.css'] = array();

  $list[$module_path . '/css/ie/views-admin.ie7.css'] = array(
    'browsers' => array(
      'IE' => 'lte IE 7',
      '!IE' => FALSE
    ),
    'preprocess' => FALSE,
  );

  $list[$module_path . '/css/views-admin.theme.css'] = array();

  // Add in any theme specific CSS files we have
  $themes = list_themes();
  $theme_key = $GLOBALS['theme'];
  while ($theme_key) {
    // Try to find the admin css file for non-core themes.
    if (!in_array($theme_key, array('garland', 'seven', 'bartik'))) {
      $theme_path = drupal_get_path('theme', $theme_key);
      // First search in the css directory, then in the root folder of the theme.
      if (file_exists($theme_path . "/css/views-admin.$theme_key.css")) {
        $list[$theme_path . "/css/views-admin.$theme_key.css"] = array(
          'group' => CSS_THEME,
        );
      }
      else if (file_exists($theme_path . "/views-admin.$theme_key.css")) {
        $list[$theme_path . "/views-admin.$theme_key.css"] = array(
          'group' => CSS_THEME,
        );
      }
    }
    else {
      $list[$module_path . "/css/views-admin.$theme_key.css"] = array(
        'group' => CSS_THEME,
      );
    }
    $theme_key = isset($themes[$theme_key]->base_theme) ? $themes[$theme_key]->base_theme : '';
  }
  // Views contains style overrides for the following modules
  $module_list = array('contextual', 'advanced_help', 'ctools');
  foreach ($module_list as $module) {
    if (module_exists($module)) {
      $list[$module_path . '/css/views-admin.' . $module . '.css'] = array();
    }
  }


  return $list;
}

/**
 * Adds standard Views administration CSS to the current page.
 */
function views_ui_add_admin_css() {
  foreach (views_ui_get_admin_css() as $file => $options) {
    drupal_add_css($file, $options);
  }
}

/**
 * Check to see if the advanced help module is installed, and if not put up
 * a message.
 *
 * Only call this function if the user is already in a position for this to
 * be useful.
 */
function views_ui_check_advanced_help() {
  if (!variable_get('views_ui_show_advanced_help_warning', TRUE)) {
    return;
  }

  if (!module_exists('advanced_help')) {
    $filename = db_query_range("SELECT filename FROM {system} WHERE type = 'module' AND name = 'advanced_help'", 0, 1)
      ->fetchField();
    if ($filename && file_exists($filename)) {
      drupal_set_message(t('If you <a href="@modules">enable the advanced help module</a>, Views will provide more and better help. <a href="@hide">Hide this message.</a>', array('@modules' => url('admin/modules'),'@hide' => url('admin/structure/views/settings'))));
    }
    else {
      drupal_set_message(t('If you install the advanced help module from !href, Views will provide more and better help. <a href="@hide">Hide this message.</a>', array('!href' => l('http://drupal.org/project/advanced_help', 'http://drupal.org/project/advanced_help'), '@hide' => url('admin/structure/views/settings'))));
    }
  }
}

/**
 * Returns the results of the live preview.
 */
function views_ui_preview($view, $display_id, $args = array()) {
  // When this function is invoked as a page callback, each Views argument is
  // passed separately.
  if (!is_array($args)) {
    $args = array_slice(func_get_args(), 2);
  }

  // Save $_GET['q'] so it can be restored before returning from this function.
  $q = $_GET['q'];

  // Determine where the query and performance statistics should be output.
  $show_query = variable_get('views_ui_show_sql_query', FALSE);
  $show_info = variable_get('views_ui_show_preview_information', FALSE);
  $show_location = variable_get('views_ui_show_sql_query_where', 'above');

  $show_stats = variable_get('views_ui_show_performance_statistics', FALSE);
  if ($show_stats) {
    $show_stats = variable_get('views_ui_show_sql_query_where', 'above');
  }

  $combined = $show_query && $show_stats;

  $rows = array('query' => array(), 'statistics' => array());
  $output = '';

  $errors = $view->validate();
  if ($errors === TRUE) {
    $view->ajax = TRUE;
    $view->live_preview = TRUE;
    $view->views_ui_context = TRUE;

    // AJAX happens via $_POST but everything expects exposed data to
    // be in GET. Copy stuff but remove ajax-framework specific keys.
    // If we're clicking on links in a preview, though, we could actually
    // still have some in $_GET, so we use $_REQUEST to ensure we get it all.
    $exposed_input = $_REQUEST;
    foreach (array('view_name', 'view_display_id', 'view_args', 'view_path', 'view_dom_id', 'pager_element', 'view_base_path', 'ajax_html_ids', 'ajax_page_state', 'form_id', 'form_build_id', 'form_token') as $key) {
      if (isset($exposed_input[$key])) {
        unset($exposed_input[$key]);
      }
    }

    $view->set_exposed_input($exposed_input);


    if (!$view->set_display($display_id)) {
      return t('Invalid display id @display', array('@display' => $display_id));
    }

    $view->set_arguments($args);

    // Store the current view URL for later use:
    if ($view->display_handler->get_option('path')) {
      $path = $view->get_url();
    }

    // Make view links come back to preview.
    $view->override_path = 'admin/structure/views/nojs/preview/' . $view->name . '/' . $display_id;

    // Also override $_GET['q'] so we get the pager.
    $original_path = current_path();
    $_GET['q'] = $view->override_path;
    if ($args) {
      $_GET['q'] .= '/' . implode('/', $args);
    }

    // Suppress contextual links of entities within the result set during a
    // Preview.
    // @todo We'll want to add contextual links specific to editing the View, so
    //   the suppression may need to be moved deeper into the Preview pipeline.
    views_ui_contextual_links_suppress_push();
    $preview = $view->preview($display_id, $args);
    views_ui_contextual_links_suppress_pop();

    // Reset variables.
    unset($view->override_path);
    $_GET['q'] = $original_path;

    // Prepare the query information and statistics to show either above or
    // below the view preview.
    if ($show_info || $show_query || $show_stats) {
      // Get information from the preview for display.
      if (!empty($view->build_info['query'])) {
        if ($show_query) {
          $query = $view->build_info['query'];
          // Only the sql default class has a method getArguments.
          $quoted = array();

          if (get_class($view->query) == 'views_plugin_query_default') {
            $quoted = $query->getArguments();
            $connection = Database::getConnection();
            foreach ($quoted as $key => $val) {
              if (is_array($val)) {
                $quoted[$key] = implode(', ', array_map(array($connection, 'quote'), $val));
              }
              else {
                $quoted[$key] = $connection->quote($val);
              }
            }
          }
          $rows['query'][] = array('<strong>' . t('Query') . '</strong>', '<pre>' . check_plain(strtr($query, $quoted)) . '</pre>');
          if (!empty($view->additional_queries)) {
            $queries = '<strong>' . t('These queries were run during view rendering:') . '</strong>';
            foreach ($view->additional_queries as $query) {
              if ($queries) {
                $queries .= "\n";
              }
              $queries .= t('[@time ms]', array('@time' => intval($query[1] * 100000) / 100)) . ' ' . $query[0];
            }

            $rows['query'][] = array('<strong>' . t('Other queries') . '</strong>', '<pre>' . $queries . '</pre>');
          }
        }
        if ($show_info) {
          $rows['query'][] = array('<strong>' . t('Title') . '</strong>', filter_xss_admin($view->get_title()));
          if (isset($path)) {
            $path = l($path, $path);
          }
          else {
            $path = t('This display has no path.');
          }
          $rows['query'][] = array('<strong>' . t('Path') . '</strong>', $path);
        }

        if ($show_stats) {
          $rows['statistics'][] = array('<strong>' . t('Query build time') . '</strong>', t('@time ms', array('@time' => intval($view->build_time * 100000) / 100)));
          $rows['statistics'][] = array('<strong>' . t('Query execute time') . '</strong>', t('@time ms', array('@time' => intval($view->execute_time * 100000) / 100)));
          $rows['statistics'][] = array('<strong>' . t('View render time') . '</strong>', t('@time ms', array('@time' => intval($view->render_time * 100000) / 100)));

        }
        drupal_alter('views_preview_info', $rows, $view);
      }
      else {
        // No query was run. Display that information in place of either the
        // query or the performance statistics, whichever comes first.
        if ($combined || ($show_location === 'above')) {
          $rows['query'] = array(array('<strong>' . t('Query') . '</strong>', t('No query was run')));
        }
        else {
          $rows['statistics'] = array(array('<strong>' . t('Query') . '</strong>', t('No query was run')));
        }
      }
    }
  }
  else {
    foreach ($errors as $error) {
      drupal_set_message($error, 'error');
    }
    $preview = t('Unable to preview due to validation errors.');
  }

  // Assemble the preview, the query info, and the query statistics in the
  // requested order.
  if ($show_location === 'above') {
    if ($combined) {
      $output .= '<div class="views-query-info">' . theme('table', array('rows' => array_merge($rows['query'], $rows['statistics']))) . '</div>';
    }
    else {
      $output .= '<div class="views-query-info">' . theme('table', array('rows' => $rows['query'])) . '</div>';
    }
  }
  elseif ($show_stats === 'above') {
    $output .= '<div class="views-query-info">' . theme('table', array('rows' => $rows['statistics'])) . '</div>';
  }

  $output .= $preview;

  if ($show_location === 'below') {
    if ($combined) {
      $output .= '<div class="views-query-info">' . theme('table', array('rows' => array_merge($rows['query'], $rows['statistics']))) . '</div>';
    }
    else {
      $output .= '<div class="views-query-info">' . theme('table', array('rows' => $rows['query'])) . '</div>';
    }
  }
  elseif ($show_stats === 'below') {
    $output .= '<div class="views-query-info">' . theme('table', array('rows' => $rows['statistics'])) . '</div>';
  }

  $_GET['q'] = $q;
  return $output;
}

/**
 * Page callback to add a new view.
 */
function views_ui_add_page() {
  views_ui_add_admin_css();
  drupal_set_title(t('Add new view'));
  return drupal_get_form('views_ui_add_form');
}

/**
 * Form builder for the "add new view" page.
 */
function views_ui_add_form($form, &$form_state) {
  ctools_include('dependent');
  $form['#attached']['js'][] = drupal_get_path('module', 'views_ui') . '/js/views-admin.js';
  $form['#attributes']['class'] = array('views-admin');

  $form['human_name'] = array(
    '#type' => 'textfield',
    '#title' => t('View name'),
    '#required' => TRUE,
    '#size' => 32,
    '#default_value' => !empty($form_state['view']) ? $form_state['view']->human_name : '',
    '#maxlength' => 255,
  );
  $form['name'] = array(
    '#type' => 'machine_name',
    '#maxlength' => 128,
    '#machine_name' => array(
      'exists' => 'views_get_view',
      'source' => array('human_name'),
    ),
    '#description' => t('A unique machine-readable name for this View. It must only contain lowercase letters, numbers, and underscores.'),
  );

  $form['description_enable'] = array(
    '#type' => 'checkbox',
    '#title' => t('Description'),
  );
  $form['description'] = array(
    '#type' => 'textfield',
    '#title' => t('Provide description'),
    '#title_display' => 'invisible',
    '#size' => 64,
    '#default_value' => !empty($form_state['view']) ? $form_state['view']->description : '',
    '#dependency' => array(
      'edit-description-enable' => array(1),
    ),
  );

  // Create a wrapper for the entire dynamic portion of the form. Everything
  // that can be updated by AJAX goes somewhere inside here. For example, this
  // is needed by "Show" dropdown (below); it changes the base table of the
  // view and therefore potentially requires all options on the form to be
  // dynamically updated.
  $form['displays'] = array();

  // Create the part of the form that allows the user to select the basic
  // properties of what the view will display.
  $form['displays']['show'] = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#attributes' => array('class' => array('container-inline')),
  );

  // Create the "Show" dropdown, which allows the base table of the view to be
  // selected.
  $wizard_plugins = views_ui_get_wizards();
  $options = array();
  foreach ($wizard_plugins as $key => $wizard) {
    $options[$key] = $wizard['title'];
  }
  $form['displays']['show']['wizard_key'] = array(
    '#type' => 'select',
    '#title' => t('Show'),
    '#options' => $options,
  );
  $show_form = &$form['displays']['show'];
  $show_form['wizard_key']['#default_value'] = views_ui_get_selected($form_state, array('show', 'wizard_key'), 'node', $show_form['wizard_key']);
  // Changing this dropdown updates the entire content of $form['displays'] via
  // AJAX.
  views_ui_add_ajax_trigger($show_form, 'wizard_key', array('displays'));

  // Build the rest of the form based on the currently selected wizard plugin.
  $wizard_key = $show_form['wizard_key']['#default_value'];
  $get_instance = $wizard_plugins[$wizard_key]['get_instance'];
  $wizard_instance = $get_instance($wizard_plugins[$wizard_key]);
  $form = $wizard_instance->build_form($form, $form_state);

  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save & exit'),
    '#validate' => array('views_ui_wizard_form_validate'),
    '#submit' => array('views_ui_add_form_save_submit'),
  );
  $form['continue'] = array(
    '#type' => 'submit',
    '#value' => t('Continue & edit'),
    '#validate' => array('views_ui_wizard_form_validate'),
    '#submit' => array('views_ui_add_form_store_edit_submit'),
    '#process' => array_merge(array('views_ui_default_button'), element_info_property('submit', '#process', array())),
  );
  $form['cancel'] = array(
    '#type' => 'submit',
    '#value' => t('Cancel'),
    '#submit' => array('views_ui_add_form_cancel_submit'),
    '#limit_validation_errors' => array(),
  );

  return $form;
}

/**
 * Helper form element validator: integer.
 *
 * The problem with this is that the function is private so it's not guaranteed
 * that it might not be renamed/changed. In the future field.module or something else
 * should provide a public validate function.
 *
 * @see _element_validate_integer_positive()
 */
function views_element_validate_integer($element, &$form_state) {
  $value = $element['#value'];
  if ($value !== '' && (!is_numeric($value) || intval($value) != $value)) {
    form_error($element, t('%name must be a positive integer.', array('%name' => $element['#title'])));
  }
}

/**
 * Gets the current value of a #select element, from within a form constructor function.
 *
 * This function is intended for use in highly dynamic forms (in particular the
 * add view wizard) which are rebuilt in different ways depending on which
 * triggering element (AJAX or otherwise) was most recently fired. For example,
 * sometimes it is necessary to decide how to build one dynamic form element
 * based on the value of a different dynamic form element that may not have
 * even been present on the form the last time it was submitted. This function
 * takes care of resolving those conflicts and gives you the proper current
 * value of the requested #select element.
 *
 * By necessity, this function sometimes uses non-validated user input from
 * $form_state['input'] in making its determination. Although it performs some
 * minor validation of its own, it is not complete. The intention is that the
 * return value of this function should only be used to help decide how to
 * build the current form the next time it is reloaded, not to be saved as if
 * it had gone through the normal, final form validation process. Do NOT use
 * the results of this function for any other purpose besides deciding how to
 * build the next version of the form.
 *
 * @param $form_state
 *   The  standard associative array containing the current state of the form.
 * @param $parents
 *   An array of parent keys that point to the part of the submitted form
 *   values that are expected to contain the element's value (in the case where
 *   this form element was actually submitted). In a simple case (assuming
 *   #tree is TRUE throughout the form), if the select element is located in
 *   $form['wrapper']['select'], so that the submitted form values would
 *   normally be found in $form_state['values']['wrapper']['select'], you would
 *   pass array('wrapper', 'select') for this parameter.
 * @param $default_value
 *   The default value to return if the #select element does not currently have
 *   a proper value set based on the submitted input.
 * @param $element
 *   An array representing the current version of the #select element within
 *   the form.
 *
 * @return
 *   The current value of the #select element. A common use for this is to feed
 *   it back into $element['#default_value'] so that the form will be rendered
 *   with the correct value selected.
 */
function views_ui_get_selected($form_state, $parents, $default_value, $element) {
  // For now, don't trust this to work on anything but a #select element.
  if (!isset($element['#type']) || $element['#type'] != 'select' || !isset($element['#options'])) {
    return $default_value;
  }

  // If there is a user-submitted value for this element that matches one of
  // the currently available options attached to it, use that. We need to check
  // $form_state['input'] rather than $form_state['values'] here because the
  // triggering element often has the #limit_validation_errors property set to
  // prevent unwanted errors elsewhere on the form. This means that the
  // $form_state['values'] array won't be complete. We could make it complete
  // by adding each required part of the form to the #limit_validation_errors
  // property individually as the form is being built, but this is difficult to
  // do for a highly dynamic and extensible form. This method is much simpler.
  if (!empty($form_state['input'])) {
    $key_exists = NULL;
    $submitted = drupal_array_get_nested_value($form_state['input'], $parents, $key_exists);
    // Check that the user-submitted value is one of the allowed options before
    // returning it. This is not a substitute for actual form validation;
    // rather it is necessary because, for example, the same select element
    // might have #options A, B, and C under one set of conditions but #options
    // D, E, F under a different set of conditions. So the form submission
    // might have occurred with option A selected, but when the form is rebuilt
    // option A is no longer one of the choices. In that case, we don't want to
    // use the value that was submitted anymore but rather fall back to the
    // default value.
    if ($key_exists && in_array($submitted, array_keys($element['#options']))) {
      return $submitted;
    }
  }

  // Fall back on returning the default value if nothing was returned above.
  return $default_value;
}

/**
 * Converts a form element in the add view wizard to be AJAX-enabled.
 *
 * This function takes a form element and adds AJAX behaviors to it such that
 * changing it triggers another part of the form to update automatically. It
 * also adds a submit button to the form that appears next to the triggering
 * element and that duplicates its functionality for users who do not have
 * JavaScript enabled (the button is automatically hidden for users who do have
 * JavaScript).
 *
 * To use this function, call it directly from your form builder function
 * immediately after you have defined the form element that will serve as the
 * JavaScript trigger. Calling it elsewhere (such as in hook_form_alter()) may
 * mean that the non-JavaScript fallback button does not appear in the correct
 * place in the form.
 *
 * @param $wrapping_element
 *   The element whose child will server as the AJAX trigger. For example, if
 *   $form['some_wrapper']['triggering_element'] represents the element which
 *   will trigger the AJAX behavior, you would pass $form['some_wrapper'] for
 *   this parameter.
 * @param $trigger_key
 *   The key within the wrapping element that identifies which of its children
 *   serves as the AJAX trigger. In the above example, you would pass
 *   'triggering_element' for this parameter.
 * @param $refresh_parents
 *   An array of parent keys that point to the part of the form that will be
 *   refreshed by AJAX. For example, if triggering the AJAX behavior should
 *   cause $form['dynamic_content']['section'] to be refreshed, you would pass
 *   array('dynamic_content', 'section') for this parameter.
 */
function views_ui_add_ajax_trigger(&$wrapping_element, $trigger_key, $refresh_parents) {
  $seen_ids = &drupal_static(__FUNCTION__ . ':seen_ids', array());
  $seen_buttons = &drupal_static(__FUNCTION__ . ':seen_buttons', array());

  // Add the AJAX behavior to the triggering element.
  $triggering_element = &$wrapping_element[$trigger_key];
  $triggering_element['#ajax']['callback'] = 'views_ui_ajax_update_form';
  // We do not use drupal_html_id() to get an ID for the AJAX wrapper, because
  // it remembers IDs across AJAX requests (and won't reuse them), but in our
  // case we need to use the same ID from request to request so that the
  // wrapper can be recognized by the AJAX system and its content can be
  // dynamically updated. So instead, we will keep track of duplicate IDs
  // (within a single request) on our own, later in this function.
  $triggering_element['#ajax']['wrapper'] = 'edit-view-' . implode('-', $refresh_parents) . '-wrapper';

  // Add a submit button for users who do not have JavaScript enabled. It
  // should be displayed next to the triggering element on the form.
  $button_key = $trigger_key . '_trigger_update';
  $wrapping_element[$button_key] = array(
    '#type' => 'submit',
    // Hide this button when JavaScript is enabled.
    '#attributes' => array('class' => array('js-hide')),
    '#submit' => array('views_ui_nojs_submit'),
    // Add a process function to limit this button's validation errors to the
    // triggering element only. We have to do this in #process since until the
    // form API has added the #parents property to the triggering element for
    // us, we don't have any (easy) way to find out where its submitted values
    // will eventually appear in $form_state['values'].
    '#process' => array_merge(array('views_ui_add_limited_validation'), element_info_property('submit', '#process', array())),
    // Add an after-build function that inserts a wrapper around the region of
    // the form that needs to be refreshed by AJAX (so that the AJAX system can
    // detect and dynamically update it). This is done in #after_build because
    // it's a convenient place where we have automatic access to the complete
    // form array, but also to minimize the chance that the HTML we add will
    // get clobbered by code that runs after we have added it.
    '#after_build' => array_merge(element_info_property('submit', '#after_build', array()), array('views_ui_add_ajax_wrapper')),
  );
  // Copy #weight and #access from the triggering element to the button, so
  // that the two elements will be displayed together.
  foreach (array('#weight', '#access') as $property) {
    if (isset($triggering_element[$property])) {
      $wrapping_element[$button_key][$property] = $triggering_element[$property];
    }
  }
  // For easiest integration with the form API and the testing framework, we
  // always give the button a unique #value, rather than playing around with
  // #name.
  $button_title = !empty($triggering_element['#title']) ? $triggering_element['#title'] : $trigger_key;
  if (empty($seen_buttons[$button_title])) {
    $wrapping_element[$button_key]['#value'] = t('Update "@title" choice', array(
      '@title' => $button_title,
    ));
    $seen_buttons[$button_title] = 1;
  }
  else {
    $wrapping_element[$button_key]['#value'] = t('Update "@title" choice (@number)', array(
      '@title' => $button_title,
      '@number' => ++$seen_buttons[$button_title],
   ));
  }

  // Attach custom data to the triggering element and submit button, so we can
  // use it in both the process function and AJAX callback.
  $ajax_data = array(
    'wrapper' => $triggering_element['#ajax']['wrapper'],
    'trigger_key' => $trigger_key,
    'refresh_parents' => $refresh_parents,
    // Keep track of duplicate wrappers so we don't add the same wrapper to the
    // page more than once.
    'duplicate_wrapper' => !empty($seen_ids[$triggering_element['#ajax']['wrapper']]),
  );
  $seen_ids[$triggering_element['#ajax']['wrapper']] = TRUE;
  $triggering_element['#views_ui_ajax_data'] = $ajax_data;
  $wrapping_element[$button_key]['#views_ui_ajax_data'] = $ajax_data;
}

/**
 * Processes a non-JavaScript fallback submit button to limit its validation errors.
 */
function views_ui_add_limited_validation($element, &$form_state) {
  // Retrieve the AJAX triggering element so we can determine its parents. (We
  // know it's at the same level of the complete form array as the submit
  // button, so all we have to do to find it is swap out the submit button's
  // last array parent.)
  $array_parents = $element['#array_parents'];
  array_pop($array_parents);
  $array_parents[] = $element['#views_ui_ajax_data']['trigger_key'];
  $ajax_triggering_element = drupal_array_get_nested_value($form_state['complete form'], $array_parents);

  // Limit this button's validation to the AJAX triggering element, so it can
  // update the form for that change without requiring that the rest of the
  // form be filled out properly yet.
  $element['#limit_validation_errors'] = array($ajax_triggering_element['#parents']);

  // If we are in the process of a form submission and this is the button that
  // was clicked, the form API workflow in form_builder() will have already
  // copied it to $form_state['triggering_element'] before our #process
  // function is run. So we need to make the same modifications in $form_state
  // as we did to the element itself, to ensure that #limit_validation_errors
  // will actually be set in the correct place.
  if (!empty($form_state['triggering_element'])) {
    $clicked_button = &$form_state['triggering_element'];
    if ($clicked_button['#name'] == $element['#name'] && $clicked_button['#value'] == $element['#value']) {
      $clicked_button['#limit_validation_errors'] = $element['#limit_validation_errors'];
    }
  }

  return $element;
}

/**
 * After-build function that adds a wrapper to a form region (for AJAX refreshes).
 *
 * This function inserts a wrapper around the region of the form that needs to
 * be refreshed by AJAX, based on information stored in the corresponding
 * submit button form element.
 */
function views_ui_add_ajax_wrapper($element, &$form_state) {
  // Don't add the wrapper <div> if the same one was already inserted on this
  // form.
  if (empty($element['#views_ui_ajax_data']['duplicate_wrapper'])) {
    // Find the region of the complete form that needs to be refreshed by AJAX.
    // This was earlier stored in a property on the element.
    $complete_form = &$form_state['complete form'];
    $refresh_parents = $element['#views_ui_ajax_data']['refresh_parents'];
    $refresh_element = drupal_array_get_nested_value($complete_form, $refresh_parents);

    // The HTML ID that AJAX expects was also stored in a property on the
    // element, so use that information to insert the wrapper <div> here.
    $id = $element['#views_ui_ajax_data']['wrapper'];
    $refresh_element += array(
      '#prefix' => '',
      '#suffix' => '',
    );
    $refresh_element['#prefix'] = '<div id="' . $id . '" class="views-ui-ajax-wrapper">' . $refresh_element['#prefix'];
    $refresh_element['#suffix'] .= '</div>';

    // Copy the element that needs to be refreshed back into the form, with our
    // modifications to it.
    drupal_array_set_nested_value($complete_form, $refresh_parents, $refresh_element);
  }

  return $element;
}

/**
 * Updates a part of the add view form via AJAX.
 *
 * @return
 *   The part of the form that has changed.
 */
function views_ui_ajax_update_form($form, $form_state) {
  // The region that needs to be updated was stored in a property of the
  // triggering element by views_ui_add_ajax_trigger(), so all we have to do is
  // retrieve that here.
  return drupal_array_get_nested_value($form, $form_state['triggering_element']['#views_ui_ajax_data']['refresh_parents']);
}

/**
 * Non-Javascript fallback for updating the add view form.
 */
function views_ui_nojs_submit($form, &$form_state) {
  $form_state['rebuild'] = TRUE;
}

/**
 * Validate the add view form.
 */
function views_ui_wizard_form_validate($form, &$form_state) {
  $wizard = views_ui_get_wizard($form_state['values']['show']['wizard_key']);
  $form_state['wizard'] = $wizard;
  $get_instance = $wizard['get_instance'];
  $form_state['wizard_instance'] = $get_instance($wizard);
  $errors = $form_state['wizard_instance']->validate($form, $form_state);
  foreach ($errors as $name => $message) {
    form_set_error($name, $message);
  }
}

/**
 * Process the add view form, 'save'.
 */
function views_ui_add_form_save_submit($form, &$form_state) {
  try {
    $view = $form_state['wizard_instance']->create_view($form, $form_state);
  }
  catch (ViewsWizardException $e) {
    drupal_set_message($e->getMessage(), 'error');
    $form_state['redirect'] = 'admin/structure/views';
  }
  $view->save();
  menu_rebuild();
  cache_clear_all('*', 'cache_views', TRUE);
  cache_clear_all();
  $form_state['redirect'] = 'admin/structure/views';
  if (!empty($view->display['page'])) {
    $display = $view->display['page'];
    if ($display->handler->has_path()) {
      $one_path = $display->handler->get_option('path');
      if (strpos($one_path, '%') === FALSE) {
        $form_state['redirect'] = $one_path;  // PATH TO THE VIEW IF IT HAS ONE
        return;
      }
    }
  }
  drupal_set_message(t('Your view was saved. You may edit it from the list below.'));
}

/**
 * Process the add view form, 'continue'.
 */
function views_ui_add_form_store_edit_submit($form, &$form_state) {
  try {
    $view = $form_state['wizard_instance']->create_view($form, $form_state);
  }
  catch (ViewsWizardException $e) {
    drupal_set_message($e->getMessage(), 'error');
    $form_state['redirect'] = 'admin/structure/views';
  }
  // Just cache it temporarily to edit it.
  views_ui_cache_set($view);

  // If there is a destination query, ensure we still redirect the user to the
  // edit view page, and then redirect the user to the destination.
  $destination = array();
  if (isset($_GET['destination'])) {
    $destination = drupal_get_destination();
    unset($_GET['destination']);
  }
  $form_state['redirect'] = array('admin/structure/views/view/' . $view->name, array('query' => $destination));
}

/**
 * Cancel the add view form.
 */
function views_ui_add_form_cancel_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/structure/views';
}

/**
 * Form element validation handler for a taxonomy autocomplete field.
 *
 * This allows a taxonomy autocomplete field to be validated outside the
 * standard Field API workflow, without passing in a complete field widget.
 * Instead, all that is required is that $element['#field_name'] contain the
 * name of the taxonomy autocomplete field that is being validated.
 *
 * This function is currently not used for validation directly, although it
 * could be. Instead, it is only used to store the term IDs and vocabulary name
 * in the element value, based on the tags that the user typed in.
 *
 * @see taxonomy_autocomplete_validate()
 */
function views_ui_taxonomy_autocomplete_validate($element, &$form_state) {
  $value = array();
  if ($tags = $element['#value']) {
    // Get the machine names of the vocabularies we will search, keyed by the
    // vocabulary IDs.
    $field = field_info_field($element['#field_name']);
    $vocabularies = array();
    if (!empty($field['settings']['allowed_values'])) {
      foreach ($field['settings']['allowed_values'] as $tree) {
        if ($vocabulary = taxonomy_vocabulary_machine_name_load($tree['vocabulary'])) {
          $vocabularies[$vocabulary->vid] = $tree['vocabulary'];
        }
      }
    }
    // Store the term ID of each (valid) tag that the user typed.
    $typed_terms = drupal_explode_tags($tags);
    foreach ($typed_terms as $typed_term) {
      if ($terms = taxonomy_term_load_multiple(array(), array('name' => trim($typed_term), 'vid' => array_keys($vocabularies)))) {
        $term = array_pop($terms);
        $value['tids'][] = $term->tid;
      }
    }
    // Store the term IDs along with the name of the vocabulary. Currently
    // Views (as well as the Field UI) assumes that there will only be one
    // vocabulary, although technically the API allows there to be more than
    // one.
    if (!empty($value['tids'])) {
      $value['tids'] = array_unique($value['tids']);
      $value['vocabulary'] = array_pop($vocabularies);
    }
  }
  form_set_value($element, $value, $form_state);
}

/**
 * Theme function; returns basic administrative information about a view.
 *
 * TODO: template + preprocess
 */
function theme_views_ui_view_info($variables) {
  $view = $variables['view'];
  $title = $view->get_human_name();

  $displays = _views_ui_get_displays_list($view);
  $displays = empty($displays) ? t('None') : format_plural(count($displays), 'Display', 'Displays') . ': ' . '<em>' . implode(', ', $displays) . '</em>';

  switch ($view->type) {
    case t('Default'):
    default:
      $type = t('In code');
      break;

    case t('Normal'):
      $type = t('In database');
      break;

    case t('Overridden'):
      $type = t('Database overriding code');
  }

  $output = '';
  $output .= '<div class="views-ui-view-title">' . $title . "</div>\n";
  $output .= '<div class="views-ui-view-displays">' . $displays . "</div>\n";
  $output .= '<div class="views-ui-view-storage">' . $type . "</div>\n";
  $output .= '<div class="views-ui-view-base">' . t('Type') . ': ' . $variables['base']. "</div>\n";
  return $output;
}

/**
 * Page to delete a view.
 */
function views_ui_break_lock_confirm($form, &$form_state, $view) {
  $form_state['view'] = &$view;
  $form = array();

  if (empty($view->locked)) {
    $form['#markup'] = t('There is no lock on view %view to break.', array('%name' => $view->name));
    return $form;
  }

  $cancel = 'admin/structure/views/view/' . $view->name . '/edit';
  if (!empty($_REQUEST['cancel'])) {
    $cancel = $_REQUEST['cancel'];
  }

  $account = user_load($view->locked->uid);
  return confirm_form($form,
                  t('Are you sure you want to break the lock on view %name?',
                  array('%name' => $view->name)),
                  $cancel,
                  t('By breaking this lock, any unsaved changes made by !user will be lost!', array('!user' => theme('username', array('account' => $account)))),
                  t('Break lock'),
                  t('Cancel'));
}

/**
 * Submit handler to break_lock a view.
 */
function views_ui_break_lock_confirm_submit(&$form, &$form_state) {
  ctools_object_cache_clear_all('view', $form_state['view']->name);
  $form_state['redirect'] = 'admin/structure/views/view/' . $form_state['view']->name . '/edit';
  drupal_set_message(t('The lock has been broken and you may now edit this view.'));
}

/**
 * Helper function to return the used display_id for the edit page
 *
 * This function handles access to the display.
 */
function views_ui_edit_page_display($view, $display_id) {
  // Determine the displays available for editing.
  if ($tabs = views_ui_edit_page_display_tabs($view, $display_id)) {
    // If a display isn't specified, use the first one.
    if (empty($display_id)) {
      foreach ($tabs as $id => $tab) {
        if (!isset($tab['#access']) || $tab['#access']) {
          $display_id = $id;
          break;
        }
      }
    }
    // If a display is specified, but we don't have access to it, return
    // an access denied page.
    if ($display_id && (!isset($tabs[$display_id]) || (isset($tabs[$display_id]['#access']) && !$tabs[$display_id]['#access']))) {
      return MENU_ACCESS_DENIED;
    }

    return $display_id;
  }
  elseif ($display_id) {
    return MENU_ACCESS_DENIED;
  }
  else {
    $display_id = NULL;
  }

  return $display_id;
}

/**
 * Page callback for the Edit View page.
 */
function views_ui_edit_page($view, $display_id = NULL) {
  $display_id = views_ui_edit_page_display($view, $display_id);
  if (!in_array($display_id, array(MENU_ACCESS_DENIED, MENU_NOT_FOUND))) {
    $build = array();
    $build['edit_form'] = drupal_get_form('views_ui_edit_form', $view, $display_id);
    $build['preview'] = views_ui_build_preview($view, $display_id, FALSE);
  }
  else {
    $build = $display_id;
  }

  return $build;
}

function views_ui_build_preview($view, $display_id, $render = TRUE) {
  if (isset($_POST['ajax_html_ids'])) {
    unset($_POST['ajax_html_ids']);
  }

  $build = array(
    '#theme_wrappers' => array('container'),
    '#attributes' => array('id' => 'views-preview-wrapper', 'class' => 'views-admin clearfix'),
  );

  $form_state = array('build_info' => array('args' => array($view, $display_id)));
  $build['controls'] = drupal_build_form('views_ui_preview_form', $form_state);

  $args = array();
  if (!empty($form_state['values']['view_args'])) {
    $args = explode('/', $form_state['values']['view_args']);
  }

  $build['preview'] = array(
    '#theme_wrappers' => array('container'),
    '#attributes' => array('id' => 'views-live-preview'),
    '#markup' => $render ? views_ui_preview($view->clone_view(), $display_id, $args) : '',
  );

  return $build;
}

/**
 * Form builder callback for editing a View.
 *
 * @todo Remove as many #prefix/#suffix lines as possible. Use #theme_wrappers
 *   instead.
 *
 * @todo Rename to views_ui_edit_view_form(). See that function for the "old"
 *   version.
 *
 * @see views_ui_ajax_get_form()
 */
function views_ui_edit_form($form, &$form_state, $view, $display_id = NULL) {
  // Do not allow the form to be cached, because $form_state['view'] can become
  // stale between page requests.
  // See views_ui_ajax_get_form() for how this affects #ajax.
  // @todo To remove this and allow the form to be cacheable:
  //   - Change $form_state['view'] to $form_state['temporary']['view'].
  //   - Add a #process function to initialize $form_state['temporary']['view']
  //     on cached form submissions.
  //   - Update ctools_include() to support cached forms, or else use
  //     form_load_include().
  $form_state['no_cache'] = TRUE;

  if ($display_id) {
    if (!$view->set_display($display_id)) {
      $form['#markup'] = t('Invalid display id @display', array('@display' => $display_id));
      return $form;
    }

    $view->fix_missing_relationships();
  }

  ctools_include('dependent');
  $form['#attached']['js'][] = ctools_attach_js('dependent');
  $form['#attached']['js'][] = ctools_attach_js('collapsible-div');

  $form['#tree'] = TRUE;
  // @todo When more functionality is added to this form, cloning here may be
  //   too soon. But some of what we do with $view later in this function
  //   results in making it unserializable due to PDO limitations.
  $form_state['view'] = clone($view);

  $form['#attached']['library'][] = array('system', 'ui.tabs');
  $form['#attached']['library'][] = array('system', 'ui.dialog');
  $form['#attached']['library'][] = array('system', 'drupal.ajax');
  $form['#attached']['library'][] = array('system', 'jquery.form');
  // TODO: This should be getting added to the page when an ajax popup calls
  // for it, instead of having to add it manually here.
  $form['#attached']['js'][] = 'misc/tabledrag.js';

  $form['#attached']['css'] = views_ui_get_admin_css();
  $module_path = drupal_get_path('module', 'views_ui');

  $form['#attached']['js'][] = $module_path . '/js/views-admin.js';
  $form['#attached']['js'][] = array(
    'data' => array('views' => array('ajax' => array(
      'id' => '#views-ajax-body',
      'title' => '#views-ajax-title',
      'popup' => '#views-ajax-popup',
      'defaultForm' => views_ui_get_default_ajax_message(),
    ))),
    'type' => 'setting',
  );

  $form += array(
    '#prefix' => '',
    '#suffix' => '',
  );
  $form['#prefix'] = $form['#prefix'] . '<div class="views-edit-view views-admin clearfix">';
  $form['#suffix'] = '</div>' . $form['#suffix'];

  $form['#attributes']['class'] = array('form-edit');

  if (isset($view->locked) && is_object($view->locked)) {
    $form['locked'] = array(
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('view-locked', 'messages', 'warning')),
      '#markup' => t('This view is being edited by user !user, and is therefore locked from editing by others. This lock is !age old. Click here to <a href="!break">break this lock</a>.', array('!user' => theme('username', array('account' => user_load($view->locked->uid))), '!age' => format_interval(REQUEST_TIME - $view->locked->updated), '!break' => url('admin/structure/views/view/' . $view->name . '/break-lock'))),
    );
  }
  if (isset($view->vid) && $view->vid == 'new') {
    $message = t('* All changes are stored temporarily. Click Save to make your changes permanent. Click Cancel to discard the view.');
  }
  else {
    $message = t('* All changes are stored temporarily. Click Save to make your changes permanent. Click Cancel to discard your changes.');
  }

  $form['changed'] = array(
    '#theme_wrappers' => array('container'),
    '#attributes' => array('class' => array('view-changed', 'messages', 'warning')),
    '#markup' => $message,
  );
  if (empty($view->changed)) {
    $form['changed']['#attributes']['class'][] = 'js-hide';
  }

  $form['help_text'] = array(
    '#prefix' => '<div>',
    '#suffix' => '</div>',
    '#markup' => t('Modify the display(s) of your view below or add new displays.'),
  );

  $form['actions'] = array(
    '#type' => 'actions',
    '#weight' => 0,
  );

  if (empty($view->changed)) {
    $form['actions']['#attributes'] = array(
      'class' => array(
        'js-hide',
      ),
    );
  }

  $form['actions']['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    // Taken from the "old" UI. @TODO: Review and rename.
    '#validate' => array('views_ui_edit_view_form_validate'),
    '#submit' => array('views_ui_edit_view_form_submit'),
  );
  $form['actions']['cancel'] = array(
    '#type' => 'submit',
    '#value' => t('Cancel'),
    '#submit' => array('views_ui_edit_view_form_cancel'),
  );

  $form['displays'] = array(
    '#prefix' => '<h1 class="unit-title clearfix">' . t('Displays') . '</h1>' . "\n" . '<div class="views-displays">',
    '#suffix' => '</div>',
  );

  $form['displays']['top'] = views_ui_render_display_top($view, $display_id);

  // The rest requires a display to be selected.
  if ($display_id) {
    $form_state['display_id'] = $display_id;

    // The part of the page where editing will take place.
    // This element is the ctools collapsible-div container for the display edit elements.
    $form['displays']['settings'] = array(
      '#theme_wrappers' => array('container'),
      '#attributes' => array(
        'class' => array(
          'views-display-settings',
          'box-margin',
          'ctools-collapsible-container',
        ),
      ),
      '#id' => 'edit-display-settings',
    );
    $display_title = views_ui_get_display_label($view, $display_id, FALSE);
    // Add a handle for the ctools collapsible-div. The handle is the title of the display
    $form['displays']['settings']['tab_title']['#markup'] = '<h2 id="edit-display-settings-title" class="ctools-collapsible-handle">' . t('@display_title details', array('@display_title' => ucwords($display_title))) . '</h2>';
    // Add a text that the display is disabled.
    if (!empty($view->display[$display_id]->handler)) {
      $enabled = $view->display[$display_id]->handler->get_option('enabled');
      if (empty($enabled)) {
        $form['displays']['settings']['disabled']['#markup'] = t('This display is disabled.');
      }
    }
    // The ctools collapsible-div content
    $form['displays']['settings']['settings_content']= array(
      '#theme_wrappers' => array('container'),
      '#id' => 'edit-display-settings-content',
      '#attributes' => array(
        'class' => array(
          'ctools-collapsible-content',
        ),
      ),
    );
    // Add the edit display content
    $form['displays']['settings']['settings_content']['tab_content'] = views_ui_get_display_tab($view, $display_id);
    $form['displays']['settings']['settings_content']['tab_content']['#theme_wrappers'] = array('container');
    $form['displays']['settings']['settings_content']['tab_content']['#attributes'] = array('class' => array('views-display-tab'));
    $form['displays']['settings']['settings_content']['tab_content']['#id'] = 'views-tab-' . $display_id;
    // Mark deleted displays as such.
    if (!empty($view->display[$display_id]->deleted)) {
      $form['displays']['settings']['settings_content']['tab_content']['#attributes']['class'][] = 'views-display-deleted';
    }
    // Mark disabled displays as such.
    if (empty($enabled)) {
      $form['displays']['settings']['settings_content']['tab_content']['#attributes']['class'][] = 'views-display-disabled';
    }

    // The content of the popup dialog.
    $form['ajax-area'] = array(
      '#theme_wrappers' => array('container'),
      '#id' => 'views-ajax-popup',
    );
    $form['ajax-area']['ajax-title'] = array(
      '#markup' => '<h2 id="views-ajax-title"></h2>',
    );
    $form['ajax-area']['ajax-body'] = array(
      '#theme_wrappers' => array('container'),
      '#id' => 'views-ajax-body',
      '#markup' => views_ui_get_default_ajax_message(),
    );
  }

  // If relationships had to be fixed, we want to get that into the cache
  // so that edits work properly, and to try to get the user to save it
  // so that it's not using weird fixed up relationships.
  if (!empty($view->relationships_changed) && empty($_POST)) {
    drupal_set_message(t('This view has been automatically updated to fix missing relationships. While this View should continue to work, you should verify that the automatic updates are correct and save this view.'));
    views_ui_cache_set($view);
  }
  return $form;
}

/**
 * Provide the preview formulas and the preview output, too.
 */
function views_ui_preview_form($form, &$form_state, $view, $display_id = 'default') {
  $form_state['no_cache'] = TRUE;
  $form_state['view'] = $view;

  $form['#attributes'] = array('class' => array('clearfix',));

  // Add a checkbox controlling whether or not this display auto-previews.
  $form['live_preview'] = array(
    '#type' => 'checkbox',
    '#id' => 'edit-displays-live-preview',
    '#title' => t('Auto preview'),
    '#default_value' => variable_get('views_ui_always_live_preview', TRUE),
  );

  // Add the arguments textfield
  $form['view_args'] = array(
    '#type' => 'textfield',
    '#title' => t('Preview with contextual filters:'),
    '#description' => t('Separate contextual filter values with a "/". For example, %example.', array('%example' => '40/12/10')),
    '#id' => 'preview-args',
//      '#attributes' => array('class' => array('ctools-auto-submit')),
  );

  // Add the preview button
  $form['button'] = array(
    '#type' => 'submit',
    '#value' => t('Update preview'),
    '#attributes' => array('class' => array('arguments-preview', 'ctools-auto-submit-click')),
    '#pre_render' => array('ctools_dependent_pre_render'),
    '#prefix' => '<div id="preview-submit-wrapper">',
    '#suffix' => '</div>',
    '#id' => 'preview-submit',
    '#submit' => array('views_ui_edit_form_submit_preview'),
    '#ajax' => array(
      'path' => 'admin/structure/views/view/' . $view->name . '/preview/' . $display_id . '/ajax',
      'wrapper' => 'views-preview-wrapper',
      'event' => 'click',
      'progress' => array('type' => 'throbber'),
      'method' => 'replace',
    ),
    // Make ENTER in arguments textfield (and other controls) submit the form
    // as this button, not the Save button.
    // @todo This only works for JS users. To make this work for nojs users,
    //   we may need to split Preview into a separate form.
    '#process' => array_merge(array('views_ui_default_button'), element_info_property('submit', '#process', array())),
  );
  $form['#action'] = url('admin/structure/views/view/' . $view->name .'/preview/' . $display_id);

  return $form;
}

/**
 * Render the top of the display so it can be updated during ajax operations.
 */
function views_ui_render_display_top($view, $display_id) {
  $element['#theme_wrappers'] = array('views_container');
  $element['#attributes']['class'] = array('views-display-top', 'clearfix');
  $element['#attributes']['id'] = array('views-display-top');

  // Extra actions for the display
  $element['extra_actions'] = array(
    '#theme' => 'links__ctools_dropbutton',
    '#attributes' => array(
        'id' => 'views-display-extra-actions',
        'class' => array(
          'horizontal', 'right', 'links', 'actions',
        ),
      ),
    '#links' => array(
      'edit-details' => array(
        'title' => t('edit view name/description'),
        'href' => "admin/structure/views/nojs/edit-details/$view->name",
        'attributes' => array('class' => array('views-ajax-link')),
      ),
      'analyze' => array(
        'title' => t('analyze view'),
        'href' => "admin/structure/views/nojs/analyze/$view->name/$display_id",
        'attributes' => array('class' => array('views-ajax-link')),
      ),
      'clone' => array(
        'title' => t('clone view'),
        'href' => "admin/structure/views/view/$view->name/clone",
      ),
      'export' => array(
        'title' => t('export view'),
        'href' => "admin/structure/views/view/$view->name/export",
      ),
      'reorder' => array(
        'title' => t('reorder displays'),
        'href' => "admin/structure/views/nojs/reorder-displays/$view->name/$display_id",
        'attributes' => array('class' => array('views-ajax-link')),
      ),
    ),
  );

  // Let other modules add additional links here.
  drupal_alter('views_ui_display_top_links', $element['extra_actions']['#links'], $view, $display_id);

  if (isset($view->type) && $view->type != t('Default')) {
    if ($view->type == t('Overridden')) {
      $element['extra_actions']['#links']['revert'] = array(
        'title' => t('revert view'),
        'href' => "admin/structure/views/view/$view->name/revert",
        'query' => array('destination' => "admin/structure/views/view/$view->name"),
      );
    }
    else {
      $element['extra_actions']['#links']['delete'] = array(
        'title' => t('delete view'),
        'href' => "admin/structure/views/view/$view->name/delete",
      );
    }
  }

  // Determine the displays available for editing.
  if ($tabs = views_ui_edit_page_display_tabs($view, $display_id)) {
    if ($display_id) {
      $tabs[$display_id]['#active'] = TRUE;
    }
    $tabs['#prefix'] = '<h2 class="element-invisible">' . t('Secondary tabs') . '</h2><ul id = "views-display-menu-tabs" class="tabs secondary">';
    $tabs['#suffix'] = '</ul>';
    $element['tabs'] = $tabs;
  }

  // Buttons for adding a new display.
  foreach (views_fetch_plugin_names('display', NULL, array($view->base_table)) as $type => $label) {
    $element['add_display'][$type] = array(
      '#type' => 'submit',
      '#value' => t('Add !display', array('!display' => $label)),
      '#limit_validation_errors' => array(),
      '#submit' => array('views_ui_edit_form_submit_add_display', 'views_ui_edit_form_submit_delay_destination'),
      '#attributes' => array('class' => array('add-display')),
      // Allow JavaScript to remove the 'Add ' prefix from the button label when
      // placing the button in a "Add" dropdown menu.
      '#process' => array_merge(array('views_ui_form_button_was_clicked'), element_info_property('submit', '#process', array())),
      '#values' => array(t('Add !display', array('!display' => $label)), $label),
    );
  }

  return $element;
}

function views_ui_get_default_ajax_message() {
  return '<div class="message">' . t("Click on an item to edit that item's details.") . '</div>';
}

/**
 * Submit handler to add a display to a view.
 */
function views_ui_edit_form_submit_add_display($form, &$form_state) {
  $view = $form_state['view'];

  // Create the new display.
  $parents = $form_state['triggering_element']['#parents'];
  $display_type = array_pop($parents);
  $display_id = $view->add_display($display_type);
  views_ui_cache_set($view);

  // Redirect to the new display's edit page.
  $form_state['redirect'] = 'admin/structure/views/view/' . $view->name . '/edit/' . $display_id;
}

/**
 * Submit handler to duplicate a display for a view.
 */
function views_ui_edit_form_submit_duplicate_display($form, &$form_state) {
  $view = $form_state['view'];
  $display_id = $form_state['display_id'];

  // Create the new display.
  $display = $view->display[$display_id];
  $new_display_id = $view->add_display($display->display_plugin);
  $view->display[$new_display_id] = clone $display;
  $view->display[$new_display_id]->id = $new_display_id;

  // By setting the current display the changed marker will appear on the new
  // display.
  $view->current_display = $new_display_id;
  views_ui_cache_set($view);

  // Redirect to the new display's edit page.
  $form_state['redirect'] = 'admin/structure/views/view/' . $view->name . '/edit/' . $new_display_id;
}

/**
 * Submit handler to delete a display from a view.
 */
function views_ui_edit_form_submit_delete_display($form, &$form_state) {
  $view = $form_state['view'];
  $display_id = $form_state['display_id'];

  // Mark the display for deletion.
  $view->display[$display_id]->deleted = TRUE;
  views_ui_cache_set($view);

  // Redirect to the top-level edit page. The first remaining display will
  // become the active display.
  $form_state['redirect'] = 'admin/structure/views/view/' . $view->name;
}

/**
 * Submit handler to add a restore a removed display to a view.
 */
function views_ui_edit_form_submit_undo_delete_display($form, &$form_state) {
  // Create the new display
  $id = $form_state['display_id'];
  $form_state['view']->display[$id]->deleted = FALSE;

  // Store in cache
  views_ui_cache_set($form_state['view']);

  // Redirect to the top-level edit page.
  $form_state['redirect'] = 'admin/structure/views/view/' . $form_state['view']->name . '/edit/' . $id;
}

/**
 * Submit handler to enable a disabled display.
 */
function views_ui_edit_form_submit_enable_display($form, &$form_state) {
  $id = $form_state['display_id'];
  // set_option doesn't work because this would might affect upper displays
  $form_state['view']->display[$id]->handler->set_option('enabled', TRUE);

  // Store in cache
  views_ui_cache_set($form_state['view']);

  // Redirect to the top-level edit page.
  $form_state['redirect'] = 'admin/structure/views/view/' . $form_state['view']->name . '/edit/' . $id;
}

/**
 * Submit handler to disable display.
 */
function views_ui_edit_form_submit_disable_display($form, &$form_state) {
  $id = $form_state['display_id'];
  $form_state['view']->display[$id]->handler->set_option('enabled', FALSE);

  // Store in cache
  views_ui_cache_set($form_state['view']);

  // Redirect to the top-level edit page.
  $form_state['redirect'] = 'admin/structure/views/view/' . $form_state['view']->name . '/edit/' . $id;
}

/**
 * Submit handler when Preview button is clicked.
 */
function views_ui_edit_form_submit_preview($form, &$form_state) {
  // Rebuild the form with a pristine $view object.
  $form_state['build_info']['args'][0] = views_ui_cache_load($form_state['view']->name);
  $form_state['show_preview'] = TRUE;
  $form_state['rebuild'] = TRUE;
}

/**
 * Submit handler for form buttons that do not complete a form workflow.
 *
 * The Edit View form is a multistep form workflow, but with state managed by
 * the CTools object cache rather than $form_state['rebuild']. Without this
 * submit handler, buttons that add or remove displays would redirect to the
 * destination parameter (e.g., when the Edit View form is linked to from a
 * contextual link). This handler can be added to buttons whose form submission
 * should not yet redirect to the destination.
 */
function views_ui_edit_form_submit_delay_destination($form, &$form_state) {
  if (isset($_GET['destination']) && $form_state['redirect'] !== FALSE) {
    if (!isset($form_state['redirect'])) {
      $form_state['redirect'] = $_GET['q'];
    }
    if (is_string($form_state['redirect'])) {
      $form_state['redirect'] = array($form_state['redirect']);
    }
    $options = isset($form_state['redirect'][1]) ? $form_state['redirect'][1] : array();
    if (!isset($options['query']['destination'])) {
      $options['query']['destination'] = $_GET['destination'];
    }
    $form_state['redirect'][1] = $options;
    unset($_GET['destination']);
  }
}

/**
 * Adds tabs for navigating across Displays when editing a View.
 *
 * This function can be called from hook_menu_local_tasks_alter() to implement
 * these tabs as secondary local tasks, or it can be called from elsewhere if
 * having them as secondary local tasks isn't desired. The caller is responsible
 * for setting the active tab's #active property to TRUE.
 *
 * @param $view
 *    The view which will be edited.
 * @param $display_id
 *    The display_id which is edited on the current request.
 */
function views_ui_edit_page_display_tabs($view, $display_id = NULL) {
  $tabs = array();

  // Create a tab for each display.
  foreach ($view->display as $id => $display) {
    $tabs[$id] = array(
      '#theme' => 'menu_local_task',
      '#link' => array(
        'title' => views_ui_get_display_label($view, $id),
        'href' => 'admin/structure/views/view/' . $view->name . '/edit/' . $id,
        'localized_options' => array(),
      ),
    );
    if (!empty($display->deleted)) {
      $tabs[$id]['#link']['localized_options']['attributes']['class'][] = 'views-display-deleted-link';
    }
    if (empty($display->options['enabled'])) {
      $tabs[$id]['#link']['localized_options']['attributes']['class'][] = 'views-display-disabled-link';
    }
  }

  // If the default display isn't supposed to be shown, don't display its tab, unless it's the only display.
  if ((!views_ui_show_default_display($view) && $display_id != 'default') && count($tabs) > 1) {
    $tabs['default']['#access'] = FALSE;
  }

  return $tabs;
}

/**
 * Controls whether or not the default display should have its own tab on edit.
 */
function views_ui_show_default_display($view) {
  // Always show the default display for advanced users who prefer that mode.
  $advanced_mode = variable_get('views_ui_show_master_display', FALSE);
  // For other users, show the default display only if there are no others, and
  // hide it if there's at least one "real" display.
  $additional_displays = (count($view->display) == 1);

  return $advanced_mode || $additional_displays;
}

/**
 * Returns a renderable array representing the edit page for one display.
 */
function views_ui_get_display_tab($view, $display_id) {
  $build = array();
  $display = $view->display[$display_id];
  // If the plugin doesn't exist, display an error message instead of an edit
  // page.
  if (empty($display->handler)) {
    $title = isset($display->display_title) ? $display->display_title : t('Invalid');
    // @TODO: Improved UX for the case where a plugin is missing.
    $build['#markup'] = t("Error: Display @display refers to a plugin named '@plugin', but that plugin is not available.", array('@display' => $display->id, '@plugin' => $display->display_plugin));
  }
  // Build the content of the edit page.
  else {
    $build['details'] = views_ui_get_display_tab_details($view, $display);
  }
  // In AJAX context, views_ui_regenerate_tab() returns this outside of form
  // context, so hook_form_views_ui_edit_form_alter() is insufficient.
  drupal_alter('views_ui_display_tab', $build, $view, $display_id);
  return $build;
}

/**
 * Helper function to get the display details section of the edit UI.
 *
 * @param $view
 * @param $display
 *
 * @return array
 *   A renderable page build array.
 */
function views_ui_get_display_tab_details($view, $display) {
  $display_title = views_ui_get_display_label($view, $display->id, FALSE);
  $build = array(
    '#theme_wrappers' => array('container'),
    '#attributes' => array('id' => 'edit-display-settings-details',),
  );

  $plugin = views_fetch_plugin_data('display', $view->display[$display->id]->display_plugin);
  // The following is for display purposes only. We need to determine if there is more than one button and wrap
  // the buttons in a .ctools-dropbutton class if more than one is present.  Otherwise, we'll just wrap the
  // actions in the .ctools-button class.
  $isDisplayDeleted = !empty($display->deleted);
  $isDeletable = empty($plugin['no remove']);
  // The master display cannot be cloned.
  $isDefault = $display->id == 'default';
  // @todo: Figure out why get_option doesn't work here.
  $isEnabled = $display->handler->get_option('enabled');

  if (!$isDisplayDeleted && $isDeletable && !$isDefault) {
    $prefix = '<div class="ctools-no-js ctools-button ctools-dropbutton"><div class="ctools-link"><a href="#" class="ctools-twisty ctools-text">open</a></div><div class="ctools-content"><ul class="horizontal right actions">';
    $suffix = '</ul></div></div>';
    $itemElement = 'li';
  }
  else {
    $prefix = '<div class="ctools-button"><div class="ctools-content"><ul class="horizontal right actions">';
    $suffix = '</ul></div></div>';
    $itemElement = 'li';
  }

  if ($display->id != 'default') {
    $build['top']['#theme_wrappers'] = array('container');
    $build['top']['#attributes']['id'] = 'edit-display-settings-top';
    $build['top']['#attributes']['class'] = array('views-ui-display-tab-actions', 'views-ui-display-tab-bucket', 'clearfix');

    // The Delete, Duplicate and Undo Delete buttons.
    $build['top']['actions'] = array(
      '#prefix' => $prefix,
      '#suffix' => $suffix,
    );

    if (!$isDisplayDeleted) {
      if (!$isEnabled) {
        $build['top']['actions']['enable'] = array(
          '#type' => 'submit',
          '#value' => t('enable @display_title', array('@display_title' => $display_title)),
          '#limit_validation_errors' => array(),
          '#submit' => array('views_ui_edit_form_submit_enable_display', 'views_ui_edit_form_submit_delay_destination'),
          '#prefix' => '<' . $itemElement . ' class="enable">',
          "#suffix" => '</' . $itemElement . '>',
        );
      }
      // Add a link to view the page.
      elseif ($display->handler->has_path()) {
        $path = $display->handler->get_path();
        if (strpos($path, '%') === FALSE) {
          $build['top']['actions']['path'] = array(
            '#type' => 'link',
            '#title' => t('view @display', array('@display' => $display->display_title)),
            '#options' => array('alt' => array(t("Go to the real page for this display"))),
            '#href' => $path,
            '#prefix' => '<' . $itemElement . ' class="view">',
            "#suffix" => '</' . $itemElement . '>',
          );
        }
      }
      if (!$isDefault) {
        $build['top']['actions']['duplicate'] = array(
          '#type' => 'submit',
          '#value' => t('clone @display_title', array('@display_title' => $display_title)),
          '#limit_validation_errors' => array(),
          '#submit' => array('views_ui_edit_form_submit_duplicate_display', 'views_ui_edit_form_submit_delay_destination'),
          '#prefix' => '<' . $itemElement . ' class="duplicate">',
          "#suffix" => '</' . $itemElement . '>',
        );
      }
      if ($isDeletable) {
        $build['top']['actions']['delete'] = array(
          '#type' => 'submit',
          '#value' => t('delete @display_title', array('@display_title' => $display_title)),
          '#limit_validation_errors' => array(),
          '#submit' => array('views_ui_edit_form_submit_delete_display', 'views_ui_edit_form_submit_delay_destination'),
          '#prefix' => '<' . $itemElement . ' class="delete">',
          "#suffix" => '</' . $itemElement . '>',
        );
      }
      if ($isEnabled) {
        $build['top']['actions']['disable'] = array(
          '#type' => 'submit',
          '#value' => t('disable @display_title', array('@display_title' => $display_title)),
          '#limit_validation_errors' => array(),
          '#submit' => array('views_ui_edit_form_submit_disable_display', 'views_ui_edit_form_submit_delay_destination'),
          '#prefix' => '<' . $itemElement . ' class="disable">',
          "#suffix" => '</' . $itemElement . '>',
        );
      }
    }
    else {
      $build['top']['actions']['undo_delete'] = array(
        '#type' => 'submit',
        '#value' => t('undo delete of @display_title', array('@display_title' => $display_title)),
        '#limit_validation_errors' => array(),
        '#submit' => array('views_ui_edit_form_submit_undo_delete_display', 'views_ui_edit_form_submit_delay_destination'),
        '#prefix' => '<' . $itemElement . ' class="undo-delete">',
        "#suffix" => '</' . $itemElement . '>',
      );
    }

    // The area above the three columns.
    $build['top']['display_title'] = array(
      '#theme' => 'views_ui_display_tab_setting',
      '#description' => t('Display name'),
      '#link' => $display->handler->option_link(check_plain($display_title), 'display_title'),
    );
  }

  $build['columns'] = array();
  $build['columns']['#theme_wrappers'] = array('container');
  $build['columns']['#attributes'] = array('id' => 'edit-display-settings-main', 'class' => array('clearfix', 'views-display-columns'),);

  $build['columns']['first']['#theme_wrappers'] = array('container');
  $build['columns']['first']['#attributes'] = array('class' => array('views-display-column', 'first'));

  $build['columns']['second']['#theme_wrappers'] = array('container');
  $build['columns']['second']['#attributes'] = array('class' => array('views-display-column', 'second'));

  $build['columns']['second']['settings'] = array();
  $build['columns']['second']['header'] = array();
  $build['columns']['second']['footer'] = array();
  $build['columns']['second']['pager'] = array();

  // The third column buckets are wrapped in a CTools collapsible div
  $build['columns']['third']['#theme_wrappers'] = array('container');
  $build['columns']['third']['#attributes'] = array('class' => array('views-display-column', 'third', 'ctools-collapsible-container', 'ctools-collapsible-remember'));
  // Specify an id that won't change after AJAX requests, so ctools can keep
  // track of the user's preferred collapsible state. Use the same id across
  // different displays of the same view, so changing displays doesn't
  // recollapse the column.
  $build['columns']['third']['#attributes']['id'] = 'views-ui-advanced-column-' . $view->name;
  // Collapse the div by default.
  if (!variable_get('views_ui_show_advanced_column', FALSE)) {
    $build['columns']['third']['#attributes']['class'][] = 'ctools-collapsed';
  }
  $build['columns']['third']['advanced'] = array('#markup' => '<h3 class="ctools-collapsible-handle"><a href="">' . t('Advanced') . '</a></h3>',);
  $build['columns']['third']['collapse']['#theme_wrappers'] = array('container');
  $build['columns']['third']['collapse']['#attributes'] = array('class' => array('ctools-collapsible-content',),);

  // Each option (e.g. title, access, display as grid/table/list) fits into one
  // of several "buckets," or boxes (Format, Fields, Sort, and so on).
  $buckets = array();

  // Fetch options from the display plugin, with a list of buckets they go into.
  $options = array();
  $display->handler->options_summary($buckets, $options);

  // Place each option into its bucket.
  foreach ($options as $id => $option) {
    // Each option self-identifies as belonging in a particular bucket.
    $buckets[$option['category']]['build'][$id] = views_ui_edit_form_get_build_from_option($id, $option, $view, $display);
  }

  // Place each bucket into the proper column.
  foreach ($buckets as $id => $bucket) {
    // Let buckets identify themselves as belonging in a column.
    if (isset($bucket['column']) && isset($build['columns'][$bucket['column']])) {
      $column = $bucket['column'];
    }
    // If a bucket doesn't pick one of our predefined columns to belong to, put
    // it in the last one.
    else {
      $column = 'third';
    }
    if (isset($bucket['build']) && is_array($bucket['build'])) {
      // The third column is a CTools collapsible div, so
      // the structure of the form is a little different for this column
      if ($column === 'third') {
        $build['columns'][$column]['collapse'][$id] = $bucket['build'];
        $build['columns'][$column]['collapse'][$id]['#theme_wrappers'][] = 'views_ui_display_tab_bucket';
        $build['columns'][$column]['collapse'][$id]['#title'] = !empty($bucket['title']) ? $bucket['title'] : '';
        $build['columns'][$column]['collapse'][$id]['#name'] = !empty($bucket['title']) ? $bucket['title'] : $id;
      }
      else {
        $build['columns'][$column][$id] = $bucket['build'];
        $build['columns'][$column][$id]['#theme_wrappers'][] = 'views_ui_display_tab_bucket';
        $build['columns'][$column][$id]['#title'] = !empty($bucket['title']) ? $bucket['title'] : '';
        $build['columns'][$column][$id]['#name'] = !empty($bucket['title']) ? $bucket['title'] : $id;
      }
    }
  }

  $build['columns']['first']['fields'] = views_ui_edit_form_get_bucket('field', $view, $display);
  $build['columns']['first']['filters'] = views_ui_edit_form_get_bucket('filter', $view, $display);
  $build['columns']['first']['sorts'] = views_ui_edit_form_get_bucket('sort', $view, $display);
  $build['columns']['second']['header'] = views_ui_edit_form_get_bucket('header', $view, $display);
  $build['columns']['second']['footer'] = views_ui_edit_form_get_bucket('footer', $view, $display);
  $build['columns']['third']['collapse']['arguments'] = views_ui_edit_form_get_bucket('argument', $view, $display);
  $build['columns']['third']['collapse']['relationships'] = views_ui_edit_form_get_bucket('relationship', $view, $display);
  $build['columns']['third']['collapse']['empty'] = views_ui_edit_form_get_bucket('empty', $view, $display);

  return $build;
}

/**
 * Build a renderable array representing one option on the edit form.
 *
 * This function might be more logical as a method on an object, if a suitable
 * object emerges out of refactoring.
 */
function views_ui_edit_form_get_build_from_option($id, $option, $view, $display) {
  $option_build = array();
  $option_build['#theme'] = 'views_ui_display_tab_setting';

  $option_build['#description'] = $option['title'];

  $option_build['#link'] = $display->handler->option_link($option['value'], $id, '', empty($option['desc']) ? '' : $option['desc']);

  $option_build['#links'] = array();
  if (!empty($option['links']) && is_array($option['links'])) {
    foreach ($option['links'] as $link_id => $link_value) {
      $option_build['#settings_links'][] = $display->handler->option_link($option['setting'], $link_id, 'views-button-configure', $link_value);
    }
  }

  if (!empty($display->handler->options['defaults'][$id])) {
    $display_id = 'default';
    $option_build['#defaulted'] = TRUE;
  }
  else {
    $display_id = $display->id;
    if (!$display->handler->is_default_display()) {
      if ($display->handler->defaultable_sections($id)) {
        $option_build['#overridden'] = TRUE;
      }
    }
  }
  $option_build['#attributes']['class'][] = drupal_clean_css_identifier($display_id . '-' . $id);
  if (!empty($view->changed_sections[$display_id . '-' . $id])) {
    $option_build['#changed'] = TRUE;
  }
  return $option_build;
}

function template_preprocess_views_ui_display_tab_setting(&$variables) {
  static $zebra = 0;
  $variables['zebra'] = ($zebra % 2 === 0 ? 'odd' : 'even');
  $zebra++;

  // Put the main link to the left side
  array_unshift($variables['settings_links'], $variables['link']);
  $variables['settings_links'] = implode('<span class="label">&nbsp;|&nbsp;</span>', $variables['settings_links']);

  // Add classes associated with this display tab to the overall list.
  $variables['classes_array'] = array_merge($variables['classes_array'], $variables['class']);

  if (!empty($variables['defaulted'])) {
    $variables['classes_array'][] = 'defaulted';
  }
  if (!empty($variables['overridden'])) {
    $variables['classes_array'][] = 'overridden';
    $variables['attributes_array']['title'][] = t('Overridden');
  }

  // Append a colon to the description, if requested.
  if ($variables['description'] && $variables['description_separator']) {
    $variables['description'] .= t(':');
  }
}

function template_preprocess_views_ui_display_tab_bucket(&$variables) {
  $element = $variables['element'];

  $variables['item_help_icon'] = '';
  if (!empty($element['#item_help_icon'])) {
    $variables['item_help_icon'] = render($element['#item_help_icon']);
  }
  if (!empty($element['#name'])) {
    $variables['classes_array'][] = drupal_clean_css_identifier(strtolower($element['#name']));
  }
  if (!empty($element['#overridden'])) {
    $variables['classes_array'][] = 'overridden';
    $variables['attributes_array']['title'][] = t('Overridden');
  }

  $variables['content'] = $element['#children'];
  $variables['title'] = $element['#title'];
  $variables['actions'] = !empty($element['#actions']) ? $element['#actions'] : '';
}

function template_preprocess_views_ui_display_tab_column(&$variables) {
  $element = $variables['element'];

  $variables['content'] = $element['#children'];
  $variables['column'] = $element['#column'];
}

/**
 * Move form elements into fieldsets for presentation purposes.
 *
 * Many views forms use #tree = TRUE to keep their values in a hierarchy for
 * easier storage. Moving the form elements into fieldsets during form building
 * would break up that hierarchy. Therefore, we wait until the pre_render stage,
 * where any changes we make affect presentation only and aren't reflected in
 * $form_state['values'].
 */
function views_ui_pre_render_add_fieldset_markup($form) {
  foreach (element_children($form) as $key) {
    $element = $form[$key];
    // In our form builder functions, we added an arbitrary #fieldset property
    // to any element that belongs in a fieldset. If this form element has that
    // property, move it into its fieldset.
    if (isset($element['#fieldset']) && isset($form[$element['#fieldset']])) {
      $form[$element['#fieldset']][$key] = $element;
      // Remove the original element this duplicates.
      unset($form[$key]);
    }
  }

  return $form;
}

/**
 * Flattens the structure of an element containing the #flatten property.
 *
 * If a form element has #flatten = TRUE, then all of it's children
 * get moved to the same level as the element itself.
 * So $form['to_be_flattened'][$key] becomes $form[$key], and
 * $form['to_be_flattened'] gets unset.
 */
function views_ui_pre_render_flatten_data($form) {
  foreach (element_children($form) as $key) {
    $element = $form[$key];
    if (!empty($element['#flatten'])) {
      foreach (element_children($element) as $child_key) {
        $form[$child_key] = $form[$key][$child_key];
      }
      // All done, remove the now-empty parent.
      unset($form[$key]);
    }
  }

  return $form;
}

/**
 * Moves argument options into their place.
 *
 * When configuring the default argument behavior, almost each of the radio
 * buttons has its own fieldset shown bellow it when the radio button is
 * clicked. That fieldset is created through a custom form process callback.
 * Each element that has #argument_option defined and pointing to a default
 * behavior gets moved to the appropriate fieldset.
 * So if #argument_option is specified as 'default', the element is moved
 * to the 'default_options' fieldset.
 */
function views_ui_pre_render_move_argument_options($form) {
  foreach (element_children($form) as $key) {
    $element = $form[$key];
    if (!empty($element['#argument_option'])) {
      $container_name = $element['#argument_option'] . '_options';
      if (isset($form['no_argument']['default_action'][$container_name])) {
        $form['no_argument']['default_action'][$container_name][$key] = $element;
      }
      // Remove the original element this duplicates.
      unset($form[$key]);
    }
  }
  return $form;
}

/**
 * Custom form radios process function.
 *
 * Roll out a single radios element to a list of radios,
 * using the options array as index.
 * While doing that, create a container element underneath each option, which
 * contains the settings related to that option.
 *
 * @see form_process_radios()
 */
function views_ui_process_container_radios($element) {
  if (count($element['#options']) > 0) {
    foreach ($element['#options'] as $key => $choice) {
      $element += array($key => array());
      // Generate the parents as the autogenerator does, so we will have a
      // unique id for each radio button.
      $parents_for_id = array_merge($element['#parents'], array($key));

      $element[$key] += array(
        '#type' => 'radio',
        '#title' => $choice,
        // The key is sanitized in drupal_attributes() during output from the
        // theme function.
        '#return_value' => $key,
        '#default_value' => isset($element['#default_value']) ? $element['#default_value'] : NULL,
        '#attributes' => $element['#attributes'],
        '#parents' => $element['#parents'],
        '#id' => drupal_html_id('edit-' . implode('-', $parents_for_id)),
        '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
      );
      $element[$key . '_options'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('views-admin-dependent')),
      );
    }
  }
  return $element;
}

/**
 * Import a view from cut & paste.
 */
function views_ui_import_page($form, &$form_state) {
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('View name'),
    '#description' => t('Enter the name to use for this view if it is different from the source view. Leave blank to use the name of the view.'),
  );

  $form['name_override'] = array(
    '#type' => 'checkbox',
    '#title' => t('Replace an existing view if one exists with the same name'),
  );

  $form['view'] = array(
    '#type' => 'textarea',
    '#title' => t('Paste view code here'),
    '#required' => TRUE,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Import'),
    '#submit' => array('views_ui_import_submit'),
    '#validate' => array('views_ui_import_validate'),
  );
  return $form;
}

/**
 * Validate handler to import a view.
 */
function views_ui_import_validate($form, &$form_state) {
  $view = '';
  views_include('view');
  // Be forgiving if someone pastes views code that starts with '<?php'.
  if (substr($form_state['values']['view'], 0, 5) == '<?php') {
    $form_state['values']['view'] = substr($form_state['values']['view'], 5);
  }
  ob_start();
  eval($form_state['values']['view']);
  ob_end_clean();

  if (!is_object($view)) {
    return form_error($form['view'], t('Unable to interpret view code.'));
  }

  if (empty($view->api_version) || $view->api_version < 2) {
    form_error($form['view'], t('That view is not compatible with this version of Views.
      If you have a view from views1 you have to go to a drupal6 installation and import it there.'));
  }
  elseif (version_compare($view->api_version, views_api_version(), '>')) {
    form_error($form['view'], t('That view is created for the version @import_version of views, but you only have @api_version', array(
      '@import_version' => $view->api_version,
      '@api_version' => views_api_version())));
  }

  // View name must be alphanumeric or underscores, no other punctuation.
  if (!empty($form_state['values']['name']) && preg_match('/[^a-zA-Z0-9_]/', $form_state['values']['name'])) {
    form_error($form['name'], t('View name must be alphanumeric or underscores only.'));
  }

  if ($form_state['values']['name']) {
    $view->name = $form_state['values']['name'];
  }

  $test = views_get_view($view->name);
  if (!$form_state['values']['name_override']) {
    if ($test && $test->type != t('Default')) {
      form_set_error('', t('A view by that name already exists; please choose a different name'));
    }
  }
  else {
    if ($test->vid) {
      $view->vid = $test->vid;
    }
  }

  // Make sure base table gets set properly if it got moved.
  $view->update();

  $view->init_display();

  $broken = FALSE;
  // Make sure that all plugins and handlers needed by this view actually exist.
  foreach ($view->display as $id => $display) {
    if (empty($display->handler) || !empty($display->handler->broken)) {
      drupal_set_message(t('Display plugin @plugin is not available.', array('@plugin' => $display->display_plugin)), 'error');
      $broken = TRUE;
      continue;
    }

    $plugin = views_get_plugin('style', $display->handler->get_option('style_plugin'));
    if (!$plugin) {
      drupal_set_message(t('Style plugin @plugin is not available.', array('@plugin' => $display->handler->get_option('style_plugin'))), 'error');
      $broken = TRUE;
    }
    elseif ($plugin->uses_row_plugin()) {
      $plugin = views_get_plugin('row', $display->handler->get_option('row_plugin'));
      if (!$plugin) {
        drupal_set_message(t('Row plugin @plugin is not available.', array('@plugin' => $display->handler->get_option('row_plugin'))), 'error');
        $broken = TRUE;
      }
    }

    foreach (views_object_types() as $type => $info) {
      $handlers = $display->handler->get_handlers($type);
      if ($handlers) {
        foreach ($handlers as $id => $handler) {
          if ($handler->broken()) {
            drupal_set_message(t('@type handler @table.@field is not available.', array(
              '@type' => $info['stitle'],
              '@table' => $handler->table,
              '@field' => $handler->field,
            )), 'error');
            $broken = TRUE;
          }
        }
      }
    }
  }

  if ($broken) {
    form_set_error('', t('Unable to import view.'));
  }

  $form_state['view'] = &$view;
}

/**
 * Submit handler for view import.
 */
function views_ui_import_submit($form, &$form_state) {
  // Store in cache and then go to edit.
  views_ui_cache_set($form_state['view']);
  $form_state['redirect'] = 'admin/structure/views/view/' . $form_state['view']->name . '/edit';
}

/**
 * Validate that a view is complete and whole.
 */
function views_ui_edit_view_form_validate($form, &$form_state) {
  // Do not validate cancel or delete or revert.
  if (empty($form_state['clicked_button']['#value']) || $form_state['clicked_button']['#value'] != t('Save')) {
    return;
  }

  $errors = $form_state['view']->validate();
  if ($errors !== TRUE) {
    foreach ($errors as $error) {
      form_set_error('', $error);
    }
  }
}

/**
 * Submit handler for the edit view form.
 */
function views_ui_edit_view_form_submit($form, &$form_state) {
  // Go through and remove displayed scheduled for removal.
  foreach ($form_state['view']->display as $id => $display) {
    if (!empty($display->deleted)) {
      unset($form_state['view']->display[$id]);
    }
  }
  // Rename display ids if needed.
  foreach ($form_state['view']->display as $id => $display) {
    if (!empty($display->new_id)) {
      $form_state['view']->display[$id]->id = $display->new_id;
      // Redirect the user to the renamed display to be sure that the page itself exists and doesn't throw errors.
      $form_state['redirect'] = 'admin/structure/views/view/' . $form_state['view']->name . '/edit/' . $display->new_id;
    }
  }

  // Direct the user to the right url, if the path of the display has changed.
  if (!empty($_GET['destination'])) {
    $destination = $_GET['destination'];
    // Find out the first display which has a changed path and redirect to this url.
    $old_view = views_get_view($form_state['view']->name);
    foreach ($old_view->display as $id => $display) {
      // Only check for displays with a path.
      if (!isset($display->display_options['path'])) {
        continue;
      }
      $old_path = $display->display_options['path'];
      if (($display->display_plugin == 'page') && ($old_path == $destination) && ($old_path != $form_state['view']->display[$id]->display_options['path'])) {
        $destination = $form_state['view']->display[$id]->display_options['path'];
        unset($_GET['destination']);
      }
    }
    $form_state['redirect'] = $destination;
  }

  $form_state['view']->save();
  drupal_set_message(t('The view %name has been saved.', array('%name' => $form_state['view']->get_human_name())));

  // Make sure menu items get rebuilt as neces
  menu_rebuild();

  // Clear the views cache.
  cache_clear_all('*', 'cache_views');

  // Clear the page cache.
  cache_clear_all();

  // Remove this view from cache so we can edit it properly.
  ctools_object_cache_clear('view', $form_state['view']->name);
}

/**
 * Submit handler for the edit view form.
 */
function views_ui_edit_view_form_cancel($form, &$form_state) {
  // Remove this view from cache so edits will be lost.
  ctools_object_cache_clear('view', $form_state['view']->name);
  if (empty($form['view']->vid)) {
    // I seem to have to drupal_goto here because I can't get fapi to
    // honor the redirect target. Not sure what I screwed up here.
    drupal_goto('admin/structure/views');
  }
}

function views_ui_edit_view_form_delete($form, &$form_state) {
  unset($_REQUEST['destination']);
  // Redirect to the delete confirm page
  $form_state['redirect'] = array('admin/structure/views/view/' . $form_state['view']->name . '/delete', array('query' => drupal_get_destination() + array('cancel' => 'admin/structure/views/view/' . $form_state['view']->name . '/edit')));
}

/**
 * Add information about a section to a display.
 */
function views_ui_edit_form_get_bucket($type, $view, $display) {
  $build = array(
    '#theme_wrappers' => array('views_ui_display_tab_bucket'),
  );
  $types = views_object_types();

  $build['#overridden'] = FALSE;
  $build['#defaulted'] = FALSE;
  if (module_exists('advanced_help')) {
    $build['#item_help_icon'] = array(
      '#theme' => 'advanced_help_topic',
      '#module' => 'views',
      '#topic' => $type,
    );
  }

  $build['#name'] = $build['#title'] = $types[$type]['title'];

  // Different types now have different rearrange forms, so we use this switch
  // to get the right one.
  switch ($type) {
    case 'filter':
      $rearrange_url = "admin/structure/views/nojs/rearrange-$type/$view->name/$display->id/$type";
      $rearrange_text = t('And/Or, Rearrange');
      // TODO: Add another class to have another symbol for filter rearrange.
      $class = 'icon compact rearrange';
      break;
    case 'field':
      // Fetch the style plugin info so we know whether to list fields or not.
      $style_plugin = $display->handler->get_plugin();
      $uses_fields = $style_plugin && $style_plugin->uses_fields();
      if (!$uses_fields) {
        $build['fields'][] = array(
          '#markup' => t('The selected style or row format does not utilize fields.'),
          '#theme_wrappers' => array('views_container'),
          '#attributes' => array('class' => array('views-display-setting')),
        );
        return $build;
      }

    default:
      $rearrange_url = "admin/structure/views/nojs/rearrange/$view->name/$display->id/$type";
      $rearrange_text = t('Rearrange');
      $class = 'icon compact rearrange';
  }

  // Create an array of actions to pass to theme_links
  $actions = array();
  $count_handlers = count($display->handler->get_handlers($type));
  $actions['add'] = array(
    'title' => t('Add'),
    'href' => "admin/structure/views/nojs/add-item/$view->name/$display->id/$type",
    'attributes'=> array('class' => array('icon compact add', 'views-ajax-link'), 'title' => t('Add'), 'id' => 'views-add-' . $type),
    'html' => TRUE,
  );
  if ($count_handlers > 0) {
    $actions['rearrange'] = array(
      'title' => $rearrange_text,
      'href' => $rearrange_url,
      'attributes' => array('class' => array($class, 'views-ajax-link'), 'title' => t('Rearrange'), 'id' => 'views-rearrange-' . $type),
      'html' => TRUE,
    );
  }

  // Render the array of links
  $build['#actions'] = theme('links__ctools_dropbutton',
    array(
      'links' => $actions,
      'attributes' => array(
        'class' => array('inline', 'links', 'actions', 'horizontal', 'right')
      ),
      'class' => array('views-ui-settings-bucket-operations'),
    )
  );

  if (!$display->handler->is_default_display()) {
    if (!$display->handler->is_defaulted($types[$type]['plural'])) {
      $build['#overridden'] = TRUE;
    }
    else {
      $build['#defaulted'] = TRUE;
    }
  }

  // If there's an options form for the bucket, link to it.
  if (!empty($types[$type]['options'])) {
    $build['#title'] = l($build['#title'], "admin/structure/views/nojs/config-type/$view->name/$display->id/$type", array('attributes' => array('class' => array('views-ajax-link'), 'id' => 'views-title-' . $type)));
  }

  static $relationships = NULL;
  if (!isset($relationships)) {
    // Get relationship labels
    $relationships = array();
    // @todo: get_handlers()
    $handlers = $display->handler->get_option('relationships');
    if ($handlers) {
      foreach ($handlers as $id => $info) {
        $handler = $display->handler->get_handler('relationship', $id);
        $relationships[$id] = $handler->label();
      }
    }
  }

  // Filters can now be grouped so we do a little bit extra:
  $groups = array();
  $grouping = FALSE;
  if ($type == 'filter') {
    $group_info = $view->display_handler->get_option('filter_groups');
    // If there is only one group but it is using the "OR" filter, we still
    // treat it as a group for display purposes, since we want to display the
    // "OR" label next to items within the group.
    if (!empty($group_info['groups']) && (count($group_info['groups']) > 1 || current($group_info['groups']) == 'OR')) {
      $grouping = TRUE;
      $groups = array(0 => array());
    }
  }

  $build['fields'] = array();

  foreach ($display->handler->get_option($types[$type]['plural']) as $id => $field) {
    // Build the option link for this handler ("Node: ID = article").
    $build['fields'][$id] = array();
    $build['fields'][$id]['#theme'] = 'views_ui_display_tab_setting';

    $handler = $display->handler->get_handler($type, $id);
    if (empty($handler)) {
      $build['fields'][$id]['#class'][] = 'broken';
      $field_name = t('Broken/missing handler: @table > @field', array('@table' => $field['table'], '@field' => $field['field']));
      $build['fields'][$id]['#link'] = l($field_name, "admin/structure/views/nojs/config-item/$view->name/$display->id/$type/$id", array('attributes' => array('class' => array('views-ajax-link')), 'html' => TRUE));
      continue;
    }

    $field_name = check_plain($handler->ui_name(TRUE));
    if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
      $field_name = '(' . $relationships[$field['relationship']] . ') ' . $field_name;
    }

    $description = check_plain($handler->admin_summary());
    $link_text = $field_name . (empty($description) ? '' : " ($description)");
    $link_attributes = array('class' => array('views-ajax-link'));
    if (!empty($field['exclude'])) {
      $link_attributes['class'][] = 'views-field-excluded';
    }
    $build['fields'][$id]['#link'] = l($link_text, "admin/structure/views/nojs/config-item/$view->name/$display->id/$type/$id", array('attributes' => $link_attributes, 'html' => TRUE));
    $build['fields'][$id]['#class'][] = drupal_clean_css_identifier($display->id . '-' . $type . '-' . $id);
    if (!empty($view->changed_sections[$display->id . '-' . $type . '-' . $id])) {
      // @TODO: #changed is no longer being used?
      $build['fields'][$id]['#changed'] = TRUE;
    }

    if ($display->handler->use_group_by() && $handler->use_group_by()) {
      $build['fields'][$id]['#settings_links'][] = l('<span class="label">' . t('Aggregation settings') . '</span>', "admin/structure/views/nojs/config-item-group/$view->name/$display->id/$type/$id", array('attributes' => array('class' => 'views-button-configure views-ajax-link', 'title' => t('Aggregation settings')), 'html' => true));
    }

    if ($handler->has_extra_options()) {
      $build['fields'][$id]['#settings_links'][] = l('<span class="label">' . t('Settings') . '</span>', "admin/structure/views/nojs/config-item-extra/$view->name/$display->id/$type/$id", array('attributes' => array('class' => array('views-button-configure', 'views-ajax-link'), 'title' => t('Settings')), 'html' => true));
    }

    if ($grouping) {
      $gid = $handler->options['group'];

      // Show in default group if the group does not exist.
      if (empty($group_info['groups'][$gid])) {
        $gid = 0;
      }
      $groups[$gid][] = $id;
    }
  }

  // If using grouping, re-order fields so that they show up properly in the list.
  if ($type == 'filter' && $grouping) {
    $store = $build['fields'];
    $build['fields'] = array();
    foreach ($groups as $gid => $contents) {
      // Display an operator between each group.
      if (!empty($build['fields'])) {
        $build['fields'][] = array(
          '#theme' => 'views_ui_display_tab_setting',
          '#class' => array('views-group-text'),
          '#link' => ($group_info['operator'] == 'OR' ? t('OR') : t('AND')),
        );
      }
      // Display an operator between each pair of filters within the group.
      $keys = array_keys($contents);
      $last = end($keys);
      foreach ($contents as $key => $pid) {
        if ($key != $last) {
          $store[$pid]['#link'] .= '&nbsp;&nbsp;' . ($group_info['groups'][$gid] == 'OR' ? t('OR') : t('AND'));
        }
        $build['fields'][$pid] = $store[$pid];
      }
    }
  }

  return $build;
}

/**
 * Regenerate the current tab for AJAX updates.
 */
function views_ui_regenerate_tab(&$view, &$output, $display_id) {
  if (!$view->set_display('default')) {
    return;
  }

  // Regenerate the main display area.
  $build = views_ui_get_display_tab($view, $display_id);
  views_ui_add_microweights($build);
  $output[] = ajax_command_html('#views-tab-' . $display_id, drupal_render($build));

  // Regenerate the top area so changes to display names and order will appear.
  $build = views_ui_render_display_top($view, $display_id);
  views_ui_add_microweights($build);
  $output[] = ajax_command_replace('#views-display-top', drupal_render($build));
}

/**
 * Recursively adds microweights to a render array, similar to what form_builder() does for forms.
 *
 * @todo Submit a core patch to fix drupal_render() to do this, so that all
 *   render arrays automatically preserve array insertion order, as forms do.
 */
function views_ui_add_microweights(&$build) {
  $count = 0;
  foreach (element_children($build) as $key) {
    if (!isset($build[$key]['#weight'])) {
      $build[$key]['#weight'] = $count/1000;
    }
    views_ui_add_microweights($build[$key]);
    $count++;
  }
}

/**
 * Provide a standard set of Apply/Cancel/OK buttons for the forms. Also provide
 * a hidden op operator because the forms plugin doesn't seem to properly
 * provide which button was clicked.
 *
 * TODO: Is the hidden op operator still here somewhere, or is that part of the
 * docblock outdated?
 */
function views_ui_standard_form_buttons(&$form, &$form_state, $form_id, $name = NULL, $third = NULL, $submit = NULL) {
  $form['buttons'] = array(
    '#prefix' => '<div class="clearfix"><div class="form-buttons">',
    '#suffix' => '</div></div>',
  );

  if (empty($name)) {
    $name = t('Apply');
    $view = $form_state['view'];
    if (!empty($view->stack) && count($view->stack) > 1) {
      $name = t('Apply and continue');
    }
    $names = array(t('Apply'), t('Apply and continue'));
  }

  // Forms that are purely informational set an ok_button flag, so we know not
  // to create an "Apply" button for them.
  if (empty($form_state['ok_button'])) {
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => $name,
      // The regular submit handler ($form_id . '_submit') does not apply if
      // we're updating the default display. It does apply if we're updating
      // the current display. Since we have no way of knowing at this point
      // which display the user wants to update, views_ui_standard_submit will
      // take care of running the regular submit handler as appropriate.
      '#submit' => array('views_ui_standard_submit'),
    );
    // Form API button click detection requires the button's #value to be the
    // same between the form build of the initial page request, and the initial
    // form build of the request processing the form submission. Ideally, the
    // button's #value shouldn't change until the form rebuild step. However,
    // views_ui_ajax_form() implements a different multistep form workflow than
    // the Form API does, and adjusts $view->stack prior to form processing, so
    // we compensate by extending button click detection code to support any of
    // the possible button labels.
    if (isset($names)) {
      $form['buttons']['submit']['#values'] = $names;
      $form['buttons']['submit']['#process'] = array_merge(array('views_ui_form_button_was_clicked'), element_info_property($form['buttons']['submit']['#type'], '#process', array()));
    }
    // If a validation handler exists for the form, assign it to this button.
    if (function_exists($form_id . '_validate')) {
      $form['buttons']['submit']['#validate'][] = $form_id . '_validate';
    }
  }

  // Create a "Cancel" button. For purely informational forms, label it "OK".
  $cancel_submit = function_exists($form_id . '_cancel') ? $form_id . '_cancel' : 'views_ui_standard_cancel';
  $form['buttons']['cancel'] = array(
    '#type' => 'submit',
    '#value' => empty($form_state['ok_button']) ? t('Cancel') : t('Ok'),
    '#submit' => array($cancel_submit),
    '#validate' => array(),
  );

  // Some forms specify a third button, with a name and submit handler.
  if ($third) {
    if (empty($submit)) {
      $submit = 'third';
    }
    $third_submit = function_exists($form_id . '_' . $submit) ? $form_id . '_' . $submit : 'views_ui_standard_cancel';

    $form['buttons'][$submit] = array(
      '#type' => 'submit',
      '#value' => $third,
      '#validate' => array(),
      '#submit' => array($third_submit),
    );
  }

  // Compatibility, to be removed later: // TODO: When is "later"?
  // We used to set these items on the form, but now we want them on the $form_state:
  if (isset($form['#title'])) {
    $form_state['title'] = $form['#title'];
  }
  if (isset($form['#help_topic'])) {
    $form_state['help_topic'] = $form['#help_topic'];
  }
  if (isset($form['#help_module'])) {
    $form_state['help_module'] = $form['#help_module'];
  }
  if (isset($form['#url'])) {
    $form_state['url'] = $form['#url'];
  }
  if (isset($form['#section'])) {
    $form_state['#section'] = $form['#section'];
  }
  // Finally, we never want these cached -- our object cache does that for us.
  $form['#no_cache'] = TRUE;

  // If this isn't an ajaxy form, then we want to set the title.
  if (!empty($form['#title'])) {
    drupal_set_title($form['#title']);
  }
}

/**
 * Basic submit handler applicable to all 'standard' forms.
 *
 * This submit handler determines whether the user wants the submitted changes
 * to apply to the default display or to the current display, and dispatches
 * control appropriately.
 */
function views_ui_standard_submit($form, &$form_state) {
  // Determine whether the values the user entered are intended to apply to
  // the current display or the default display.

  list($was_defaulted, $is_defaulted, $revert) = views_ui_standard_override_values($form, $form_state);

  // Mark the changed section of the view as changed.
  // TODO: Document why we are doing this, and see if we still need it.
  if (!empty($form['#section'])) {
    $form_state['view']->changed_sections[$form['#section']] = TRUE;
  }

  // Based on the user's choice in the display dropdown, determine which display
  // these changes apply to.
  if ($revert) {
    // If it's revert just change the override and return.
    $display = &$form_state['view']->display[$form_state['display_id']];
    $display->handler->options_override($form, $form_state);

    // Don't execute the normal submit handling but still store the changed view into cache.
    views_ui_cache_set($form_state['view']);
    return;
  }
  elseif ($was_defaulted === $is_defaulted) {
    // We're not changing which display these form values apply to.
    // Run the regular submit handler for this form.
  }
  elseif ($was_defaulted && !$is_defaulted) {
    // We were using the default display's values, but we're now overriding
    // the default display and saving values specific to this display.
    $display = &$form_state['view']->display[$form_state['display_id']];
    // options_override toggles the override of this section.
    $display->handler->options_override($form, $form_state);
    $display->handler->options_submit($form, $form_state);
  }
  elseif (!$was_defaulted && $is_defaulted) {
    // We used to have an override for this display, but the user now wants
    // to go back to the default display.
    // Overwrite the default display with the current form values, and make
    // the current display use the new default values.
    $display = &$form_state['view']->display[$form_state['display_id']];
    // options_override toggles the override of this section.
    $display->handler->options_override($form, $form_state);
    $display->handler->options_submit($form, $form_state);
  }

  $submit_handler = $form['#form_id'] . '_submit';
  if (function_exists($submit_handler)) {
    $submit_handler($form, $form_state);
  }
}

/**
 * Return the was_defaulted, is_defaulted and revert state of a form.
 */
function views_ui_standard_override_values($form, $form_state) {
  // Make sure the dropdown exists in the first place.
  if (isset($form_state['values']['override']['dropdown'])) {
    // #default_value is used to determine whether it was the default value or not.
    // So the available options are: $display, 'default' and 'default_revert', not 'defaults'.
    $was_defaulted = ($form['override']['dropdown']['#default_value'] === 'defaults');
    $is_defaulted = ($form_state['values']['override']['dropdown'] === 'default');
    $revert = ($form_state['values']['override']['dropdown'] === 'default_revert');

    if ($was_defaulted !== $is_defaulted && isset($form['#section'])) {
      // We're changing which display these values apply to.
      // Update the #section so it knows what to mark changed.
      $form['#section'] = str_replace('default-', $form_state['display_id'] . '-', $form['#section']);
    }
  }
  else {
    // The user didn't get the dropdown for overriding the default display.
    $was_defaulted = FALSE;
    $is_defaulted = FALSE;
    $revert = FALSE;
  }

  return array($was_defaulted, $is_defaulted, $revert);
}

/**
 * Submit handler for cancel button
 */
function views_ui_standard_cancel($form, &$form_state) {
  if (!empty($form_state['view']->changed) && isset($form_state['view']->form_cache)) {
    unset($form_state['view']->form_cache);
    views_ui_cache_set($form_state['view']);
  }

  $form_state['redirect'] = 'admin/structure/views/view/' . $form_state['view']->name . '/edit';
}

/**
 * Add a <select> dropdown for a given section, allowing the user to
 * change whether this info is stored on the default display or on
 * the current display.
 */
function views_ui_standard_display_dropdown(&$form, &$form_state, $section) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $displays = $view->display;
  $current_display = $view->display[$display_id];

  // Add the "2 of 3" progress indicator.
  // @TODO: Move this to a separate function if it's needed on any forms that
  // don't have the display dropdown.
  if ($form_progress = views_ui_get_form_progress($view)) {
    $form['progress']['#markup'] = '<div id="views-progress-indicator">' . t('@current of @total', array('@current' => $form_progress['current'], '@total' => $form_progress['total'])) . '</div>';
    $form['progress']['#weight'] = -1001;
  }

  if ($current_display->handler->is_default_display()) {
    return;
  }

  // Determine whether any other displays have overrides for this section.
  $section_overrides = FALSE;
  $section_defaulted = $current_display->handler->is_defaulted($section);
  foreach ($displays as $id => $display) {
    if ($id === 'default' || $id === $display_id) {
      continue;
    }
    if ($display->handler && !$display->handler->is_defaulted($section)) {
      $section_overrides = TRUE;
    }
  }

  $display_dropdown['default'] = ($section_overrides ? t('All displays (except overridden)') : t('All displays'));
  $display_dropdown[$display_id] = t('This @display_type (override)', array('@display_type' => $current_display->display_plugin));
  // Only display the revert option if we are in a overridden section.
  if (!$section_defaulted) {
    $display_dropdown['default_revert'] = t('Revert to default');
  }

  $form['override'] = array(
    '#prefix' => '<div class="views-override clearfix container-inline">',
    '#suffix' => '</div>',
    '#weight' => -1000,
    '#tree' => TRUE,
  );
  $form['override']['dropdown'] = array(
    '#type' => 'select',
    '#title' => t('For'), // @TODO: Translators may need more context than this.
    '#options' => $display_dropdown,
  );
  if ($current_display->handler->is_defaulted($section)) {
    $form['override']['dropdown']['#default_value'] = 'defaults';
  }
  else {
    $form['override']['dropdown']['#default_value'] = $display_id;
  }

}

/**
 * Get the user's current progress through the form stack.
 *
 * @param $view
 *   The current view.
 *
 * @return
 *   FALSE if the user is not currently in a multiple-form stack. Otherwise,
 *   an associative array with the following keys:
 *   - current: The number of the current form on the stack.
 *   - total: The total number of forms originally on the stack.
 */
function views_ui_get_form_progress($view) {
  $progress = FALSE;
  if (!empty($view->stack)) {
    $stack = $view->stack;
    // The forms on the stack have integer keys that don't change as the forms
    // are completed, so we can see which ones are still left.
    $keys = array_keys($view->stack);
    // Add 1 to the array keys for the benefit of humans, who start counting
    // from 1 and not 0.
    $current = reset($keys) + 1;
    $total = end($keys) + 1;
    if ($total > 1) {
      $progress = array();
      $progress['current'] = $current;
      $progress['total'] = $total;
    }
  }
  return $progress;
}


// --------------------------------------------------------------------------
// Various subforms for editing the pieces of a view.

function views_ui_ajax_forms($key = NULL) {
  $forms = array(
    'display' => array(
      'form_id' => 'views_ui_edit_display_form',
      'args' => array('section'),
    ),
    'remove-display' => array(
      'form_id' => 'views_ui_remove_display_form',
      'args' => array(),
    ),
    'config-type' => array(
      'form_id' => 'views_ui_config_type_form',
      'args' => array('type'),
    ),
    'rearrange' => array(
      'form_id' => 'views_ui_rearrange_form',
      'args' => array('type'),
    ),
    'rearrange-filter' => array(
      'form_id' => 'views_ui_rearrange_filter_form',
      'args' => array('type'),
    ),
    'reorder-displays' => array(
      'form_id' => 'views_ui_reorder_displays_form',
      'args' => array(),
    ),
    'add-item' => array(
      'form_id' => 'views_ui_add_item_form',
      'args' => array('type'),
    ),
    'config-item' => array(
      'form_id' => 'views_ui_config_item_form',
      'args' => array('type', 'id'),
    ),
    'config-item-extra' => array(
      'form_id' => 'views_ui_config_item_extra_form',
      'args' => array('type', 'id'),
    ),
    'config-item-group' => array(
      'form_id' => 'views_ui_config_item_group_form',
      'args' => array('type', 'id'),
    ),
    'config-style' => array(
      'form_id' => 'views_ui_config_style_form',
      'args' => array('type', 'id'),
    ),
    'edit-details' => array(
      'form_id' => 'views_ui_edit_details_form',
      'args' => array(),
    ),
    'analyze' => array(
      'form_id' => 'views_ui_analyze_view_form',
      'args' => array(),
    ),
  );

  if ($key) {
    return !empty($forms[$key]) ? $forms[$key] : NULL;
  }

  return $forms;
}

/**
 * Build a form identifier that we can use to see if one form
 * is the same as another. Since the arguments differ slightly
 * we do a lot of spiffy concatenation here.
 */
function views_ui_build_identifier($key, $view, $display_id, $args) {
  $form = views_ui_ajax_forms($key);
  // Automatically remove the single-form cache if it exists and
  // does not match the key.
  $identifier = implode('-', array($key, $view->name, $display_id));

  foreach ($form['args'] as $id) {
    $arg = (!empty($args)) ? array_shift($args) : NULL;
    $identifier .= '-' . $arg;
  }
  return $identifier;
}

/**
 * Build up a $form_state object suitable for use with drupal_build_form
 * based on known information about a form.
 */
function views_ui_build_form_state($js, $key, &$view, $display_id, $args) {
  $form = views_ui_ajax_forms($key);
  // Build up form state
  $form_state = array(
    'form_key' => $key,
    'form_id' => $form['form_id'],
    'view' => &$view,
    'ajax' => $js,
    'display_id' => $display_id,
    'no_redirect' => TRUE,
  );

  foreach ($form['args'] as $id) {
    $form_state[$id] = (!empty($args)) ? array_shift($args) : NULL;
  }

  return $form_state;
}

/**
 * Create the URL for one of our standard AJAX forms based upon known
 * information about the form.
 */
function views_ui_build_form_url($form_state) {
  $form = views_ui_ajax_forms($form_state['form_key']);
  $ajax = empty($form_state['ajax']) ? 'nojs' : 'ajax';
  $name = $form_state['view']->name;
  $url = "admin/structure/views/$ajax/$form_state[form_key]/$name/$form_state[display_id]";
  foreach ($form['args'] as $arg) {
    $url .= '/' . $form_state[$arg];
  }
  return $url;
}

/**
 * Add another form to the stack; clicking 'apply' will go to this form
 * rather than closing the ajax popup.
 */
function views_ui_add_form_to_stack($key, &$view, $display_id, $args, $top = FALSE, $rebuild_keys = FALSE) {
  if (empty($view->stack)) {
    $view->stack = array();
  }

  $stack = array(views_ui_build_identifier($key, $view, $display_id, $args), $key, &$view, $display_id, $args);
  // If we're being asked to add this form to the bottom of the stack, no
  // special logic is required. Our work is equally easy if we were asked to add
  // to the top of the stack, but there's nothing in it yet.
  if (!$top || empty($view->stack)) {
    $view->stack[] = $stack;
  }
  // If we're adding to the top of an existing stack, we have to maintain the
  // existing integer keys, so they can be used for the "2 of 3" progress
  // indicator (which will now read "2 of 4").
  else {
    $keys = array_keys($view->stack);
    $first = current($keys);
    $last = end($keys);
    for ($i = $last; $i >= $first; $i--) {
      if (!isset($view->stack[$i])) {
        continue;
      }
      // Move form number $i to the next position in the stack.
      $view->stack[$i + 1] = $view->stack[$i];
      unset($view->stack[$i]);
    }
    // Now that the previously $first slot is free, move the new form into it.
    $view->stack[$first] = $stack;
    ksort($view->stack);

    // Start the keys from 0 again, if requested.
    if ($rebuild_keys) {
      $view->stack = array_values($view->stack);
    }
  }
}

/**
 * Generic entry point to handle forms.
 *
 * We do this for consistency and to make it easy to chain forms
 * together.
 */
function views_ui_ajax_form($js, $key, $view, $display_id = '') {
  // Reset the cache of IDs. Drupal rather aggressively prevents id duplication
  // but this causes it to remember IDs that are no longer even being used.
  if (isset($_POST['ajax_html_ids'])) {
    unset($_POST['ajax_html_ids']);
  }

  $form = views_ui_ajax_forms($key);
  if (empty($form)) {
    return MENU_NOT_FOUND;
  }

  views_include('ajax');
  $args = func_get_args();
  // Remove the known args
  array_splice($args, 0, 4);

  $form_state = views_ui_build_form_state($js, $key, $view, $display_id, $args);
  // check to see if this is the top form of the stack. If it is, pop
  // it off; if it isn't, the user clicked somewhere else and the stack is
  // now irrelevant.
  if (!empty($view->stack)) {
    $identifier = views_ui_build_identifier($key, $view, $display_id, $args);
    // Retrieve the first form from the stack without changing the integer keys,
    // as they're being used for the "2 of 3" progress indicator.
    reset($view->stack);
    list($key, $top) = each($view->stack);
    unset($view->stack[$key]);

    if (array_shift($top) != $identifier) {
      $view->stack = array();
    }
  }

  // Automatically remove the form cache if it is set and the key does
  // not match. This way navigating away from the form without hitting
  // update will work.
  if (isset($view->form_cache) && $view->form_cache['key'] != $key) {
    unset($view->form_cache);
  }

  // With the below logic, we may end up rendering a form twice (or two forms
  // each sharing the same element ids), potentially resulting in
  // drupal_add_js() being called twice to add the same setting. drupal_get_js()
  // is ok with that, but until ajax_render() is (http://drupal.org/node/208611),
  // reset the drupal_add_js() static before rendering the second time.
  $drupal_add_js_original = drupal_add_js();
  $drupal_add_js = &drupal_static('drupal_add_js');
  $output = views_ajax_form_wrapper($form_state['form_id'], $form_state);
  if ($form_state['submitted'] && empty($form_state['rerender'])) {
    // Sometimes we need to re-generate the form for multi-step type operations.
    $object = NULL;
    if (!empty($view->stack)) {
      $drupal_add_js = $drupal_add_js_original;
      $stack = $view->stack;
      $top = array_shift($stack);
      $top[0] = $js;
      $form_state = call_user_func_array('views_ui_build_form_state', $top);
      $form_state['input'] = array();
      $form_state['url'] = url(views_ui_build_form_url($form_state));
      if (!$js) {
        return drupal_goto(views_ui_build_form_url($form_state));
      }
      $output = views_ajax_form_wrapper($form_state['form_id'], $form_state);
    }
    elseif (!$js) {
      // if nothing on the stack, non-js forms just go back to the main view editor.
      return drupal_goto("admin/structure/views/view/$view->name/edit");
    }
    else {
      $output = array();
      $output[] = views_ajax_command_dismiss_form();
      $output[] = views_ajax_command_show_buttons();
      $output[] = views_ajax_command_trigger_preview();
      if (!empty($form_state['#page_title'])) {
        $output[] = views_ajax_command_replace_title($form_state['#page_title']);
      }
    }
    // If this form was for view-wide changes, there's no need to regenerate
    // the display section of the form.
    if ($display_id !== '') {
      views_ui_regenerate_tab($view, $output, $display_id);
    }
  }

  return $js ? array('#type' => 'ajax', '#commands' => $output) : $output;
}

/**
 * Submit handler to add a restore a removed display to a view.
 */
function views_ui_remove_display_form_restore($form, &$form_state) {
  // Create the new display
  $id = $form_state['display_id'];
  $form_state['view']->display[$id]->deleted = FALSE;

  // Store in cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Form constructor callback to display analysis information on a view
 */
function views_ui_analyze_view_form($form, &$form_state) {
  $view = &$form_state['view'];

  $form['#title'] = t('View analysis');
  $form['#section'] = 'analyze';

  views_include('analyze');
  $messages = views_analyze_view($view);

  $form['analysis'] = array(
    '#prefix' => '<div class="form-item">',
    '#suffix' => '</div>',
    '#markup' => views_analyze_format_result($view, $messages),
  );

  // Inform the standard button function that we want an OK button.
  $form_state['ok_button'] = TRUE;
  views_ui_standard_form_buttons($form, $form_state, 'views_ui_analyze_view_form');
  return $form;
}

/**
 * Submit handler for views_ui_analyze_view_form
 */
function views_ui_analyze_view_form_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/structure/views/view/' . $form_state['view']->name . '/edit';
}

/**
 * Form constructor callback to reorder displays on a view
 */
function views_ui_reorder_displays_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];

  $form['view'] = array('#type' => 'value', '#value' => $view);

  $form['#tree'] = TRUE;

  $last_display = end($view->display);

  foreach ($view->display as $display) {
    $form[$display->id] = array(
      'title'  => array('#markup' => $display->display_title),
      'weight' => array(
        '#type' => 'weight',
        '#value' => $display->position,
        '#delta' => $last_display->position,
        '#title' => t('Weight for @display', array('@display' => $display->display_title)),
        '#title_display' => 'invisible',
      ),
      '#tree' => TRUE,
      '#display' => $display,
      'removed' => array(
        '#type' => 'checkbox',
        '#id' => 'display-removed-' . $display->id,
        '#attributes' => array('class' => array('views-remove-checkbox')),
        '#default_value' => isset($display->deleted),
      ),
    );

    if (isset($display->deleted) && $display->deleted) {
      $form[$display->id]['deleted'] = array('#type' => 'value', '#value' => TRUE);
    }
    if ($display->id === 'default') {
      unset($form[$display->id]['weight']);
      unset($form[$display->id]['removed']);
    }

  }

  $form['#title'] = t('Displays Reorder');
  $form['#section'] = 'reorder';

  // Add javascript settings that will be added via $.extend for tabledragging
  $form['#js']['tableDrag']['reorder-displays']['weight'][0] = array(
    'target' => 'weight',
    'source' => NULL,
    'relationship' => 'sibling',
    'action' => 'order',
    'hidden' => TRUE,
    'limit' => 0,
  );

  $form['#action'] = url('admin/structure/views/nojs/reorder-displays/' . $view->name . '/' . $display_id);

  views_ui_standard_form_buttons($form, $form_state, 'views_ui_reorder_displays_form');

  return $form;
}

/**
 * Display position sorting function
 */
function _views_position_sort($display1, $display2) {
  if ($display1->position != $display2->position) {
    return $display1->position < $display2->position ? -1 : 1;
  }

  return 0;
}

/**
 * Submit handler for rearranging display form
 */
function views_ui_reorder_displays_form_submit($form, &$form_state) {
  foreach($form_state['input'] as $display => $info) {
    // add each value that is a field with a weight to our list, but only if
    // it has had its 'removed' checkbox checked.
    if (is_array($info) && isset($info['weight']) && empty($info['removed'])) {
      $order[$display] = $info['weight'];
    }
  }

  // Sort the order array
  asort($order);

  // Fixing up positions
  $position = 2;

  foreach(array_keys($order) as $display) {
    $order[$display] = $position++;
  }

  // Setting up position and removing deleted displays
  $displays = $form_state['view']->display;
  foreach($displays as $display_id => $display) {
    // Don't touch the default !!!
    if ($display_id === 'default') {
      continue;
    }
    if (isset($order[$display_id])) {
      $form_state['view']->display[$display_id]->position = $order[$display_id];
    }
    else {
      $form_state['view']->display[$display_id]->deleted = TRUE;
    }
  }

  // Sorting back the display array as the position is not enough
  uasort($form_state['view']->display, '_views_position_sort');

  // Store in cache
  views_ui_cache_set($form_state['view']);
  $form_state['redirect'] = array('admin/structure/views/view/' . $form_state['view']->name . '/edit', array('fragment' => 'views-tab-default'));
}

/**
 * Turn the reorder form into a proper table
 */
function theme_views_ui_reorder_displays_form($vars) {
  $form = $vars['form'];
  $rows = array();
  foreach (element_children($form) as $key) {
    if (isset($form[$key]['#display'])) {
      $display = &$form[$key];

      $row = array();
      $row[] = drupal_render($display['title']);
      $form[$key]['weight']['#attributes']['class'] = array('weight');
      $row[] = drupal_render($form[$key]['weight']);
      if (isset($display['removed'])) {
        $row[] = drupal_render($form[$key]['removed']) .
          l('<span>' . t('Remove') . '</span>',
            'javascript:void()',
            array(
              'attributes' => array(
                'id' => 'display-remove-link-' . $key,
                'class' => array('views-button-remove display-remove-link'),
                'alt' => t('Remove this display'),
                'title' => t('Remove this display')),
              'html' => TRUE));
      }
      else {
        $row[] = '';
      }
      $class = array();
      $styles = array();
      if (isset($form[$key]['weight']['#type'])) {
        $class[] = 'draggable';
      }
      if (isset($form[$key]['deleted']['#value']) && $form[$key]['deleted']['#value']) {
        $styles[] = 'display: none;';
      }
      $rows[] = array('data' => $row, 'class' => $class, 'id' => 'display-row-' . $key, 'style' => $styles);
    }
  }

  $header = array(t('Display'), t('Weight'), t('Remove'));
  $output = '';
  drupal_add_tabledrag('reorder-displays', 'order', 'sibling', 'weight');

  $output = drupal_render($form['override']);
  $output .= theme('table',
    array('header' => $header,
    'rows' => $rows,
    'attributes' => array('id' => 'reorder-displays'),
  ));
  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Form builder to edit details of a view.
 */
function views_ui_edit_details_form($form, &$form_state) {
  $view = &$form_state['view'];

  $form['#title'] = t('View name and description');
  $form['#section'] = 'details';

  $form['details'] = array(
    '#theme_wrappers' => array('container'),
    '#attributes' => array('class' => array('scroll')),
  );
  $form['details']['human_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Human-readable name'),
    '#description' => t('A descriptive human-readable name for this view. Spaces are allowed'),
    '#default_value' => $view->get_human_name(),
  );
  $form['details']['tag'] = array(
    '#type' => 'textfield',
    '#title' => t('View tag'),
    '#description' => t('Optionally, enter a comma delimited list of tags for this view to use in filtering and sorting views on the administrative page.'),
    '#default_value' => $view->tag,
    '#autocomplete_path' => 'admin/views/ajax/autocomplete/tag',
  );
  $form['details']['description'] = array(
    '#type' => 'textfield',
    '#title' => t('View description'),
    '#description' => t('This description will appear on the Views administrative UI to tell you what the view is about.'),
    '#default_value' => $view->description,
  );

  views_ui_standard_form_buttons($form, $form_state, 'views_ui_edit_details_form');
  return $form;
}

/**
 * Submit handler for views_ui_edit_details_form.
 */
function views_ui_edit_details_form_submit($form, &$form_state) {
  $view = $form_state['view'];
  foreach ($form_state['values'] as $key => $value) {
    // Only save values onto the view if they're actual view properties
    // (as opposed to 'op' or 'form_build_id').
    if (isset($form['details'][$key])) {
      $view->$key = $value;
    }
  }
  $form_state['#page_title'] = views_ui_edit_page_title($view);
  views_ui_cache_set($view);
}

/**
 * Form constructor callback to edit display of a view
 */
function views_ui_edit_display_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $section = $form_state['section'];

  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = &$view->display[$display_id];

  // Get form from the handler.
  $form['options'] = array(
    '#theme_wrappers' => array('container'),
    '#attributes' => array('class' => array('scroll')),
  );
  $display->handler->options_form($form['options'], $form_state);

  // The handler options form sets $form['#title'], which we need on the entire
  // $form instead of just the ['options'] section.
  $form['#title'] = $form['options']['#title'];
  unset($form['options']['#title']);

  // Move the override dropdown out of the scrollable section of the form.
  if (isset($form['options']['override'])) {
    $form['override'] = $form['options']['override'];
    unset($form['options']['override']);
  }

  $name = NULL;
  if (isset($form_state['update_name'])) {
    $name = $form_state['update_name'];
  }

  views_ui_standard_form_buttons($form, $form_state, 'views_ui_edit_display_form', $name);
  return $form;
}

/**
 * Validate handler for views_ui_edit_display_form
 */
function views_ui_edit_display_form_validate($form, &$form_state) {
  $display = &$form_state['view']->display[$form_state['display_id']];
  $display->handler->options_validate($form['options'], $form_state);

  if (form_get_errors()) {
    $form_state['rerender'] = TRUE;
  }
}

/**
 * Submit handler for views_ui_edit_display_form
 */
function views_ui_edit_display_form_submit($form, &$form_state) {
  $display = &$form_state['view']->display[$form_state['display_id']];
  $display->handler->options_submit($form, $form_state);

  views_ui_cache_set($form_state['view']);
}

/**
 * Override handler for views_ui_edit_display_form
 *
 * @TODO: Not currently used. Remove unless we implement an override toggle.
 */
function views_ui_edit_display_form_override($form, &$form_state) {
  $display = &$form_state['view']->display[$form_state['display_id']];
  $display->handler->options_override($form, $form_state);

  views_ui_cache_set($form_state['view']);
  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
}

/**
 * Form to config items in the views UI.
 */
function views_ui_config_type_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];

  $types = views_object_types();
  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = &$view->display[$display_id];
  $form['#title'] = t('Configure @type', array('@type' => $types[$type]['ltitle']));
  $form['#section'] = $display_id . 'config-item';

  if ($display->handler->defaultable_sections($types[$type]['plural'])) {
    $form_state['section'] = $types[$type]['plural'];
    views_ui_standard_display_dropdown($form, $form_state, $form_state['section']);
  }

  if (!empty($types[$type]['options']) && function_exists($types[$type]['options'])) {
    $options = $type . '_options';
    $form[$options] = array('#tree' => TRUE);
    $types[$type]['options']($form, $form_state);
  }

  $name = NULL;
  if (isset($form_state['update_name'])) {
    $name = $form_state['update_name'];
  }

  views_ui_standard_form_buttons($form, $form_state, 'views_ui_config_type_form', $name);
  return $form;
}

/**
 * Submit handler for type configuration form
 */
function views_ui_config_type_form_submit($form, &$form_state) {
  $types = views_object_types();
  $display = &$form_state['view']->display[$form_state['display_id']];

  // Store in cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Form to rearrange items in the views UI.
 */
function views_ui_rearrange_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];

  $types = views_object_types();
  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = &$view->display[$display_id];
  $form['#title'] = t('Rearrange @type', array('@type' => $types[$type]['ltitle']));
  $form['#section'] = $display_id . 'rearrange-item';

  if ($display->handler->defaultable_sections($types[$type]['plural'])) {
    $form_state['section'] = $types[$type]['plural'];
    views_ui_standard_display_dropdown($form, $form_state, $form_state['section']);
  }

  $count = 0;

  // Get relationship labels
  $relationships = array();
  foreach ($display->handler->get_handlers('relationship') as $id => $handler) {
    $relationships[$id] = $handler->label();
    $handlers = $display->handler->get_option('relationships');
    if ($handlers) {
      foreach ($handlers as $id => $info) {
        $handler = $display->handler->get_handler('relationship', $id);
        $relationships[$id] = $handler->label();
      }
    }
  }

  // Filters can now be grouped so we do a little bit extra:
  $groups = array();
  $grouping = FALSE;
  if ($type == 'filter') {
    $group_info = $view->display_handler->get_option('filter_groups');
    if (!empty($group_info['groups']) && count($group_info['groups']) > 1) {
      $grouping = TRUE;
      $groups = array(0 => array());
    }
  }

  foreach ($display->handler->get_option($types[$type]['plural']) as $id => $field) {
    $form['fields'][$id] = array('#tree' => TRUE);
    $form['fields'][$id]['weight'] = array(
      '#type' => 'textfield',
      '#default_value' => ++$count,
    );
    $handler = $display->handler->get_handler($type, $id);
    if ($handler) {
      $name = $handler->ui_name() . ' ' . $handler->admin_summary();
      if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
        $name = '(' . $relationships[$field['relationship']] . ') ' . $name;
      }

      $form['fields'][$id]['name'] = array(
        '#markup' => $name,
      );
    }
    else {
      $form['fields'][$id]['name'] = array('#markup' => t('Broken field @id', array('@id' => $id)));
    }
    $form['fields'][$id]['removed'] = array(
      '#type' => 'checkbox',
      '#id' => 'views-removed-' . $id,
      '#attributes' => array('class' => array('views-remove-checkbox')),
      '#default_value' => 0,
    );
  }

  // Add javascript settings that will be added via $.extend for tabledragging
  $form['#js']['tableDrag']['arrange']['weight'][0] = array(
    'target' => 'weight',
    'source' => NULL,
    'relationship' => 'sibling',
    'action' => 'order',
    'hidden' => TRUE,
    'limit' => 0,
  );

  $name = NULL;
  if (isset($form_state['update_name'])) {
    $name = $form_state['update_name'];
  }

  views_ui_standard_form_buttons($form, $form_state, 'views_ui_rearrange_form');
  return $form;
}

/**
 * Turn the rearrange form into a proper table
 */
function theme_views_ui_rearrange_form($variables) {
  $form = $variables['form'];

  $rows = array();
  foreach (element_children($form['fields']) as $id) {
    if (isset($form['fields'][$id]['name'])) {
      $row = array();
      $row[] = drupal_render($form['fields'][$id]['name']);
      $form['fields'][$id]['weight']['#attributes']['class'] = array('weight');
      $row[] = drupal_render($form['fields'][$id]['weight']);
      $row[] = drupal_render($form['fields'][$id]['removed']) . l('<span>' . t('Remove') . '</span>', 'javascript:void()', array('attributes' => array('id' => 'views-remove-link-' . $id, 'class' => array('views-hidden', 'views-button-remove', 'views-remove-link'), 'alt' => t('Remove this item'), 'title' => t('Remove this item')), 'html' => TRUE));
      $rows[] = array('data' => $row, 'class' => array('draggable'), 'id' => 'views-row-' . $id);
    }
  }
  if (empty($rows)) {
    $rows[] = array(array('data' => t('No fields available.'), 'colspan' => '2'));
  }

  $header = array('', t('Weight'), t('Remove'));
  $output = drupal_render($form['override']);
  $output .= '<div class="scroll">';
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'arrange')));
  $output .= '</div>';
  $output .= drupal_render_children($form);
  drupal_add_tabledrag('arrange', 'order', 'sibling', 'weight');

  return $output;
}

/**
 * Theme the expose filter form.
 */
function theme_views_ui_expose_filter_form($variables) {
  $form = $variables['form'];
  $more = drupal_render($form['more']);

  $output = drupal_render($form['form_description']);
  $output .= drupal_render($form['expose_button']);
  if (isset($form['required'])) {
    $output .= drupal_render($form['required']);
  }
  $output .= drupal_render($form['label']);

  $output .= drupal_render($form['operator']);
  $output .= drupal_render($form['value']);

  if (isset($form['use_operator'])) {
    $output .= '<div class="views-left-40">';
    $output .= drupal_render($form['use_operator']);
    $output .= '</div>';
  }

  // Only output the right column markup if there's a left column to begin with
  if (!empty($form['operator']['#type'])) {
    $output .= '<div class="views-right-60">';
    $output .= drupal_render_children($form);
    $output .= '</div>';
  }
  else {
    $output .= drupal_render_children($form);
  }

  $output .= $more;

  return $output;
}

/**
 * Submit handler for rearranging form
 */
function views_ui_rearrange_form_submit($form, &$form_state) {
  $types = views_object_types();
  $display = &$form_state['view']->display[$form_state['display_id']];

  $old_fields = $display->handler->get_option($types[$form_state['type']]['plural']);
  $new_fields = $order = array();

  // Make an array with the weights
  foreach ($form_state['values'] as $field => $info) {
    // add each value that is a field with a weight to our list, but only if
    // it has had its 'removed' checkbox checked.
    if (is_array($info) && isset($info['weight']) && empty($info['removed'])) {
      $order[$field] = $info['weight'];
    }
  }

  // Sort the array
  asort($order);

  // Create a new list of fields in the new order.
  foreach (array_keys($order) as $field) {
    $new_fields[$field] = $old_fields[$field];
  }
  $display->handler->set_option($types[$form_state['type']]['plural'], $new_fields);

  // Store in cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Form to rearrange items in the views UI.
 */
function views_ui_rearrange_filter_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];

  $types = views_object_types();
  if (!$view->set_display($display_id)) {
    views_ajax_render(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = &$view->display[$display_id];
  $form['#title'] = check_plain($display->display_title) . ': ';
  $form['#title'] .= t('Rearrange @type', array('@type' => $types[$type]['ltitle']));
  $form['#section'] = $display_id . 'rearrange-item';

  if ($display->handler->defaultable_sections($types[$type]['plural'])) {
    $form_state['section'] = $types[$type]['plural'];
    views_ui_standard_display_dropdown($form, $form_state, $form_state['section']);
  }

  if (!empty($view->form_cache)) {
    $groups = $view->form_cache['groups'];
    $handlers = $view->form_cache['handlers'];
  }
  else {
    $groups = $display->handler->get_option('filter_groups');
    $handlers = $display->handler->get_option($types[$type]['plural']);
  }
  $count = 0;

  // Get relationship labels
  $relationships = array();
  foreach ($display->handler->get_handlers('relationship') as $id => $handler) {
    $relationships[$id] = $handler->label();
  }

  $group_options = array();

  /**
   * Filter groups is an array that contains:
   * array(
   *   'operator' => 'and' || 'or',
   *   'groups' => array(
   *     $group_id => 'and' || 'or',
   *   ),
   * );
   */

  $grouping = count(array_keys($groups['groups'])) > 1;

  $form['filter_groups']['#tree'] = TRUE;
  $form['filter_groups']['operator'] = array(
    '#type' => 'select',
    '#options' => array (
      'AND' => t('And'),
      'OR' => t('Or'),
    ),
    '#default_value' => $groups['operator'],
    '#attributes' => array(
      'class' => array('warning-on-change'),
    ),
    '#title' => t('Operator to use on all groups'),
    '#description' => t('Either "group 0 AND group 1 AND group 2" or "group 0 OR group 1 OR group 2", etc'),
    '#access' => $grouping,
  );

  $form['remove_groups']['#tree'] = TRUE;

  foreach ($groups['groups'] as $id => $group) {
    $form['filter_groups']['groups'][$id] = array(
      '#title' => t('Operator'),
      '#type' => 'select',
      '#options' => array(
        'AND' => t('And'),
        'OR' => t('Or'),
      ),
      '#default_value' => $group,
      '#attributes' => array(
        'class' => array('warning-on-change'),
      ),
    );

    $form['remove_groups'][$id] = array(); // to prevent a notice
    if ($id != 1) {
      $form['remove_groups'][$id] = array(
        '#type' => 'submit',
        '#value' => t('Remove group @group', array('@group' => $id)),
        '#id' => "views-remove-group-$id",
        '#attributes' => array(
          'class' => array('views-remove-group'),
        ),
        '#group' => $id,
      );
    }
    $group_options[$id] = $id == 1 ? t('Default group') : t('Group @group', array('@group' => $id));
    $form['#group_renders'][$id] = array();
  }

  $form['#group_options'] = $group_options;
  $form['#groups'] = $groups;
  // We don't use get_handlers() because we want items without handlers to
  // appear and show up as 'broken' so that the user can see them.
  $form['filters'] = array('#tree' => TRUE);
  foreach ($handlers as $id => $field) {
    // If the group does not exist, move the filters to the default group.
    if (empty($field['group']) || empty($groups['groups'][$field['group']])) {
      $field['group'] = 1;
    }

    $handler = $display->handler->get_handler($type, $id);
    if ($grouping && $handler && !$handler->can_group()) {
      $field['group'] = 'ungroupable';
    }

    // If not grouping and the handler is set ungroupable, move it back to
    // the default group to prevent weird errors from having it be in its
    // own group:
    if (!$grouping && $field['group'] == 'ungroupable') {
      $field['group'] = 1;
    }

    // Place this item into the proper group for rendering.
    $form['#group_renders'][$field['group']][] = $id;

    $form['filters'][$id]['weight'] = array(
      '#type' => 'textfield',
      '#default_value' => ++$count,
      '#size' => 8,
    );
    $form['filters'][$id]['group'] = array(
      '#type' => 'select',
      '#options' => $group_options,
      '#default_value' => $field['group'],
      '#attributes' => array(
        'class' => array('views-region-select', 'views-region-' . $id),
      ),
      '#access' => $field['group'] !== 'ungroupable',
    );

    if ($handler) {
      $name = $handler->ui_name() . ' ' . $handler->admin_summary();
      if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
        $name = '(' . $relationships[$field['relationship']] . ') ' . $name;
      }

      $form['filters'][$id]['name'] = array(
        '#markup' => $name,
      );
    }
    else {
      $form['filters'][$id]['name'] = array('#markup' => t('Broken field @id', array('@id' => $id)));
    }
    $form['filters'][$id]['removed'] = array(
      '#type' => 'checkbox',
      '#id' => 'views-removed-' . $id,
      '#attributes' => array('class' => array('views-remove-checkbox')),
      '#default_value' => 0,
    );
  }

  if (isset($form_state['update_name'])) {
    $name = $form_state['update_name'];
  }

  views_ui_standard_form_buttons($form, $form_state, 'views_ui_rearrange_filter_form');
  $form['buttons']['add_group'] = array(
    '#type' => 'submit',
    '#value' => t('Create new filter group'),
    '#id' => 'views-add-group',
    '#group' => 'add',
  );

  return $form;
}

/**
 * Turn the rearrange form into a proper table
 */
function theme_views_ui_rearrange_filter_form(&$vars) {
  $form = $vars['form'];
  $rows = $ungroupable_rows = array();
  // Enable grouping only if > 1 group.
  $grouping = count(array_keys($form['#group_options'])) > 1;

  foreach ($form['#group_renders'] as $group_id => $contents) {
    // Header row for the group.
    if ($group_id !== 'ungroupable') {
      // Set up tabledrag so that it changes the group dropdown when rows are
      // dragged between groups.
      drupal_add_tabledrag('views-rearrange-filters', 'match', 'sibling', 'views-group-select', 'views-group-select-' . $group_id);

      // Title row, spanning all columns.
      $row = array();
      // Add a cell to the first row, containing the group operator.
      $row[] = array('class' => array('group', 'group-operator', 'container-inline'), 'data' => drupal_render($form['filter_groups']['groups'][$group_id]), 'rowspan' => max(array(2, count($contents) + 1)));
      // Title.
      $row[] = array('class' => array('group', 'group-title'), 'data' => '<span>' . $form['#group_options'][$group_id] . '</span>', 'colspan' => 4);
      $rows[] = array('class' => array('views-group-title'), 'data' => $row, 'id' => 'views-group-title-' . $group_id);

      // Row which will only appear if the group has nothing in it.
      $row = array();
      $class = 'group-' . (count($contents) ? 'populated' : 'empty');
      $instructions = '<span>' . t('No filters have been added.') . '</span> <span class="js-only">' . t('Drag to add filters.') . '</span>';
      // When JavaScript is enabled, the button for removing the group (if it's
      // present) should be hidden, since it will be replaced by a link on the
      // client side.
      if (!empty($form['remove_groups'][$group_id]['#type']) && $form['remove_groups'][$group_id]['#type'] == 'submit') {
        $form['remove_groups'][$group_id]['#attributes']['class'][] = 'js-hide';
      }
      $row[] = array('colspan' => 5, 'data' => $instructions . drupal_render($form['remove_groups'][$group_id]));
      $rows[] = array('class' => array("group-message", "group-$group_id-message", $class), 'data' => $row, 'id' => 'views-group-' . $group_id);
    }

    foreach ($contents as $id) {
      if (isset($form['filters'][$id]['name'])) {
        $row = array();
        $row[] = drupal_render($form['filters'][$id]['name']);
        $form['filters'][$id]['weight']['#attributes']['class'] = array('weight');
        $row[] = drupal_render($form['filters'][$id]['weight']);
        $form['filters'][$id]['group']['#attributes']['class'] = array('views-group-select views-group-select-' . $group_id);
        $row[] = drupal_render($form['filters'][$id]['group']);
        $form['filters'][$id]['removed']['#attributes']['class'][] = 'js-hide';
        $row[] = drupal_render($form['filters'][$id]['removed']) . l('<span>' . t('Remove') . '</span>', 'javascript:void()', array('attributes' => array('id' => 'views-remove-link-' . $id, 'class' => array('views-hidden', 'views-button-remove', 'views-groups-remove-link', 'views-remove-link'), 'alt' => t('Remove this item'), 'title' => t('Remove this item')), 'html' => true));

        $row = array('data' => $row, 'class' => array('draggable'), 'id' => 'views-row-' . $id);
        if ($group_id !== 'ungroupable') {
          $rows[] = $row;
        }
        else {
          $ungroupable_rows[] = $row;
        }
      }
    }
  }
  if (empty($rows)) {
    $rows[] = array(array('data' => t('No fields available.'), 'colspan' => '2'));
  }

  $output = drupal_render($form['override']);
  $output .= '<div class="scroll">';
  if ($grouping) {
    $output .= drupal_render($form['filter_groups']['operator']);
  }
  else {
    $form['filter_groups']['groups'][0]['#title'] = t('Operator');
    $output .= drupal_render($form['filter_groups']['groups'][0]);
  }

  if (!empty($ungroupable_rows)) {
    drupal_add_tabledrag('views-rearrange-filters-ungroupable', 'order', 'sibling', 'weight');
    $header = array(t('Ungroupable filters'), t('Weight'), array('class' => array('views-hide-label'), 'data' => t('Group')), array('class' => array('views-hide-label'), 'data' => t('Remove')));
    $output .= theme('table', array('header' => $header, 'rows' => $ungroupable_rows, 'attributes' => array('id' => 'views-rearrange-filters-ungroupable', 'class' => array('arrange'))));
  }

  // Set up tabledrag so that the weights are changed when rows are dragged.
  drupal_add_tabledrag('views-rearrange-filters', 'order', 'sibling', 'weight');
  $output .= theme('table', array('rows' => $rows, 'attributes' => array('id' => 'views-rearrange-filters', 'class' => array('arrange'))));
  $output .= '</div>';

  // When JavaScript is enabled, the button for adding a new group should be
  // hidden, since it will be replaced by a link on the client side.
  $form['buttons']['add_group']['#attributes']['class'][] = 'js-hide';

  // Render the rest of the form and return.
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Submit handler for rearranging form
 */
function views_ui_rearrange_filter_form_submit($form, &$form_state) {
  $types = views_object_types();
  $display = &$form_state['view']->display[$form_state['display_id']];
  $remember_groups = array();

  if (!empty($form_state['view']->form_cache)) {
    $old_fields = $form_state['view']->form_cache['handlers'];
  }
  else {
    $old_fields = $display->handler->get_option($types[$form_state['type']]['plural']);
  }
  $count = 0;

  $groups = $form_state['values']['filter_groups'];
  // Whatever button was clicked, re-calculate field information.
  $new_fields = $order = array();

  // Make an array with the weights
  foreach ($form_state['values']['filters'] as $field => $info) {
    // add each value that is a field with a weight to our list, but only if
    // it has had its 'removed' checkbox checked.
    if (is_array($info) && empty($info['removed'])) {
      if (isset($info['weight'])) {
        $order[$field] = $info['weight'];
      }

      if (isset($info['group'])) {
        $old_fields[$field]['group'] = $info['group'];
        $remember_groups[$info['group']][] = $field;
      }
    }
  }

  // Sort the array
  asort($order);

  // Create a new list of fields in the new order.
  foreach (array_keys($order) as $field) {
    $new_fields[$field] = $old_fields[$field];
  }

  // If the #group property is set on the clicked button, that means we are
  // either adding or removing a group, not actually updating the filters.
  if (!empty($form_state['clicked_button']['#group'])) {
    if ($form_state['clicked_button']['#group'] == 'add') {
      // Add a new group
      $groups['groups'][] = 'AND';
    }
    else {
      // Renumber groups above the removed one down.
      foreach (array_keys($groups['groups']) as $group_id) {
        if ($group_id >= $form_state['clicked_button']['#group']) {
          $old_group = $group_id + 1;
          if (isset($groups['groups'][$old_group])) {
            $groups['groups'][$group_id] = $groups['groups'][$old_group];
            if (isset($remember_groups[$old_group])) {
              foreach ($remember_groups[$old_group] as $id) {
                $new_fields[$id]['group'] = $group_id;
              }
            }
          }
          else {
            // If this is the last one, just unset it.
            unset($groups['groups'][$group_id]);
          }
        }
      }
    }
    // Update our cache with values so that cancel still works the way
    // people expect.
    $form_state['view']->form_cache = array(
      'key' => 'rearrange-filter',
      'groups' => $groups,
      'handlers' => $new_fields,
    );

    // Return to this form except on actual Update.
    views_ui_add_form_to_stack('rearrange-filter', $form_state['view'], $form_state['display_id'], array($form_state['type']));
  }
  else {
    // The actual update button was clicked. Remove the empty groups, and
    // renumber them sequentially.
    ksort($remember_groups);
    $groups['groups'] = views_array_key_plus(array_values(array_intersect_key($groups['groups'], $remember_groups)));
    // Change the 'group' key on each field to match. Here, $mapping is an
    // array whose keys are the old group numbers and whose values are the new
    // (sequentially numbered) ones.
    $mapping = array_flip(views_array_key_plus(array_keys($remember_groups)));
    foreach ($new_fields as &$new_field) {
      $new_field['group'] = $mapping[$new_field['group']];
    }

    // Write the changed handler values.
    $display->handler->set_option($types[$form_state['type']]['plural'], $new_fields);
    $display->handler->set_option('filter_groups', $groups);
    if (isset($form_state['view']->form_cache)) {
      unset($form_state['view']->form_cache);
    }
  }

  // Store in cache.
  views_ui_cache_set($form_state['view']);
}

/**
 * Form to add_item items in the views UI.
 */
function views_ui_add_item_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];

  $form = array(
    'options' => array(
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('scroll')),
    ),
  );

  ctools_add_js('dependent');

  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = &$view->display[$display_id];

  $types = views_object_types();
  $ltitle = $types[$type]['ltitle'];
  $section = $types[$type]['plural'];

  if (!empty($types[$type]['type'])) {
    $type = $types[$type]['type'];
  }

  $form['#title'] = t('Add @type', array('@type' => $ltitle));
  $form['#section'] = $display_id . 'add-item';


  // Add the display override dropdown.
  views_ui_standard_display_dropdown($form, $form_state, $section);

  // Figure out all the base tables allowed based upon what the relationships provide.
  $base_tables = $view->get_base_tables();
  $options = views_fetch_fields(array_keys($base_tables), $type, $display->handler->use_group_by());

  if (!empty($options)) {
    $form['options']['controls'] = array(
      '#theme_wrappers' => array('container'),
      '#id' => 'views-filterable-options-controls',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['options']['controls']['options_search'] = array(
      '#type' => 'textfield',
      '#title' => t('Search'),
    );

    $groups = array('all' => t('- All -'));
    $form['options']['controls']['group'] = array(
      '#type' => 'select',
      '#title' => t('Filter'),
      '#options' => array(),
      '#attributes' => array('class' => array('ctools-master-dependent')),
    );

    $form['options']['name'] = array(
      '#prefix' => '<div class="views-radio-box form-checkboxes views-filterable-options">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#default_value' => 'all',
    );

    // Group options first to simplify the DOM objects that Views
    // dependent JS will act upon.
    $grouped_options = array();
    foreach ($options as $key => $option) {
      $group = preg_replace('/[^a-z0-9]/', '-', strtolower($option['group']));
      $groups[$group] = $option['group'];
      $grouped_options[$group][$key] = $option;
      if (!empty($option['aliases']) && is_array($option['aliases'])) {
        foreach ($option['aliases'] as $id => $alias) {
          if (empty($alias['base']) || !empty($base_tables[$alias['base']])) {
            $copy = $option;
            $copy['group'] = $alias['group'];
            $copy['title'] = $alias['title'];
            if (isset($alias['help'])) {
              $copy['help'] = $alias['help'];
            }

            $group = preg_replace('/[^a-z0-9]/', '-', strtolower($copy['group']));
            $groups[$group] = $copy['group'];
            $grouped_options[$group][$key . '$' . $id] = $copy;
          }
        }
      }
    }

    foreach ($grouped_options as $group => $group_options) {
      $form['options']['name'][$group . '_start']['#markup'] = '<div class="ctools-dependent-all ctools-dependent-' . $group . '">';
      $zebra = 0;
      foreach ($group_options as $key => $option) {
        $zebra_class = ($zebra % 2) ? 'odd' : 'even';
        $form['options']['name'][$key] = array(
          '#type' => 'checkbox',
          '#title' => t('!group: !field', array('!group' => $option['group'], '!field' => $option['title'])),
          '#description' => $option['help'],
          '#return_value' => $key,
          '#prefix' => "<div class='$zebra_class filterable-option'>",
          '#suffix' => '</div>',
        );
        $zebra++;
      }
      $form['options']['name'][$group . '_end']['#markup'] = '</div>';
    }

    $form['options']['controls']['group']['#options'] = $groups;
  }
  else {
    $form['options']['markup'] = array(
      '#markup' => '<div class="form-item">' . t('There are no @types available to add.', array('@types' =>  $ltitle)) . '</div>',
    );
  }
  // Add a div to show the selected items
  $form['selected'] = array(
    '#type' => 'item',
    '#markup' => '<div class="views-selected-options"></div>',
    '#title' => t('Selected') . ':',
    '#theme_wrappers' => array('form_element', 'views_container'),
    '#attributes' => array('class' => array('container-inline', 'views-add-form-selected')),
  );
  ctools_include('dependent');
  views_ui_standard_form_buttons($form, $form_state, 'views_ui_add_item_form', t('Add and configure @types', array('@types' => $ltitle)));

  // Remove the default submit function.
  $form['buttons']['submit']['#submit'] = array_diff($form['buttons']['submit']['#submit'], array('views_ui_standard_submit'));
  $form['buttons']['submit']['#submit'][] = 'views_ui_add_item_form_submit';

  return $form;
}

/**
 * Submit handler for adding new item(s) to a view.
 */
function views_ui_add_item_form_submit($form, &$form_state) {
  $type = $form_state['type'];
  $types = views_object_types();
  $section = $types[$type]['plural'];

  // Handle the override select.
  list($was_defaulted, $is_defaulted) = views_ui_standard_override_values($form, $form_state);
  if ($was_defaulted && !$is_defaulted) {
    // We were using the default display's values, but we're now overriding
    // the default display and saving values specific to this display.
    $display = &$form_state['view']->display[$form_state['display_id']];
    // set_override toggles the override of this section.
    $display->handler->set_override($section);
  }
  elseif (!$was_defaulted && $is_defaulted) {
    // We used to have an override for this display, but the user now wants
    // to go back to the default display.
    // Overwrite the default display with the current form values, and make
    // the current display use the new default values.
    $display = &$form_state['view']->display[$form_state['display_id']];
    // options_override toggles the override of this section.
    $display->handler->set_override($section);
  }

  if (!empty($form_state['values']['name']) && is_array($form_state['values']['name'])) {
    // Loop through each of the items that were checked and add them to the view.
    foreach (array_keys(array_filter($form_state['values']['name'])) as $field) {
      list($table, $field) = explode('.', $field, 2);

      if ($cut = strpos($field, '$')) {
        $field = substr($field, 0, $cut);
      }
      $id = $form_state['view']->add_item($form_state['display_id'], $type, $table, $field);

      // check to see if we have group by settings
      $key = $type;
      // Footer,header and empty text have a different internal handler type(area).
      if (isset($types[$type]['type'])) {
        $key = $types[$type]['type'];
      }
      $handler = views_get_handler($table, $field, $key);
      if ($form_state['view']->display_handler->use_group_by() && $handler->use_group_by()) {
        views_ui_add_form_to_stack('config-item-group', $form_state['view'], $form_state['display_id'], array($type, $id));
      }

      // check to see if this type has settings, if so add the settings form first
      if ($handler && $handler->has_extra_options()) {
        views_ui_add_form_to_stack('config-item-extra', $form_state['view'], $form_state['display_id'], array($type, $id));
      }
      // Then add the form to the stack
      views_ui_add_form_to_stack('config-item', $form_state['view'], $form_state['display_id'], array($type, $id));
    }
  }

  if (isset($form_state['view']->form_cache)) {
    unset($form_state['view']->form_cache);
  }

  // Store in cache
  views_ui_cache_set($form_state['view']);
}


/**
 * Form to config_item items in the views UI.
 */
function views_ui_config_item_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];
  $id = $form_state['id'];

  $form = array(
    'options' => array(
      '#tree' => TRUE,
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('scroll')),
    ),
  );
  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $item = $view->get_item($display_id, $type, $id);

  if ($item) {
    $handler = $view->display_handler->get_handler($type, $id);
    if (empty($handler)) {
      $form['markup'] = array('#markup' => t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
    }
    else {
      $types = views_object_types();

      // If this item can come from the default display, show a dropdown
      // that lets the user choose which display the changes should apply to.
      if ($view->display_handler->defaultable_sections($types[$type]['plural'])) {
        $form_state['section'] = $types[$type]['plural'];
        views_ui_standard_display_dropdown($form, $form_state, $form_state['section']);
      }

      // A whole bunch of code to figure out what relationships are valid for
      // this item.
      $relationships = $view->display_handler->get_option('relationships');
      $relationship_options = array();

      foreach ($relationships as $relationship) {
        // relationships can't link back to self. But also, due to ordering,
        // relationships can only link to prior relationships.
        if ($type == 'relationship' && $id == $relationship['id']) {
          break;
        }
        $relationship_handler = views_get_handler($relationship['table'], $relationship['field'], 'relationship');
        // ignore invalid/broken relationships.
        if (empty($relationship_handler)) {
          continue;
        }

        // If this relationship is valid for this type, add it to the list.
        $data = views_fetch_data($relationship['table']);
        $base = $data[$relationship['field']]['relationship']['base'];
        $base_fields = views_fetch_fields($base, $form_state['type'], $view->display_handler->use_group_by());
        if (isset($base_fields[$item['table'] . '.' . $item['field']])) {
          $relationship_handler->init($view, $relationship);
          $relationship_options[$relationship['id']] = $relationship_handler->label();
        }
      }

      if (!empty($relationship_options)) {
        // Make sure the existing relationship is even valid. If not, force
        // it to none.
        $base_fields = views_fetch_fields($view->base_table, $form_state['type'], $view->display_handler->use_group_by());
        if (isset($base_fields[$item['table'] . '.' . $item['field']])) {
          $relationship_options = array_merge(array('none' => t('Do not use a relationship')), $relationship_options);
        }
        $rel = empty($item['relationship']) ? 'none' : $item['relationship'];
        if (empty($relationship_options[$rel])) {
          // Pick the first relationship.
          $rel = key($relationship_options);
          // We want this relationship option to get saved even if the user
          // skips submitting the form.
          $view->set_item_option($display_id, $type, $id, 'relationship', $rel);
          $temp_view = $view->clone_view();
          views_ui_cache_set($temp_view);
        }

        $form['options']['relationship'] = array(
          '#type' => 'select',
          '#title' => t('Relationship'),
          '#options' => $relationship_options,
          '#default_value' => $rel,
          '#weight' => -500,
        );
      }
      else {
        $form['options']['relationship'] = array(
          '#type' => 'value',
          '#value' => 'none',
        );
      }

      $form['#title'] = t('Configure @type: @item', array('@type' => $types[$type]['lstitle'], '@item' => $handler->ui_name()));

      if (!empty($handler->definition['help'])) {
        $form['options']['form_description'] = array(
          '#markup' => $handler->definition['help'],
          '#theme_wrappers' => array('container'),
          '#attributes' => array('class' => array('form-item description')),
          '#weight' => -1000,
        );
      }

      $form['#section'] = $display_id . '-' . $type . '-' . $id;

      // Get form from the handler.
      $handler->options_form($form['options'], $form_state);
      $form_state['handler'] = &$handler;
    }

    $name = NULL;
    if (isset($form_state['update_name'])) {
      $name = $form_state['update_name'];
    }

    views_ui_standard_form_buttons($form, $form_state, 'views_ui_config_item_form', $name, t('Remove'), 'remove');
    // Only validate the override values, because this values are required for
    // the override selection.
    $form['buttons']['remove']['#limit_validation_errors'] = array(array('override'));
  }

  return $form;
}

/**
 * Submit handler for configing new item(s) to a view.
 */
function views_ui_config_item_form_validate($form, &$form_state) {
  $form_state['handler']->options_validate($form['options'], $form_state);

  if (form_get_errors()) {
    $form_state['rerender'] = TRUE;
  }
}

/**
 * A submit handler that is used for storing temporary items when using
 * multi-step changes, such as ajax requests.
 */
function views_ui_config_item_form_submit_temporary($form, &$form_state) {
  // Run it through the handler's submit function.
  $form_state['handler']->options_submit($form['options'], $form_state);
  $item = $form_state['handler']->options;
  $types = views_object_types();

  // For footer/header $handler_type is area but $type is footer/header.
  // For all other handle types it's the same.
  $handler_type = $type = $form_state['type'];
  if (!empty($types[$type]['type'])) {
    $handler_type = $types[$type]['type'];
  }

  $override = NULL;
  if ($form_state['view']->display_handler->use_group_by() && !empty($item['group_type'])) {
    if (empty($form_state['view']->query)) {
      $form_state['view']->init_query();
    }
    $aggregate = $form_state['view']->query->get_aggregation_info();
    if (!empty($aggregate[$item['group_type']]['handler'][$type])) {
      $override = $aggregate[$item['group_type']]['handler'][$type];
    }
  }

  // Create a new handler and unpack the options from the form onto it. We
  // can use that for storage.
  $handler = views_get_handler($item['table'], $item['field'], $handler_type, $override);
  $handler->init($form_state['view'], $item);


  // Add the incoming options to existing options because items using
  // the extra form may not have everything in the form here.
  $options = $form_state['values']['options'] + $form_state['handler']->options;

  // This unpacks only options that are in the definition, ensuring random
  // extra stuff on the form is not sent through.
  $handler->unpack_options($handler->options, $options, NULL, FALSE);

  // Store the item back on the view
  $form_state['view']->temporary_options[$type][$form_state['id']] = $handler->options;

  // @todo: Figure out whether views_ui_ajax_form is perhaps the better place to fix the issue.
  // views_ui_ajax_form() drops the current form from the stack, even if it's an #ajax.
  // So add the item back to the top of the stack.
  views_ui_add_form_to_stack($form_state['form_key'], $form_state['view'], $form_state['display_id'], array($type, $item['id']), TRUE);

  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Submit handler for configing new item(s) to a view.
 */
function views_ui_config_item_form_submit($form, &$form_state) {
  // Run it through the handler's submit function.
  $form_state['handler']->options_submit($form['options'], $form_state);
  $item = $form_state['handler']->options;
  $types = views_object_types();

  // For footer/header $handler_type is area but $type is footer/header.
  // For all other handle types it's the same.
  $handler_type = $type = $form_state['type'];
  if (!empty($types[$type]['type'])) {
    $handler_type = $types[$type]['type'];
  }

  $override = NULL;
  if ($form_state['view']->display_handler->use_group_by() && !empty($item['group_type'])) {
    if (empty($form_state['view']->query)) {
      $form_state['view']->init_query();
    }
    $aggregate = $form_state['view']->query->get_aggregation_info();
    if (!empty($aggregate[$item['group_type']]['handler'][$type])) {
      $override = $aggregate[$item['group_type']]['handler'][$type];
    }
  }

  // Create a new handler and unpack the options from the form onto it. We
  // can use that for storage.
  $handler = views_get_handler($item['table'], $item['field'], $handler_type, $override);
  $handler->init($form_state['view'], $item);


  // Add the incoming options to existing options because items using
  // the extra form may not have everything in the form here.
  $options = $form_state['values']['options'] + $form_state['handler']->options;

  // This unpacks only options that are in the definition, ensuring random
  // extra stuff on the form is not sent through.
  $handler->unpack_options($handler->options, $options, NULL, FALSE);

  // Store the item back on the view
  $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], $handler->options);

  // Ensure any temporary options are removed.
  if (isset($form_state['view']->temporary_options[$type][$form_state['id']])) {
    unset($form_state['view']->temporary_options[$type][$form_state['id']]);
  }

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Form to config_item items in the views UI.
 */
function views_ui_config_item_group_form($type, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];
  $id = $form_state['id'];

  $form = array(
    'options' => array(
      '#tree' => TRUE,
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('scroll')),
    ),
  );
  if (!$view->set_display($display_id)) {
    views_ajax_render(t('Invalid display id @display', array('@display' => $display_id)));
  }

  $view->init_query();

  $item = $view->get_item($display_id, $type, $id);

  if ($item) {
    $handler = $view->display_handler->get_handler($type, $id);
    if (empty($handler)) {
      $form['markup'] = array('#markup' => t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
    }
    else {
      $handler->init($view, $item);
      $types = views_object_types();

      $form['#title'] = t('Configure group settings for @type %item', array('@type' => $types[$type]['lstitle'], '%item' => $handler->ui_name()));

      $handler->groupby_form($form['options'], $form_state);
      $form_state['handler'] = &$handler;
    }

    views_ui_standard_form_buttons($form, $form_state, 'views_ui_config_item_group_form');
  }
  return $form;
}

/**
 * Submit handler for configing group settings on a view.
 */
function views_ui_config_item_group_form_submit($form, &$form_state) {
  $item =& $form_state['handler']->options;
  $type = $form_state['type'];
  $id = $form_state['id'];

  $handler = views_get_handler($item['table'], $item['field'], $type);
  $handler->init($form_state['view'], $item);

  $handler->groupby_form_submit($form, $form_state);

  // Store the item back on the view
  $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Submit handler for removing an item from a view
 */
function views_ui_config_item_form_remove($form, &$form_state) {
  // Store the item back on the view
  list($was_defaulted, $is_defaulted) = views_ui_standard_override_values($form, $form_state);
  // If the display selection was changed toggle the override value.
  if ($was_defaulted != $is_defaulted) {
    $display =& $form_state['view']->display[$form_state['display_id']];
    $display->handler->options_override($form, $form_state);
  }
  $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], NULL);

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Override handler for views_ui_edit_display_form
 */
function views_ui_config_item_form_expose($form, &$form_state) {
  $item = &$form_state['handler']->options;
  // flip
  $item['exposed'] = empty($item['exposed']);

  // If necessary, set new defaults:
  if ($item['exposed']) {
    $form_state['handler']->expose_options();
  }

  $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  views_ui_add_form_to_stack($form_state['form_key'], $form_state['view'], $form_state['display_id'], array($form_state['type'], $form_state['id']), TRUE, TRUE);

  views_ui_cache_set($form_state['view']);
  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
  $form_state['force_expose_options'] = TRUE;
}

/**
 * Form to config_item items in the views UI.
 */
function views_ui_config_item_extra_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];
  $id = $form_state['id'];

  $form = array(
    'options' => array(
      '#tree' => TRUE,
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('scroll')),
    ),
  );
  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $item = $view->get_item($display_id, $type, $id);

  if ($item) {
    $handler = $view->display_handler->get_handler($type, $id);
    if (empty($handler)) {
      $form['markup'] = array('#markup' => t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
    }
    else {
      $handler->init($view, $item);
      $types = views_object_types();

      $form['#title'] = t('Configure extra settings for @type %item', array('@type' => $types[$type]['lstitle'], '%item' => $handler->ui_name()));

      $form['#section'] = $display_id . '-' . $type . '-' . $id;

      // Get form from the handler.
      $handler->extra_options_form($form['options'], $form_state);
      $form_state['handler'] = &$handler;
    }

    views_ui_standard_form_buttons($form, $form_state, 'views_ui_config_item_extra_form');
  }
  return $form;
}

/**
 * Validation handler for configing new item(s) to a view.
 */
function views_ui_config_item_extra_form_validate($form, &$form_state) {
  $form_state['handler']->extra_options_validate($form['options'], $form_state);
}

/**
 * Submit handler for configing new item(s) to a view.
 */
function views_ui_config_item_extra_form_submit($form, &$form_state) {
  // Run it through the handler's submit function.
  $form_state['handler']->extra_options_submit($form['options'], $form_state);
  $item = $form_state['handler']->options;

  // Store the data we're given.
  foreach ($form_state['values']['options'] as $key => $value) {
    $item[$key] = $value;
  }

  // Store the item back on the view
  $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Form to config_style items in the views UI.
 */
function views_ui_config_style_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];
  $id = $form_state['id'];

  $form = array(
    'options' => array(
      '#tree' => TRUE,
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('scroll')),
    ),
  );
  if (!$view->set_display($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $item = $view->get_item($display_id, $type, $id);

  if ($item) {
    $handler = views_get_handler($item['table'], $item['field'], $type);
    if (empty($handler)) {
      $form['markup'] = array('#markup' => t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
    }
    else {
      $handler->init($view, $item);
      $types = views_object_types();

      $form['#title'] = t('Configure summary style for @type %item', array('@type' => $types[$type]['lstitle'], '%item' => $handler->ui_name()));

      $form['#section'] = $display_id . '-' . $type . '-style-options';

      $plugin = views_get_plugin('style', $handler->options['style_plugin']);
      if ($plugin) {
        $form['style_options'] = array(
          '#tree' => TRUE,
        );
        $plugin->init($view, $view->display[$display_id], $handler->options['style_options']);

        $plugin->options_form($form['style_options'], $form_state);
      }

      $form_state['handler'] = &$handler;
    }

    views_ui_standard_form_buttons($form, $form_state, 'views_ui_config_style_form');
  }
  return $form;
}

/**
 * Submit handler for configing new item(s) to a view.
 */
function views_ui_config_style_form_submit($form, &$form_state) {
  // Run it through the handler's submit function.
  $form_state['handler']->options_submit($form['style_options'], $form_state);
  $item = $form_state['handler']->options;

  // Store the data we're given.
  $item['style_options'] = $form_state['values']['style_options'];

  // Store the item back on the view
  $form_state['view']->set_item($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Get a list of roles in the system.
 */
function views_ui_get_roles() {
  static $roles = NULL;
  if (!isset($roles)) {
    $roles = array();
    $result = db_query("SELECT r.rid, r.name FROM {role} r ORDER BY r.name");
    foreach ($result as $obj) {
      $roles[$obj->rid] = $obj->name;
    }
  }

  return $roles;
}

/**
 * Form builder for the admin display defaults page.
 */
function views_ui_admin_settings_basic() {
  $form = array();
  $form['#attached']['css'] = views_ui_get_admin_css();

  $options = array();
  foreach (list_themes() as $name => $theme) {
    if ($theme->status) {
      $options[$name] = $theme->info['name'];
    }
  }

  // This is not currently a fieldset but we may want it to be later,
  // so this will make it easier to change if we do.
  $form['basic'] = array();

  $form['basic']['views_ui_show_listing_filters'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show filters on the list of views'),
    '#default_value' => variable_get('views_ui_show_listing_filters', FALSE),
  );
  $form['basic']['views_ui_show_advanced_help_warning'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show advanced help warning'),
    '#default_value' => variable_get('views_ui_show_advanced_help_warning', TRUE),
  );

  $form['basic']['views_ui_show_master_display'] = array(
    '#type' => 'checkbox',
    '#title' => t('Always show the master display'),
    '#description' => t('Advanced users of views may choose to see the master (i.e. default) display.'),
    '#default_value' => variable_get('views_ui_show_master_display', FALSE),
  );

  $form['basic']['views_ui_show_advanced_column'] = array(
    '#type' => 'checkbox',
    '#title' => t('Always show advanced display settings'),
    '#description' => t('Default to showing advanced display settings, such as relationships and contextual filters.'),
    '#default_value' => variable_get('views_ui_show_advanced_column', FALSE),
  );

  $form['basic']['views_ui_display_embed'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show the embed display in the ui.'),
    '#description' => t('Allow advanced user to use the embed view display. The plugin itself works if it\'s not visible in the ui'),
    '#default_value' => variable_get('views_ui_display_embed', FALSE),
  );

  $form['basic']['views_ui_custom_theme'] = array(
    '#type' => 'select',
    '#title' => t('Custom admin theme for the Views UI'),
    '#options' => array('_default' => t('- Use default -')) + $options,
    '#default_value' => variable_get('views_ui_custom_theme', '_default'),
    '#description' => t('In some cases you might want to select a different admin theme for the Views UI.')
  );

  $form['basic']['views_exposed_filter_any_label'] = array(
    '#type' => 'select',
    '#title' => t('Label for "Any" value on non-required single-select exposed filters'),
    '#options' => array('old_any' => '<Any>', 'new_any' => t('- Any -')),
    '#default_value' => variable_get('views_exposed_filter_any_label', 'new_any'),
  );

  $form['live_preview'] = array(
    '#type' => 'fieldset',
    '#title' => t('Live preview settings'),
  );

  $form['live_preview']['views_ui_always_live_preview'] = array(
    '#type' => 'checkbox',
    '#title' => t('Automatically update preview on changes'),
    '#default_value' => variable_get('views_ui_always_live_preview', TRUE),
  );

//  $form['live_preview']['views_ui_always_live_preview_button'] = array(
//    '#type' => 'checkbox',
//    '#title' => t('Always show the preview button, even when the automatically update option is checked'),
//    '#default_value' => variable_get('views_ui_always_live_preview_button', FALSE),
//  );

  $form['live_preview']['views_ui_show_preview_information'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show information and statistics about the view during live preview'),
    '#default_value' => variable_get('views_ui_show_preview_information', TRUE),
  );

  $form['live_preview']['views_ui_show_sql_query_where'] = array(
    '#type' => 'radios',
    '#options' => array(
      'above' => t('Above the preview'),
      'below' => t('Below the preview'),
    ),
    '#id' => 'edit-show-sql',
    '#default_value' => variable_get('views_ui_show_sql_query_where', 'above'),
    '#dependency' => array('edit-views-ui-show-preview-information' => array(TRUE)),
    '#prefix' => '<div id="edit-show-sql-wrapper" class="views-dependent">',
    '#suffix' => '</div>',
  );

  $form['live_preview']['views_ui_show_sql_query'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show the SQL query'),
    '#default_value' => variable_get('views_ui_show_sql_query', FALSE),
    '#dependency' => array('edit-views-ui-show-preview-information' => array(TRUE)),
  );
  $form['live_preview']['views_ui_show_performance_statistics'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show performance statistics'),
    '#default_value' => variable_get('views_ui_show_performance_statistics', FALSE),
    '#dependency' => array('edit-views-ui-show-preview-information' => array(TRUE)),
  );

  $form['live_preview']['views_show_additional_queries'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show other queries run during render during live preview'),
    '#description' => t("Drupal has the potential to run many queries while a view is being rendered. Checking this box will display every query run during view render as part of the live preview."),
    '#default_value' => variable_get('views_show_additional_queries', FALSE),
    '#dependency' => array('edit-views-ui-show-preview-information' => array(TRUE)),
  );

//  $form['live_preview']['views_ui_show_performance_statistics_where'] = array(

  return system_settings_form($form);
}

/**
 * Form builder for the advanced admin settings page.
 */
function views_ui_admin_settings_advanced() {
  $form = array();
  $form['#attached']['css'] = views_ui_get_admin_css();

  $form['cache'] = array(
    '#type' => 'fieldset',
    '#title' => t('Caching'),
  );

  $form['cache']['views_skip_cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Disable views data caching'),
    '#description' => t("Views caches data about tables, modules and views available, to increase performance. By checking this box, Views will skip this cache and always rebuild this data when needed. This can have a serious performance impact on your site."),
    '#default_value' => variable_get('views_skip_cache', FALSE),
  );

  $form['cache']['clear_cache'] = array(
    '#type' => 'submit',
    '#value' => t("Clear Views' cache"),
    '#submit' => array('views_ui_tools_clear_cache'),
  );

  $form['debug'] = array(
    '#type' => 'fieldset',
    '#title' => t('Debugging'),
  );

  $form['debug']['views_sql_signature'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add Views signature to all SQL queries'),
    '#description' => t("All Views-generated queries will include the name of the views and display 'view-name:display-name' as a string  at the end of the SELECT clause. This makes identifying Views queries in database server logs simpler, but should only be used when troubleshooting."),

    '#default_value' => variable_get('views_sql_signature', FALSE),
  );

  $form['debug']['views_no_javascript'] = array(
    '#type' => 'checkbox',
    '#title' => t('Disable JavaScript with Views'),
    '#description' => t("If you are having problems with the JavaScript, you can disable it here. The Views UI should degrade and still be usable without javascript; it's just not as good."),
    '#default_value' => variable_get('views_no_javascript', FALSE),
  );

  $form['debug']['views_devel_output'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable views performance statistics/debug messages via the Devel module'),
    '#description' => t("Check this to enable some Views query and performance statistics/debug messages <em>if Devel is installed</em>."),
    '#default_value' => variable_get('views_devel_output', FALSE),
  );

  $form['locale'] = array(
    '#type' => 'fieldset',
    '#title' => t('Localization'),
  );

  $form['locale']['views_localization_plugin'] =  array(
    '#type' => 'radios',
    '#title' => t('Translation method'),
    '#options' => views_fetch_plugin_names('localization', NULL, array()),
    '#default_value' => views_get_localization_plugin(),
    '#description' => t('Select a translation method to use for Views data like header, footer, and empty text.'),
  );

  $regions = array();
  $regions['watchdog'] = t('Watchdog');
  if (module_exists('devel')) {
    $regions['message'] = t('Devel message(dpm)');
    $regions['drupal_debug'] = t('Devel logging (tmp://drupal_debug.txt)');
  }

  $form['debug']['views_devel_region'] = array(
    '#type' => 'select',
    '#title' => t('Page region to output performance statistics/debug messages'),
    '#default_value' => variable_get('views_devel_region', 'footer'),
    '#options' => $regions,
    '#dependency' => array('edit-views-devel-output' => array(1)),
  );

  $options = views_fetch_plugin_names('display_extender');
  if (!empty($options)) {
    $form['extenders'] = array(
      '#type' => 'fieldset',
    );
    ;
    $form['extenders']['views_display_extenders'] = array(
      '#title' => t('Display extenders'),
      '#default_value' => views_get_enabled_display_extenders(),
      '#options' => $options,
      '#type' => 'checkboxes',
      '#description' => t('Select extensions of the views interface.')
    );
  }

  return system_settings_form($form);
}

/**
 * Submit hook to clear the views cache.
 */
function views_ui_tools_clear_cache() {
  views_invalidate_cache();
  drupal_set_message(t('The cache has been cleared.'));
}

/**
 * Submit hook to clear Drupal's theme registry (thereby triggering
 * a templates rescan).
 */
function views_ui_config_item_form_rescan($form, &$form_state) {
  drupal_theme_rebuild();

  // The 'Theme: Information' page is about to be shown again. That page
  // analyzes the output of theme_get_registry(). However, this latter
  // function uses an internal cache (which was initialized before we
  // called drupal_theme_rebuild()) so it won't reflect the
  // current state of our theme registry. The only way to clear that cache
  // is to re-initialize the theme system:
  unset($GLOBALS['theme']);
  drupal_theme_initialize();

  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
}

/**
 * Override handler for views_ui_edit_display_form
 */
function views_ui_edit_display_form_change_theme($form, &$form_state) {
  // This is just a temporary variable.
  $form_state['view']->theme = $form_state['values']['theme'];

  views_ui_cache_set($form_state['view']);
  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
}

/**
 * Page callback for views tag autocomplete
 */
function views_ui_autocomplete_tag($string = '') {
  $matches = array();
  // get matches from default views:
  views_include('view');
  $views = views_get_all_views();
  foreach ($views as $view) {
    if (!empty($view->tag) && strpos($view->tag, $string) === 0) {
      $matches[$view->tag] = $view->tag;
      if (count($matches) >= 10) {
        break;
      }
    }
  }

  drupal_json_output($matches);
}

// ------------------------------------------------------------------
// Get information from the Views data

function _views_weight_sort($a, $b) {
  if ($a['weight'] != $b['weight']) {
    return $a['weight'] < $b['weight'] ? -1 : 1;
  }
  if ($a['title'] != $b['title']) {
    return $a['title'] < $b['title'] ? -1 : 1;
  }

  return 0;
}

/**
 * Fetch a list of all base tables available
 *
 * @return
 *   A keyed array of in the form of 'base_table' => 'Description'.
 */
function views_fetch_base_tables() {
  static $base_tables = array();
  if (empty($base_tables)) {
    $weights = array();
    $tables = array();
    $data = views_fetch_data();
    foreach ($data as $table => $info) {
      if (!empty($info['table']['base'])) {
        $tables[$table] = array(
          'title' => $info['table']['base']['title'],
          'description' => !empty($info['table']['base']['help']) ? $info['table']['base']['help'] : '',
          'weight' => !empty($info['table']['base']['weight']) ? $info['table']['base']['weight'] : 0,
        );
      }
    }
    uasort($tables, '_views_weight_sort');
    $base_tables = $tables;
  }

  return $base_tables;
}

function _views_sort_types($a, $b) {
  $a_group = drupal_strtolower($a['group']);
  $b_group = drupal_strtolower($b['group']);
  if ($a_group != $b_group) {
    return $a_group < $b_group ? -1 : 1;
  }

  $a_title = drupal_strtolower($a['title']);
  $b_title = drupal_strtolower($b['title']);
  if ($a_title != $b_title) {
    return $a_title < $b_title ? -1 : 1;
  }

  return 0;
}

/**
 * Fetch a list of all fields available for a given base type.
 *
 * @param (array|string) $base
 *   A list or a single base_table, for example node.
 * @param string $type
 *   The handler type, for example field or filter.
 * @param bool $grouping
 *   Should the result grouping by its 'group' label.
 *
 * @return array
 *   A keyed array of in the form of 'base_table' => 'Description'.
 */
function views_fetch_fields($base, $type, $grouping = FALSE) {
  static $fields = array();
  if (empty($fields)) {
    $data = views_fetch_data();
    $start = microtime(TRUE);
    // This constructs this ginormous multi dimensional array to
    // collect the important data about fields. In the end,
    // the structure looks a bit like this (using nid as an example)
    // $strings['nid']['filter']['title'] = 'string'.
    //
    // This is constructed this way because the above referenced strings
    // can appear in different places in the actual data structure so that
    // the data doesn't have to be repeated a lot. This essentially lets
    // each field have a cheap kind of inheritance.

    foreach ($data as $table => $table_data) {
      $bases = array();
      $strings = array();
      $skip_bases = array();
      foreach ($table_data as $field => $info) {
        // Collect table data from this table
        if ($field == 'table') {
          // calculate what tables this table can join to.
          if (!empty($info['join'])) {
            $bases = array_keys($info['join']);
          }
          // And it obviously joins to itself.
          $bases[] = $table;
          continue;
        }
        foreach (array('field', 'sort', 'filter', 'argument', 'relationship', 'area') as $key) {
          if (!empty($info[$key])) {
            if ($grouping && !empty($info[$key]['no group by'])) {
              continue;
            }
            if (!empty($info[$key]['skip base'])) {
              foreach ((array) $info[$key]['skip base'] as $base_name) {
                $skip_bases[$field][$key][$base_name] = TRUE;
              }
            }
            elseif (!empty($info['skip base'])) {
              foreach ((array) $info['skip base'] as $base_name) {
                $skip_bases[$field][$key][$base_name] = TRUE;
              }
            }
            // Don't show old fields. The real field will be added right.
            if (isset($info[$key]['moved to'])) {
              continue;
            }
            foreach (array('title', 'group', 'help', 'base', 'aliases') as $string) {
              // First, try the lowest possible level
              if (!empty($info[$key][$string])) {
                $strings[$field][$key][$string] = $info[$key][$string];
              }
              // Then try the field level
              elseif (!empty($info[$string])) {
                $strings[$field][$key][$string] = $info[$string];
              }
              // Finally, try the table level
              elseif (!empty($table_data['table'][$string])) {
                $strings[$field][$key][$string] = $table_data['table'][$string];
              }
              else {
                if ($string != 'base' && $string != 'base') {
                  $strings[$field][$key][$string] = t("Error: missing @component", array('@component' => $string));
                }
              }
            }
          }
        }
      }
      foreach ($bases as $base_name) {
        foreach ($strings as $field => $field_strings) {
          foreach ($field_strings as $type_name => $type_strings) {
            if (empty($skip_bases[$field][$type_name][$base_name])) {
              $fields[$base_name][$type_name]["$table.$field"] = $type_strings;
            }
          }
        }
      }
    }
//    vsm('Views UI data build time: ' . (views_microtime() - $start) * 1000 . ' ms');
  }

  // If we have an array of base tables available, go through them
  // all and add them together. Duplicate keys will be lost and that's
  // Just Fine.
  if (is_array($base)) {
    $strings = array();
    foreach ($base as $base_table) {
      if (isset($fields[$base_table][$type])) {
        $strings += $fields[$base_table][$type];
      }
    }
    uasort($strings, '_views_sort_types');
    return $strings;
  }

  if (isset($fields[$base][$type])) {
    uasort($fields[$base][$type], '_views_sort_types');
    return $fields[$base][$type];
  }
  return array();
}


/**
 * Theme the form for the table style plugin
 */
function theme_views_ui_style_plugin_table($variables) {
  $form = $variables['form'];

  $output = drupal_render($form['description_markup']);

  $header = array(
    t('Field'),
    t('Column'),
    t('Align'),
    t('Separator'),
    array(
      'data' => t('Sortable'),
      'align' => 'center',
    ),
    array(
      'data' => t('Default order'),
      'align' => 'center',
    ),
    array(
      'data' => t('Default sort'),
      'align' => 'center',
    ),
    array(
      'data' => t('Hide empty column'),
      'align' => 'center',
    ),
  );
  $rows = array();
  foreach (element_children($form['columns']) as $id) {
    $row = array();
    $row[] = drupal_render($form['info'][$id]['name']);
    $row[] = drupal_render($form['columns'][$id]);
    $row[] = drupal_render($form['info'][$id]['align']);
    $row[] = drupal_render($form['info'][$id]['separator']);
    if (!empty($form['info'][$id]['sortable'])) {
      $row[] = array(
        'data' => drupal_render($form['info'][$id]['sortable']),
        'align' => 'center',
      );
      $row[] = array(
        'data' => drupal_render($form['info'][$id]['default_sort_order']),
        'align' => 'center',
      );
      $row[] = array(
        'data' => drupal_render($form['default'][$id]),
        'align' => 'center',
      );
    }
    else {
      $row[] = '';
      $row[] = '';
      $row[] = '';
    }
    $row[] = array(
      'data' => drupal_render($form['info'][$id]['empty_column']),
      'align' => 'center',
    );
    $rows[] = $row;
  }

  // Add the special 'None' row.
  $rows[] = array(t('None'), '', '', '', '', '', array('align' => 'center', 'data' => drupal_render($form['default'][-1])), '');

  $output .= theme('table', array('header' => $header, 'rows' => $rows));
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Placeholder function for overriding $display->display_title.
 *
 * @todo Remove this function once editing the display title is possible.
 */
function views_ui_get_display_label($view, $display_id, $check_changed = TRUE) {
  $title = $display_id == 'default' ? t('Master') : $view->display[$display_id]->display_title;
  $title = views_ui_truncate($title, 25);

  if ($check_changed && !empty($view->changed_display[$display_id])) {
    $changed = '*';
    $title = $title . $changed;
  }

  return $title;
}

function views_ui_add_template_page() {
  $templates = views_get_all_templates();

  if (empty($templates)) {
    return t('There are no templates available.');
  }

  $header = array(
    t('Name'),
    t('Description'),
    t('Operation'),
  );

  $rows = array();
  foreach ($templates as $name => $template) {
    $rows[] = array(
      array('data' => check_plain($template->get_human_name())),
      array('data' => check_plain($template->description)),
      array('data' => l('add', 'admin/structure/views/template/' . $template->name . '/add')),
    );
  }

  $output = theme('table', array('header' => $header, 'rows' => $rows));
  return $output;
}

/**
 * #process callback for a button; determines if a button is the form's triggering element.
 *
 * The Form API has logic to determine the form's triggering element based on
 * the data in $_POST. However, it only checks buttons based on a single #value
 * per button. This function may be added to a button's #process callbacks to
 * extend button click detection to support multiple #values per button. If the
 * data in $_POST matches any value in the button's #values array, then the
 * button is detected as having been clicked. This can be used when the value
 * (label) of the same logical button may be different based on context (e.g.,
 * "Apply" vs. "Apply and continue").
 *
 * @see _form_builder_handle_input_element()
 * @see _form_button_was_clicked()
 */
function views_ui_form_button_was_clicked($element, &$form_state) {
  $process_input = empty($element['#disabled']) && ($form_state['programmed'] || ($form_state['process_input'] && (!isset($element['#access']) || $element['#access'])));
  if ($process_input && !isset($form_state['triggering_element']) && isset($element['#button_type']) && isset($form_state['input'][$element['#name']]) && isset($element['#values']) && in_array($form_state['input'][$element['#name']], $element['#values'], TRUE)) {
    $form_state['triggering_element'] = $element;
  }
  return $element;
}

/**
 * #process callback for a button; makes implicit form submissions trigger as this button.
 *
 * @see Drupal.behaviors.viewsImplicitFormSubmission
 */
function views_ui_default_button($element, &$form_state, $form) {
  $setting['viewsImplicitFormSubmission'][$form['#id']]['defaultButton'] = $element['#id'];
  $element['#attached']['js'][] = array('type' => 'setting', 'data' => $setting);
  return $element;
}

/**
 * List all instances of fields on any views.
 *
 * Therefore it builds up a table of each field which is used in any view.
 *
 * @see field_ui_fields_list()
 */
function views_ui_field_list() {
  $views = views_get_all_views();

  // Fetch all fieldapi fields which are used in views
  // Therefore search in all views, displays and handler-types.
  $fields = array();
  foreach ($views as $view) {
    foreach ($view->display as $display_id => $display) {
      if ($view->set_display($display_id)) {
        foreach (views_object_types() as $type => $info) {
          foreach ($view->get_items($type, $display_id) as $item) {
            $data = views_fetch_data($item['table']);
            if (isset($data[$item['field']]) && isset($data[$item['field']][$type])
              && $data = $data[$item['field']][$type]) {
              // The final check that we have a fieldapi field now.
              if (isset($data['field_name'])) {
                $fields[$data['field_name']][$view->name] = $view->name;
              }
            }
          }
        }
      }
    }
  }

  $header = array(t('Field name'), t('Used in'));
  $rows = array();
  foreach ($fields as $field_name => $views) {

    $rows[$field_name]['data'][0] = check_plain($field_name);
    foreach ($views as $view) {
      $rows[$field_name]['data'][1][] = l($view, "admin/structure/views/view/$view");
    }
    $rows[$field_name]['data'][1] = implode(', ', $rows[$field_name]['data'][1]);
  }

  // Sort rows by field name.
  ksort($rows);
  $output = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No fields have been used in views yet.'),
  );

  return $output;
}
