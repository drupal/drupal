<?php
// $Id$

/**
 * @file
 * Admin page callbacks for the filter module.
 */

/**
 * Menu callback; Displays a list of all text formats and which
 * one is the default.
 *
 * @ingroup forms
 * @see filter_admin_overview_submit()
 */
function filter_admin_overview() {

  // Overview of all formats.
  $formats = filter_formats();
  $error = FALSE;

  $form = array('#tree' => TRUE);
  foreach ($formats as $id => $format) {
    $roles = array();
    foreach (user_roles() as $rid => $name) {
      // Prepare a roles array with roles that may access the filter.
      if (strpos($format->roles, ",$rid,") !== FALSE) {
        $roles[] = $name;
      }
    }
    $default = ($id == variable_get('filter_default_format', 1));
    $options[$id] = '';
    $form[$id]['name'] = array('#markup' => $format->name);
    $form[$id]['roles'] = array('#markup' => $default ? t('All roles may use the default format') : ($roles ? implode(', ', $roles) : t('No roles may use this format')));
    $form[$id]['configure'] = array('#markup' => l(t('configure'), 'admin/settings/formats/' . $id));
    $form[$id]['delete'] = array('#markup' => $default ? '' : l(t('delete'), 'admin/settings/formats/delete/' . $id));
    $form[$id]['weight'] = array('#type' => 'weight', '#default_value' => $format->weight);
  }
  $form['default'] = array('#type' => 'radios', '#options' => $options, '#default_value' => variable_get('filter_default_format', 1));
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save changes'));
  return $form;
}

function filter_admin_overview_submit($form, &$form_state) {
  // Process form submission to set the default format.
  if (is_numeric($form_state['values']['default'])) {
    drupal_set_message(t('Default format updated.'));
    variable_set('filter_default_format', $form_state['values']['default']);
  }
  foreach ($form_state['values'] as $id => $data) {
    if (is_array($data) && isset($data['weight'])) {
      // Only update if this is a form element with weight.
      db_update('filter_format')
        ->fields(array('weight' => $data['weight']))
        ->condition('format', $id)
        ->execute();
    }
  }
  drupal_set_message(t('The text format ordering has been saved.'));
}

/**
 * Theme the admin overview form.
 *
 * @ingroup themeable
 */
function theme_filter_admin_overview($form) {
  $rows = array();
  foreach ($form as $id => $element) {
    if (isset($element['roles']) && is_array($element['roles'])) {
      $element['weight']['#attributes']['class'] = 'text-format-order-weight';
      $rows[] = array(
        'data' => array(
          check_plain($element['name']['#markup']),
          drupal_render($element['roles']),
          drupal_render($form['default'][$id]),
          drupal_render($element['weight']),
          drupal_render($element['configure']),
          drupal_render($element['delete']),
        ),
        'class' => 'draggable',
      );
      unset($form[$id]);
    }
  }
  $header = array(t('Name'), t('Roles'), t('Default'), t('Weight'), array('data' => t('Operations'), 'colspan' => 2));
  $output = theme('table', $header, $rows, array('id' => 'text-format-order'));
  $output .= drupal_render_children($form);

  drupal_add_tabledrag('text-format-order', 'order', 'sibling', 'text-format-order-weight');

  return $output;
}

/**
 * Menu callback; Display a text format form.
 */
function filter_admin_format_page($format = NULL) {
  if (!isset($format->name)) {
    drupal_set_title(t('Add text format'), PASS_THROUGH);
    $format = (object)array('name' => '', 'roles' => '', 'format' => '');
  }
  return drupal_get_form('filter_admin_format_form', $format);
}

/**
 * Generate a text format form.
 *
 * @ingroup forms
 * @see filter_admin_format_form_validate()
 * @see filter_admin_format_form_submit()
 */
function filter_admin_format_form(&$form_state, $format) {
  $default = ($format->format == variable_get('filter_default_format', 1));
  if ($default) {
    $help = t('All roles for the default format must be enabled and cannot be changed.');
    $form['default_format'] = array('#type' => 'hidden', '#value' => 1);
  }

  $form['name'] = array('#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $format->name,
    '#description' => t('Specify a unique name for this text format.'),
    '#required' => TRUE,
  );

  // Add a row of checkboxes for form group.
  $form['roles'] = array('#type' => 'fieldset',
    '#title' => t('Roles'),
    '#description' => $default ? $help : t('Choose which roles may use this text format. Note that roles with the "administer filters" permission can always use all text formats.'),
    '#tree' => TRUE,
  );

  foreach (user_roles() as $rid => $name) {
    $checked = strpos($format->roles, ",$rid,") !== FALSE;
    $form['roles'][$rid] = array('#type' => 'checkbox',
      '#title' => $name,
      '#default_value' => ($default || $checked),
    );
    if ($default) {
      $form['roles'][$rid]['#disabled'] = TRUE;
    }
  }
  // Table with filters
  $all = filter_list_all();
  $enabled = filter_list_format($format->format);

  $form['filters'] = array('#type' => 'fieldset',
    '#title' => t('Filters'),
    '#description' => t('Choose the filters that will be used in this text format.'),
    '#tree' => TRUE,
  );
  foreach ($all as $id => $filter) {
    $filter_info = module_invoke($filter->module, 'filter_info');
    $form['filters'][$id] = array('#type' => 'checkbox',
      '#title' => $filter->name,
      '#default_value' => isset($enabled[$id]),
      '#description' => $filter_info[$filter->delta]['description'],
    );
  }
  if (!empty($format->format)) {
    $form['format'] = array('#type' => 'hidden', '#value' => $format->format);

    // Composition tips (guidelines)
    $tips = _filter_tips($format->format, FALSE);
    $tiplist = theme('filter_tips', $tips, FALSE);
    if (!$tiplist) {
      $tiplist = '<p>' . t('No guidelines available.') . '</p>';
    }
    else {
      $tiplist .= theme('filter_tips_more_info');
    }
    $group = '<p>' . t('These are the guidelines that users will see for posting in this text format. They are automatically generated from the filter settings.') . '</p>';
    $group .= $tiplist;
    $form['tips'] = array('#markup' => '<h2>' . t('Formatting guidelines') . '</h2>' . $group);
  }
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save configuration'));

  return $form;
}

