<?php
// $Id: captcha.inc,v 1.18 2010/12/30 00:29:15 soxofaan Exp $

/**
 * @file
 * General CAPTCHA functionality and helper functions.
 */

/**
 * Helper function for adding/updating a CAPTCHA point.
 *
 * @param $form_id the form ID to configure.
 * @param captcha_type the setting for the given form_id, can be:
 *   - 'none' to disable CAPTCHA,
 *   - 'default' to use the default challenge type
 *   - NULL to remove the entry for the CAPTCHA type
 *   - something of the form 'image_captcha/Image'
 *   - an object with attributes $captcha_type->module and $captcha_type->captcha_type
 * @return nothing
 */
function captcha_set_form_id_setting($form_id, $captcha_type) {
  // Handle 'none'.
  if ($captcha_type == 'none') {
    db_merge('captcha_points')
      ->key(array('form_id' => $form_id))
      ->fields(array('module' => NULL, 'captcha_type' => NULL))
      ->execute();
  }
  // Handle 'default'.
  elseif ($captcha_type == 'default') {
    db_merge('captcha_points')
      ->key(array('form_id' => $form_id))
      ->fields(array('module' => NULL, 'captcha_type' => 'default'))
      ->execute();
  }
  // Handle NULL.
  elseif ($captcha_type == NULL) {
    db_delete('captcha_points')->condition('form_id', $form_id)->execute();
  }
  // Handle a captcha_type object.
  elseif (is_object($captcha_type) && isset($captcha_type->module) && isset($captcha_type->captcha_type)) {
    db_merge('captcha_points')
      ->key(array('form_id' => $form_id))
      ->fields(array('module' => $captcha_type->module, 'captcha_type' => $captcha_type->captcha_type))
      ->execute();
  }
  // Handle a captcha_type string.
  elseif (is_string($captcha_type) && substr_count($captcha_type, '/') == 1) {
    list($module, $type) = explode('/', $captcha_type);
    db_merge('captcha_points')
      ->key(array('form_id' => $form_id))
      ->fields(array('module' => $module, 'captcha_type' => $type))
      ->execute();
  }
  else {
    drupal_set_message(t('Failed to set a CAPTCHA type for form %form_id: could not interpret value "@captcha_type"',
      array('%form_id' => $form_id, '@captcha_type' => (string)$captcha_type)), 'warning');
  }
}

/**
 * Get the CAPTCHA setting for a given form_id.
 *
 * @param $form_id the form_id to query for
 * @param $symbolic flag to return as (symbolic) strings instead of object.
 *
 * @return NULL if no setting is known
 *   or a captcha_point object with fields 'module' and 'captcha_type'.
 *   If argument $symbolic is true, returns (symbolic) as 'none', 'default'
 *   or in the form 'captcha/Math'.
 */
function captcha_get_form_id_setting($form_id, $symbolic=FALSE) {
  $result = db_query("SELECT module, captcha_type FROM {captcha_points} WHERE form_id = :form_id",
    array(':form_id' =>  $form_id));
  $captcha_point = $result->fetchObject();
  if (!$captcha_point) {
    $captcha_point = NULL;
  }
  elseif ($captcha_point->captcha_type == 'default') {
    if (!$symbolic) {
      list($module, $type) = explode('/', variable_get('captcha_default_challenge', 'captcha/Math'));
      $captcha_point->module = $module;
      $captcha_point->captcha_type = $type;
    }
    else {
      $captcha_point = 'default';
    }
  }
  elseif ($captcha_point->module == NULL && $captcha_point->captcha_type == NULL && $symbolic) {
    $captcha_point = 'none';
  }
  elseif ($symbolic) {
    $captcha_point = $captcha_point->module . '/' . $captcha_point->captcha_type;
  }
  return $captcha_point;
}


/**
 * Helper function for generating a new CAPTCHA session.
 *
 * @param $form_id the form_id of the form to add a CAPTCHA to.
 * @param $status the initial status of the CAPTHCA session.
 * @return the session ID of the new CAPTCHA session.
 */
function _captcha_generate_captcha_session($form_id=NULL, $status=CAPTCHA_STATUS_UNSOLVED) {
  global $user;
  // Initialize solution with random data.
  $solution = md5(mt_rand());
  // Insert an entry and thankfully receive the value of the autoincrement field 'csid'.
  $captcha_sid = db_insert('captcha_sessions')
  ->fields(array(
    'uid' => $user->uid,
    'sid' => session_id(),
    'ip_address' => ip_address(),
    'timestamp' => REQUEST_TIME,
    'form_id' => $form_id,
    'solution' => $solution,
    'status' => $status,
    'attempts' => 0,
  ))
  ->execute();
  return $captcha_sid;
}

/**
 * Helper function for updating the solution in the CAPTCHA session table.
 *
 * @param $captcha_sid the CAPTCHA session ID to update.
 * @param $solution the new solution to associate with the given CAPTCHA session.
 */
