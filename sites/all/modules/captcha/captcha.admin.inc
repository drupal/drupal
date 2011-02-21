<?php
// $Id: captcha.admin.inc,v 1.40 2010/12/30 00:29:15 soxofaan Exp $

/**
 * @file
 * Functionality and helper functions for CAPTCHA administration.
 */

/**
 * Return an array with the available CAPTCHA types, for use as options array
 * for a select form elements.
 *
 * @param $add_special_options if true: also add a 'none' and 'default' option
 *
 * @return an associative array mapping "$module/$type" to
 *   "$type (from module $module)" with $module the module name implementing the CAPTCHA
 *   and $type the name of the CAPTCHA type.
 */
function _captcha_available_challenge_types($add_special_options=TRUE) {
  $captcha_types = array();
  if ($add_special_options) {
    $captcha_types['none'] = '[' . t('none') . ']';
    $captcha_types['default'] = '[' . t('default challenge type') . ']';
  }
  // We do our own version of Drupal's module_invoke_all() here because
  // we want to build an array with custom keys and values.
  foreach (module_implements('captcha') as $module) {
    $result = call_user_func_array($module . '_captcha', array('list'));
    if (is_array($result)) {
      foreach ($result as $type) {
        $captcha_types["$module/$type"] = t('@type (from module @module)', array('@type' => $type, '@module' => $module));
      }
    }
  }
  return $captcha_types;
}

/**
 * Form builder function for the general CAPTCHA configuration
 */