/**
 * Validate text format form submissions.
 */
function filter_admin_format_form_validate($form, &$form_state) {
  if (!isset($form_state['values']['format'])) {
    $name = trim($form_state['values']['name']);
    $result = db_query("SELECT format FROM {filter_format} WHERE name = :name", array(':name' => $name))->fetchField();
    if ($result) {
      form_set_error('name', t('Text format names must be unique. A format named %name already exists.', array('%name' => $name)));
    }
  }
}

/**
 * Process text format form submissions.
 */
function filter_admin_format_form_submit($form, &$form_state) {
  $format = isset($form_state['values']['format']) ? $form_state['values']['format'] : NULL;
  $current = filter_list_format($format);
  $name = trim($form_state['values']['name']);
  $cache = TRUE;

  // Add a new text format.
  if (!$format) {
    $new = TRUE;
    db_insert('filter_format')
      ->fields(array('name' => $name))
      ->execute();
    $format = db_query("SELECT MAX(format) AS format FROM {filter_format}")->fetchField();
    drupal_set_message(t('Added text format %format.', array('%format' => $name)));
  }
  else {
    drupal_set_message(t('The text format settings have been updated.'));
  }
  db_delete('filter')
    ->condition('format', $format)
    ->execute();
  $query = db_insert('filter')->fields(array('format', 'module', 'delta', 'weight'));
  foreach ($form_state['values']['filters'] as $id => $checked) {
    if ($checked) {
      list($module, $delta) = explode('/', $id);
      // Add new filters to the bottom.
      $weight = isset($current[$id]->weight) ? $current[$id]->weight : 10;
      $query->values(array(
        'format' => $format,
        'module' => $module,
        'delta'  => $delta,
        'weight' => $weight,
      ));
      // Check if there are any 'no cache' filters.
      $cache &= !module_invoke($module, 'filter', 'no cache', $delta);
    }
    $query->execute();
  }

  // We store the roles as a string for ease of use.
  // We should always set all roles to TRUE when saving a default role.
  // We use leading and trailing comma's to allow easy substring matching.
  $roles = array();
  if (isset($form_state['values']['roles'])) {
    foreach ($form_state['values']['roles'] as $id => $checked) {
      if ($checked) {
        $roles[] = $id;
      }
    }
  }
  if (!empty($form_state['values']['default_format'])) {
    $roles = ',' . implode(',', array_keys(user_roles())) . ',';
  }
  else {
    $roles = ',' . implode(',', $roles) . ',';
  }

  db_update('filter_format')
    ->fields(array(
      'cache' => $cache,
      'name'  => $name,
      'roles' => $roles,
    ))
    ->condition('format', $format)
    ->execute();

  cache_clear_all($format . ':', 'cache_filter', TRUE);

  // If a new filter was added, return to the main list of filters. Otherwise, stay on edit filter page to show new changes.
  $return = 'admin/settings/formats';
  if (!empty($new)) {
    $return .= '/' . $format;
  }
  $form_state['redirect'] = $return;
  return;
}

/**
 * Menu callback; confirm deletion of a format.
 *
 * @ingroup forms
 * @see filter_admin_delete_submit()
 */
function filter_admin_delete() {
  $format = arg(4);
  $format = db_query('SELECT * FROM {filter_format} WHERE format = :format', array(':format' => $format))->fetchObject();

  if ($format) {
    if ($format->format != variable_get('filter_default_format', 1)) {
      $form['format'] = array('#type' => 'hidden', '#value' => $format->format);
      $form['name'] = array('#type' => 'hidden', '#value' => $format->name);

      return confirm_form($form, t('Are you sure you want to delete the text format %format?', array('%format' => $format->name)), 'admin/settings/formats', t('If you have any content left in this text format, it will be switched to the default text format. This action cannot be undone.'), t('Delete'), t('Cancel'));
    }
    else {
      drupal_set_message(t('The default format cannot be deleted.'));
      drupal_goto('admin/settings/formats');
    }
  }
  else {
    drupal_not_found();
  }
}