function _captcha_update_captcha_session($captcha_sid, $solution) {
  db_update('captcha_sessions')
    ->condition('csid', $captcha_sid)
    ->fields(array(
      'timestamp' => REQUEST_TIME,
      'solution' => $solution,
    ))
    ->execute();
}

/**
 * Helper function for checking if CAPTCHA is required for user,
 * based on the CAPTCHA persistence setting, the CAPTCHA session ID and
 * user session info.
 */
function _captcha_required_for_user($captcha_sid, $form_id) {
  // Get the CAPTCHA persistence setting.
  $captcha_persistence = variable_get('captcha_persistence', CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_INSTANCE);

  // First check: should we always add a CAPTCHA?
  if ($captcha_persistence == CAPTCHA_PERSISTENCE_SHOW_ALWAYS) {
    return TRUE;
  }

  // Get the status of the current CAPTCHA session.
  $captcha_session_status = db_query('SELECT status FROM {captcha_sessions} WHERE csid = :csid', array(':csid' => $captcha_sid))->fetchField();
  // Second check: if the current session is already solved: omit further CAPTCHAs.
  if ($captcha_session_status == CAPTCHA_STATUS_SOLVED) {
    return FALSE;
  }

  // Third check: look at the persistence level (per form instance, per form or per user).
  if ($captcha_persistence == CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_INSTANCE) {
    return TRUE;
  }
  else {
    $captcha_success_form_ids = isset($_SESSION['captcha_success_form_ids']) ? (array)($_SESSION['captcha_success_form_ids']) : array();
    switch ($captcha_persistence) {
      case CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL:
        return (count($captcha_success_form_ids) == 0);
      case CAPTCHA_PERSISTENCE_SKIP_ONCE_SUCCESSFUL_PER_FORM_TYPE:
        return !isset($captcha_success_form_ids[$form_id]);
    }
  }

  // We should never get to this point, but to be sure, we return TRUE.
  return TRUE;
}


/**
 * Get the CAPTCHA description as configured on the general CAPTCHA
 * settings page.
 *
 * If the locale module is enabled, the description will be returned
 * for the current language the page is rendered for. This language
 * can optionally been overriden with the $lang_code argument.
 *
 * @param $lang_code an optional language code to get the descripion for.
 * @return a string with (localized) CAPTCHA description.
 */
function _captcha_get_description($lang_code=NULL) {
  // If no language code is given: use the language of the current page.
  global $language;
  $lang_code = isset($lang_code) ? $lang_code : $language->language;
  // The hardcoded but localizable default.
  $default = t('This question is for testing whether you are a human visitor and to prevent automated spam submissions.', array(), array('langcode' => $lang_code));
  // Look up the configured CAPTCHA description or fall back on the (localized) default.
  if (module_exists('locale')) {
    $description = variable_get("captcha_description_$lang_code", $default);
  }
  else {
    $description = variable_get('captcha_description', $default);
  }
  return filter_xss_admin($description);
}

/**
 * Parse or interpret the given captcha_type.
 * @param $captcha_type string representation of the CAPTCHA type,
 *      e.g. 'default', 'none', 'captcha/Math', 'image_captcha/Image'
 * @return list($captcha_module, $captcha_type)
 */
function _captcha_parse_captcha_type($captcha_type) {
  if ($captcha_type == 'none') {
    return array(NULL, NULL);
  }
  if ($captcha_type == 'default') {
    $captcha_type = variable_get('captcha_default_challenge', 'captcha/Math');
  }
  return explode('/', $captcha_type);
}

/**
 * Helper function to get placement information for a given form_id.
 * @param $form_id the form_id to get the placement information for.
 * @param $form if a form corresponding to the given form_id, if there
 *   is no placement info for the given form_id, this form is examined to
 *   guess the placement.
 * @return placement info array (@see _captcha_insert_captcha_element() for more
 *   info about the fields 'path', 'key' and 'weight'.
 */