function captcha_admin_settings() {
  module_load_include('inc', 'captcha');

  // Use javascript for some added usability on admin form.
  drupal_add_js(drupal_get_path('module', 'captcha') . '/captcha.js');

  // Configuration of which forms to protect, with what challenge.
  $form['captcha_form_protection'] = array(
    '#type' => 'fieldset',
    '#title' => t('Form protection'),
    '#description' => t('Select the challenge type you want for each of the listed forms (identified by their so called <em>form_id</em>\'s). You can easily add arbitrary forms with textfield at the bottom of the table or with the help of the \'%CAPTCHA_admin_links\' option below.',
      array('%CAPTCHA_admin_links' => t('Add CAPTCHA administration links to forms'))
      ),
  );
  $form['captcha_form_protection']['captcha_default_challenge'] = array(
    '#type' => 'select',
    '#title' => t('Default challenge type'),
    '#description' => t('Select the default challenge type for CAPTCHAs. This can be overriden for each form if desired.'),
    '#options' => _captcha_available_challenge_types(FALSE),
    '#default_value' => variable_get('captcha_default_challenge', 'captcha/Math'),
  );
  // List known form_ids.
  $form['captcha_form_protection']['captcha_form_id_overview'] = array(
    '#theme' => 'captcha_admin_settings_captcha_points',
    '#tree' => TRUE,
  );
  $form['captcha_form_protection']['captcha_form_id_overview']['captcha_captcha_points'] = array();
  $captcha_type_options = _captcha_available_challenge_types();
  $result = db_select('captcha_points', 'cp')->fields('cp')->orderBy('form_id')->execute();
  foreach ($result as $captcha_point) {
    $form['captcha_form_protection']['captcha_form_id_overview']['captcha_captcha_points'][$captcha_point->form_id] = array();
    $form['captcha_form_protection']['captcha_form_id_overview']['captcha_captcha_points'][$captcha_point->form_id]['form_id'] = array(
      '#markup' => $captcha_point->form_id,
    );
    // Select widget for CAPTCHA type.
    if (isset($captcha_point->module) && $captcha_point->module) {
      $captcha_type = $captcha_point->module . '/' . $captcha_point->captcha_type;
    }
    elseif ($captcha_point->captcha_type == 'default') {
      $captcha_type = 'default';
    }
    else {
      $captcha_type = 'none';
    }
    $form['captcha_form_protection']['captcha_form_id_overview']['captcha_captcha_points'][$captcha_point->form_id]['captcha_type'] = array(
      '#type' => 'select',
      '#default_value' => $captcha_type,
      '#options' => $captcha_type_options,
    );
    // Additional operations.
    $form['captcha_form_protection']['captcha_form_id_overview']['captcha_captcha_points'][$captcha_point->form_id]['operations'] = array(
      '#markup' => implode(", ", array(
        l(t('delete'), "admin/config/people/captcha/captcha/captcha_point/{$captcha_point->form_id}/delete"),
      ))
    );
  }

  // Form items for new form_id.
  $form['captcha_form_protection']['captcha_form_id_overview']['captcha_new_captcha_point'] = array();
  // Textfield for form_id.
  $form['captcha_form_protection']['captcha_form_id_overview']['captcha_new_captcha_point']['form_id'] = array(
    '#type' => 'textfield',
    '#size' => 16,
  );
  // Select widget for CAPTCHA type.
  $form['captcha_form_protection']['captcha_form_id_overview']['captcha_new_captcha_point']['captcha_type'] = array(
    '#type' => 'select',
    '#default_value' => 'none',
    '#options' => $captcha_type_options,
  );

  // Field for the CAPTCHA administration mode.
  $form['captcha_form_protection']['captcha_administration_mode'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add CAPTCHA administration links to forms'),
    '#default_value' => variable_get('captcha_administration_mode', FALSE),
    '#description' => t('This option makes it easy to manage CAPTCHA settings on forms. When enabled, users with the "%admincaptcha" permission will see a fieldset with CAPTCHA administration links on all forms, except on administrative pages.', array('%admincaptcha' => t('administer CAPTCHA settings'))),
  );
  // Field for the CAPTCHAs on admin pages.
  $form['captcha_form_protection']['captcha_allow_on_admin_pages'] = array(
    '#type' => 'checkbox',
    '#title' => t('Allow CAPTCHAs and CAPTCHA administration links on administrative pages'),
    '#default_value' => variable_get('captcha_allow_on_admin_pages', FALSE),
    '#description' => t('This option makes it possible to add CAPTCHAs to forms on administrative pages. CAPTCHAs are disabled by default on administrative pages (which shouldn\'t be accessible to untrusted users normally) to avoid the related overhead. In some situations, e.g. in the case of demo sites, it can be usefull to allow CAPTCHAs on administrative pages.'),
  );

  // Configuration option for adding a CAPTCHA description.
  $form['captcha_add_captcha_description'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add a description to the CAPTCHA'),
    '#description' => t('Add a configurable description to explain the purpose of the CAPTCHA to the visitor.'),
    '#default_value' => variable_get('captcha_add_captcha_description', TRUE),
  );
  // Textfield(s) for the CAPTCHA description.
  if (module_exists('locale')) {
    $langs = locale_language_list();
    $form['captcha_descriptions'] = array(
      '#type' => 'fieldset',
      '#title' => t('CAPTCHA description'),
      '#description' => t('Configurable description of the CAPTCHA. An empty entry will reset the description.'),
      '#attributes' => array('id' => 'edit-captcha-description-wrapper'),
    );
    foreach ($langs as $lang_code => $lang_name) {
      $form['captcha_descriptions']["captcha_description_$lang_code"] = array(
        '#type' => 'textfield',
        '#title' => t('For language %lang_name (code %lang_code)', array('%lang_name' => $lang_name, '%lang_code' => $lang_code)),
        '#default_value' => _captcha_get_description($lang_code),
        '#maxlength' => 256,
      );
    }
  }
  else {
    $form['captcha_description'] = array(
      '#type' => 'textfield',
      '#title' => t('Challenge description'),
      '#description' => t('Configurable description of the CAPTCHA. An empty entry will reset the description.'),
      '#default_value' => _captcha_get_description(),
      '#maxlength' => 256,
      '#attributes' => array('id' => 'edit-captcha-description-wrapper'),
    );
  }

  // Option for case sensitive/insensitive validation of the responses.
  $form['captcha_default_validation'] = array(
    '#type' => 'radios',
    '#title' => t('Default CAPTCHA validation'),
    '#description' => t('Define how the response should be processed by default. Note that the modules that provide the actual challenges can override or ignore this.'),
    '#options' => array(
      CAPTCHA_DEFAULT_VALIDATION_CASE_SENSITIVE => t('Case sensitive validation: the response has to exactly match the solution.'),
      CAPTCHA_DEFAULT_VALIDATION_CASE_INSENSITIVE => t('Case insensitive validation: lowercase/uppercase errors are ignored.'),
    ),
    '#default_value' => variable_get('captcha_default_validation', CAPTCHA_DEFAULT_VALIDATION_CASE_INSENSITIVE),
  );

  // Field for CAPTCHA persistence.
  // TODO for D7: Rethink/simplify the explanation and UI strings.
  $form['captcha_persistence'] = array(
    '#type' => 'radios',
    '#title' => t('Persistence'),
    '#default_value' => variable_get('captcha_persistence', CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_INSTANCE),
    '#options' => array(
      CAPTCHA_PERSISTENCE_SHOW_ALWAYS =>
        t('Always add a challenge.'),
      CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_INSTANCE =>
        t('Omit challenges in a multi-step/preview workflow once the user successfully responds to a challenge.'),
      CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_TYPE =>
        t('Omit challenges on a form type once the user successfully responds to a challenge on a form of that type.'),
      CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL =>
        t('Omit challenges on all forms once the user successfully responds to any challenge on the site.'),
    ),
    '#description' => t('Define if challenges should be omitted during the rest of a session once the user successfully responds to a challenge.'),
  );

  // Option for logging wrong responses.
  $form['captcha_log_wrong_responses'] = array(
    '#type' => 'checkbox',
    '#title' => t('Log wrong responses'),
    '#description' => t('Report information about wrong responses to the <a href="!dblog">log</a>.', array('!dblog' => url('admin/reports/dblog'))),
    '#default_value' => variable_get('captcha_log_wrong_responses', FALSE),
  );

  // Submit button.
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );

  return $form;
}