/**
 * Process filter delete form submission.
 */
function filter_admin_delete_submit($form, &$form_state) {
  db_delete('filter_format')
    ->condition('format', $form_state['values']['format'])
    ->execute();
  db_delete('filter')
    ->condition('format', $form_state['values']['format'])
    ->execute();

  $default = variable_get('filter_default_format', 1);
  // Replace existing instances of the deleted format with the default format.
  if (db_table_exists('comment')) {
    db_update('comment')
      ->fields(array('format' => $default))
      ->condition('format', $form_state['values']['format'])
      ->execute();
  }
  if (db_table_exists('box')) {
    db_update('box')
      ->fields(array('format' => $default))
      ->condition('format', $form_state['values']['format'])
      ->execute();
  }

  cache_clear_all($form_state['values']['format'] . ':', 'cache_filter', TRUE);
  drupal_set_message(t('Deleted text format %format.', array('%format' => $form_state['values']['name'])));

  $form_state['redirect'] = 'admin/settings/formats';
  return;
}


/**
 * Menu callback; display settings defined by a format's filters.
 */
function filter_admin_configure_page($format) {
  drupal_set_title(t("Configure %format", array('%format' => $format->name)), PASS_THROUGH);
  return drupal_get_form('filter_admin_configure', $format);
}

/**
 * Build a form to change the settings for a format's filters.
 *
 * @ingroup forms
 */
function filter_admin_configure(&$form_state, $format) {
  $list = filter_list_format($format->format);
  $form = array();
  foreach ($list as $filter) {
    $filter_info = module_invoke($filter->module, 'filter_info');
    if (isset($filter_info[$filter->delta]['settings callback']) && drupal_function_exists($filter_info[$filter->delta]['settings callback'])) {
      $form_module = call_user_func($filter_info[$filter->delta]['settings callback'], $format->format);
    }
    if (isset($form_module) && is_array($form_module)) {
      $form = array_merge($form, $form_module);
    }
  }

  if (!empty($form)) {
    $form = system_settings_form($form, TRUE);
  }
  else {
    $form['error'] = array('#markup' => t('No settings are available.'));
  }
  $form['format'] = array('#type' => 'hidden', '#value' => $format->format);
  $form['#submit'][] = 'filter_admin_configure_submit';
  return $form;
}

/**
 * Clear the filter's cache when configuration settings are saved.
 */
function filter_admin_configure_submit($form, &$form_state) {
  cache_clear_all($form_state['values']['format'] . ':', 'cache_filter', TRUE);
}

/**
 * Menu callback; display form for ordering filters for a format.
 */
function filter_admin_order_page($format) {
  drupal_set_title(t("Rearrange %format", array('%format' => $format->name)), PASS_THROUGH);
  return drupal_get_form('filter_admin_order', $format);
}

/**
 * Build the form for ordering filters for a format.
 *
 * @ingroup forms
 * @see theme_filter_admin_order()
 * @see filter_admin_order_submit()
 */
function filter_admin_order(&$form_state, $format = NULL) {
  // Get list (with forced refresh).
  $filters = filter_list_format($format->format);

  $form['weights'] = array('#tree' => TRUE);
  foreach ($filters as $id => $filter) {
    $form['names'][$id] = array('#markup' => $filter->name);
    $form['weights'][$id] = array('#type' => 'weight', '#default_value' => $filter->weight);
  }
  $form['format'] = array('#type' => 'hidden', '#value' => $format->format);
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save configuration'));

  return $form;
}

/**
 * Theme filter order configuration form.
 *
 * @ingroup themeable
 */
function theme_filter_admin_order($form) {
  $header = array(t('Name'), t('Weight'));
  $rows = array();
  foreach (element_children($form['names']) as $id) {
    // Don't take form control structures.
    if (is_array($form['names'][$id])) {
      $form['weights'][$id]['#attributes']['class'] = 'filter-order-weight';
      $rows[] = array(
        'data' => array(drupal_render($form['names'][$id]), drupal_render($form['weights'][$id])),
        'class' => 'draggable',
      );
    }
  }

  $output = theme('table', $header, $rows, array('id' => 'filter-order'));
  $output .= drupal_render_children($form);

  drupal_add_tabledrag('filter-order', 'order', 'sibling', 'filter-order-weight', NULL, NULL, FALSE);

  return $output;
}

/**
 * Process filter order configuration form submission.
 */
function filter_admin_order_submit($form, &$form_state) {
  foreach ($form_state['values']['weights'] as $id => $weight) {
    list($module, $delta) = explode('/', $id);
    db_update('filter')
      ->fields(array('weight' => $weight))
      ->condition('format', $form_state['values']['format'])
      ->condition('module', $module)
      ->condition('delta', $delta)
      ->execute();
  }
  drupal_set_message(t('The filter ordering has been saved.'));

  cache_clear_all($form_state['values']['format'] . ':', 'cache_filter', TRUE);
}