function _captcha_get_captcha_placement($form_id, $form) {
  // Get CAPTCHA placement map from cache. Two levels of cache:
  // static variable in this function and storage in the variables table.
  static $placement_map = NULL;
  // Try first level cache.
  if ($placement_map === NULL) {
    // If first level cache missed: try second level cache.
    $placement_map = variable_get('captcha_placement_map_cache', NULL);
    // TODO: add UI in admin UI for flushing this cache.

    if ($placement_map === NULL) {
      // If second level cache missed: start from a fresh placement map.
      $placement_map = array();
      // Prefill with some hard coded default entries.

      // The comment form can have a 'Preview' button, or both a 'Submit' and 'Preview' button,
      // which is tricky for automatic placement detection. Luckily, Drupal core sets their
      // weight (19 and 20), so we just have to specify a slightly smaller weight.
      $placement_map['comment_form'] = array('path' => array(), 'key' => NULL, 'weight' => 18.9);
      // Additional note: the node forms also have the posibility to only show a 'Preview' button.
      // However, the 'Submit' button is always present, but is just not rendered ('#access' = FALSE)
      // in those cases. The the automatic button detection should be sufficient for node forms.

      // $placement_map['user_login'] = array('path' => array(), 'key' => NULL, 'weight' => 1.9);
      // TODO: also make the placement 'overridable' from the admin UI?
    }
  }

  // Query the placement map.
  if (array_key_exists($form_id, $placement_map)) {
    $placement = $placement_map[$form_id];
  }
  // If no placement info is available in placement map:
  // search the form for buttons and guess placement from it.
  else {
    $buttons = _captcha_search_buttons($form);
    if (count($buttons)) {
      // Pick first button.
      // TODO: make this more sofisticated? Use cases needed.
      $placement = $buttons[0];
    }
    else {
      // Use NULL when no buttons were found.
      $placement = NULL;
    }

    // Store calculated placement in caches.
    $placement_map[$form_id] = $placement;
    variable_set('captcha_placement_map_cache', $placement_map);
  }

  return $placement;
}

/**
 * Helper function for searching the buttons in a form.
 *
 * @param $form the form to search button elements in
 * @return an array of paths to the buttons.
 *   A path is an array of keys leading to the button, the last
 *   item in the path is the weight of the button element
 *   (or NULL if undefined).
 */
function _captcha_search_buttons($form) {
  $buttons = array();
  foreach (element_children($form) as $key) {
    // Look for submit or button type elements.
    if (isset($form[$key]['#type']) && ($form[$key]['#type'] == 'submit' || $form[$key]['#type'] == 'button')) {
      $weight = isset($form[$key]['#weight']) ? $form[$key]['#weight'] : NULL;
      $buttons[] = array(
        'path' => array(),
        'key' => $key,
        'weight' => $weight,
      );
    }
    // Process children recurively.
    $children_buttons = _captcha_search_buttons($form[$key]);
    foreach ($children_buttons as $b) {
      $b['path'] = array_merge(array($key), $b['path']);
      $buttons[] = $b;
    }
  }
  return $buttons;
}

/**
 * Helper function to insert a CAPTCHA element in a form before a given form element.
 * @param $form the form to add the CAPTCHA element to.
 * @param $placement information where the CAPTCHA element should be inserted.
 *   $placement should be an associative array with fields:
 *     - 'path': path (array of path items) of the container in the form where the
 *       CAPTCHA element should be inserted.
 *     - 'key': the key of the element before which the CAPTCHA element
 *       should be inserted. If the field 'key' is undefined or NULL, the CAPTCHA will
 *       just be appended to the container.
 *     - 'weight': if 'key' is not NULL: should be the weight of the element defined by 'key'.
 *       If 'key' is NULL and weight is not NULL: set the weight property of the CAPTCHA element
 *       to this value.
 * @param $captcha_element the CAPTCHA element to insert.
 */
function _captcha_insert_captcha_element(&$form, $placement, $captcha_element) {
  // Get path, target and target weight or use defaults if not available.
  $target_key = isset($placement['key']) ? $placement['key'] : NULL;
  $target_weight = isset($placement['weight']) ? $placement['weight'] : NULL;
  $path = isset($placement['path']) ? $placement['path'] : array();

  // Walk through the form along the path.
  $form_stepper = &$form;
  foreach ($path as $step) {
    if (isset($form_stepper[$step])) {
      $form_stepper = & $form_stepper[$step];
    }
    else {
      // Given path is invalid: stop stepping and
      // continue in best effort (append instead of insert).
      $target_key = NULL;
      break;
    }
  }

  // If no target is available: just append the CAPTCHA element to the container.
  if ($target_key == NULL || !array_key_exists($target_key, $form_stepper)) {
    // Optionally, set weight of CAPTCHA element.
    if ($target_weight != NULL) {
      $captcha_element['#weight'] = $target_weight;
    }
    $form_stepper['captcha'] =  $captcha_element;
  }
  // If there is a target available: make sure the CAPTCHA element comes right before it.
  else {
    // If target has a weight: set weight of CAPTCHA element a bit smaller
    // and just append the CAPTCHA: sorting will fix the ordering anyway.
    if ($target_weight != NULL) {
      $captcha_element['#weight'] = $target_weight - .1;
      $form_stepper['captcha'] =  $captcha_element;
    }
    else {
      // If we can't play with weights: insert the CAPTCHA element at the right position.
      // Because PHP lacks a function for this (array_splice() comes close,
      // but it does not preserve the key of the inserted element), we do it by hand:
      // chop of the end, append the CAPTCHA element and put the end back.
      $offset = array_search($target_key, array_keys($form_stepper));
      $end = array_splice($form_stepper, $offset);
      $form_stepper['captcha'] =  $captcha_element;
      foreach ($end as $k => $v) {
        $form_stepper[$k] = $v;
      }
    }
  }
}