/**
 * Custom theme function for a table of (form_id -> CAPTCHA type) settings
 */
function theme_captcha_admin_settings_captcha_points($variables) {
  $form = $variables['form'];
  $header = array('form_id', t('Challenge type'), t('Operations'));
  $rows = array();
  // Existing CAPTCHA points.
  foreach (element_children($form['captcha_captcha_points']) as $key) {
    $row = array();
    $row[] = drupal_render($form['captcha_captcha_points'][$key]['form_id']);
    $row[] = drupal_render($form['captcha_captcha_points'][$key]['captcha_type']);
    $row[] = drupal_render($form['captcha_captcha_points'][$key]['operations']);
    $rows[] = $row;
  }
  // For new CAPTCHA point.
  $row = array();
  $row[] = drupal_render($form['captcha_new_captcha_point']['form_id']);
  $row[] = drupal_render($form['captcha_new_captcha_point']['captcha_type']);
  $row[] = '';
  $rows[] = $row;

  $output = theme('table', array('header' => $header, 'rows' => $rows));
  return $output;
}

/**
 * Validation handler for captcha_admin_settings form.
 */
function captcha_admin_settings_validate($form, $form_state) {
  $form_id = $form_state['values']['captcha_form_id_overview']['captcha_new_captcha_point']['form_id'];
  if (!preg_match('/^[a-z0-9_]*$/', $form_id)) {
    form_set_error('captcha_form_id_overview][captcha_new_captcha_point][form_id', t('Illegal form_id'));
  }
}

/**
 * Submission function for captcha_admin_settings form.
 */
function captcha_admin_settings_submit($form, &$form_state) {

  variable_set('captcha_administration_mode', $form_state['values']['captcha_administration_mode']);
  variable_set('captcha_allow_on_admin_pages', $form_state['values']['captcha_allow_on_admin_pages']);

  variable_set('captcha_default_challenge', $form_state['values']['captcha_default_challenge']);

  // Process CAPTCHA points
  if (isset($form_state['values']['captcha_form_id_overview']['captcha_captcha_points'])) {
    foreach ($form_state['values']['captcha_form_id_overview']['captcha_captcha_points'] as $captcha_point_form_id => $data) {
      captcha_set_form_id_setting($captcha_point_form_id, $data['captcha_type']);
    }
  }

  // Add new CAPTCHA point?
  $captcha_point_form_id = $form_state['values']['captcha_form_id_overview']['captcha_new_captcha_point']['form_id'];
  if (!empty($captcha_point_form_id)) {
    $captcha_type = $form_state['values']['captcha_form_id_overview']['captcha_new_captcha_point']['captcha_type'];
    captcha_set_form_id_setting($captcha_point_form_id, $captcha_type);
    drupal_set_message(t('Added CAPTCHA point.'), 'status');
  }

  // CAPTCHA description stuff.
  variable_set('captcha_add_captcha_description', $form_state['values']['captcha_add_captcha_description']);
  // Save (or reset) the CAPTCHA descriptions.
  if (module_exists('locale')) {
    $langs = locale_language_list();
    foreach ($langs as $lang_code => $lang_name) {
      $description = $form_state['values']["captcha_description_$lang_code"];
      if ($description) {
        variable_set("captcha_description_$lang_code", $description);
      }
      else {
        variable_del("captcha_description_$lang_code");
        drupal_set_message(t('Reset of CAPTCHA description for language %language.', array('%language' => $lang_name)), 'status');
      }
    }
  }
  else {
    $description = $form_state['values']['captcha_description'];
    if ($description) {
      variable_set('captcha_description', $description);
    }
    else {
      variable_del('captcha_description');
      drupal_set_message(t('Reset of CAPTCHA description.'), 'status');
    }
  }

  variable_set('captcha_default_validation', $form_state['values']['captcha_default_validation']);
  variable_set('captcha_persistence', $form_state['values']['captcha_persistence']);
  variable_set('captcha_log_wrong_responses', $form_state['values']['captcha_log_wrong_responses']);

  drupal_set_message(t('The CAPTCHA settings were saved.'), 'status');
}


/**
 * Central handler for CAPTCHA point administration (adding, disabling, deleting)
 */
function captcha_point_admin($captcha_point_form_id=NULL, $op=NULL) {
  module_load_include('inc', 'captcha');

  // if $captcha_point_form_id and action $op given: do the action
  if ($captcha_point_form_id) {
    switch ($op) {
      case 'disable':
        return drupal_get_form('captcha_point_disable_confirm', $captcha_point_form_id, FALSE);
      case 'delete':
        return drupal_get_form('captcha_point_disable_confirm', $captcha_point_form_id, TRUE);
    }
    // return edit form for CAPTCHA point
    return drupal_get_form('captcha_point_admin_form', $captcha_point_form_id);
  }
  // return add form for CAPTCHA point
  return drupal_get_form('captcha_point_admin_form');
}

function captcha_point_admin_form($form, $form_state, $captcha_point_form_id=NULL) {
  $form = array();
  $default_captcha_type = 'none';
  if (isset($captcha_point_form_id)) {
    // use given CAPTCHA point form_id
    $form['captcha_point_form_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Form ID'),
      '#description' => t('The Drupal form_id of the form to add the CAPTCHA to.'),
      '#value' => check_plain($captcha_point_form_id),
      '#disabled' => TRUE,
    );
    $captcha_point = captcha_get_form_id_setting($captcha_point_form_id);
    if ($captcha_point) {
      $default_captcha_type = "{$captcha_point->module}/{$captcha_point->captcha_type}";
    }
  }
  else {
    // textfield for CAPTCHA point form_id
    $form['captcha_point_form_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Form ID'),
      '#description' => t('The Drupal form_id of the form to add the CAPTCHA to.'),
    );
  }
  // select widget for CAPTCHA type
  $form['captcha_type'] = array(
    '#type' => 'select',
    '#title' => t('Challenge type'),
    '#description' => t('The CAPTCHA type to use for this form'),
    '#default_value' => $default_captcha_type,
    '#options' => _captcha_available_challenge_types(),
  );
  // redirect to general CAPTCHA settings page after submission
  $form['#redirect'] = 'admin/config/people/captcha';
  // submit button
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  return $form;
}


/**
 * validation function for captcha_point_admin_form
 */
function captcha_point_admin_form_validate($form, $form_state) {
  if (!preg_match('/^[a-z0-9_]+$/', $form_state['values']['captcha_point_form_id'])) {
    form_set_error('captcha_point_form_id', t('Illegal form_id'));
  }
}


/**
 * Submit function for captcha_point_admin_form.
 */
function captcha_point_admin_form_submit($form, $form_state) {
  $captcha_point_form_id = $form_state['values']['captcha_point_form_id'];
  $captcha_type = $form_state['values']['captcha_type'];
  captcha_set_form_id_setting($captcha_point_form_id, $captcha_type);
  drupal_set_message(t('Saved CAPTCHA point settings.'), 'status');
}

/**
 * Confirm dialog for disabling/deleting a CAPTCHA point
 */
function captcha_point_disable_confirm($form, &$form_state, $captcha_point_form_id, $delete) {
  $form = array();
  $form['captcha_point_form_id'] = array(
    '#type' => 'value',
    '#value' => $captcha_point_form_id,
  );
  $form['captcha_point_delete'] = array(
    '#type' => 'value',
    '#value' => $delete,
  );
  if ($delete) {
    $message = t('Are you sure you want to delete the CAPTCHA for form_id %form_id?', array('%form_id' => $captcha_point_form_id));
    $yes = t('Delete');
  }
  else {
    $message = t('Are you sure you want to disable the CAPTCHA for form_id %form_id?', array('%form_id' => $captcha_point_form_id));
    $yes = t('Disable');
  }
  return confirm_form($form, $message, 'admin/config/people/captcha/captcha', '', $yes);
}

/**
 * Submission handler of CAPTCHA point disabling/deleting confirm_form.
 */
function captcha_point_disable_confirm_submit($form, &$form_state) {
  $captcha_point_form_id = $form_state['values']['captcha_point_form_id'];
  $delete = $form_state['values']['captcha_point_delete'];
  if ($delete) {
    captcha_set_form_id_setting($captcha_point_form_id, NULL);
    drupal_set_message(t('Deleted CAPTCHA for form %form_id.', array('%form_id' => $captcha_point_form_id)));
  }
  else {
    captcha_set_form_id_setting($captcha_point_form_id, 'none');
    drupal_set_message(t('Disabled CAPTCHA for form %form_id.', array('%form_id' => $captcha_point_form_id)));
  }
  $form_state['redirect'] = 'admin/config/people/captcha/captcha';
}

/**
 * Helper function for generating an example challenge.
 */
function _captcha_generate_example_challenge($module, $type) {
  return array(
    '#type' => 'captcha',
    '#captcha_type' => $module . '/' . $type,
    '#captcha_admin_mode' => TRUE,
  );
}

/**
 * Funtion for generating a page with CAPTCHA examples.
 *
 * If the arguments $module and $challenge are not set, generate a list with
 * examples of the available CAPTCHA types.
 * If $module and $challenge are set, generate 10 examples of the concerning
 * CAPTCHA.
 */
function captcha_examples($form, $form_state, $module, $challenge) {
  module_load_include('inc', 'captcha');

  $form = array();
  if ($module && $challenge) {
    // Generate 10 example challenges.
    for ($i=0; $i<10; $i++) {
      $form["challenge_{$i}"] = _captcha_generate_example_challenge($module, $challenge);
    }
  }
  else {
    // generate a list with examples of the available CAPTCHA types
    $form['info'] = array(
      '#markup' => t('This page gives an overview of all available challenge types, generated with their current settings.'),
    );
    foreach (module_implements('captcha') as $mkey => $module) {
      $challenges = call_user_func_array($module . '_captcha', array('list'));
      if ($challenges) {
        foreach ($challenges as $ckey => $challenge) {
          $form["captcha_{$mkey}_{$ckey}"] = array(
            '#type' => 'fieldset',
            '#title' => t('Challenge "%challenge" by module "%module"', array('%challenge' => $challenge, '%module' => $module)),
            'challenge' => _captcha_generate_example_challenge($module, $challenge),
            'more_examples' => array(
              '#markup' => l(t('10 more examples of this challenge.'), "admin/config/people/captcha/captcha/examples/$module/$challenge"),
            ),
          );
        }
      }
    }
  }
  return $form;
}
