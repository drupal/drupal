<?php
// $Id$

/**
 * @file
 * Admin page callbacks for the filter module.
 */

/**
 * Menu callback; Displays a list of all text formats and allows them to be rearranged.
 *
 * @ingroup forms
 * @see filter_admin_overview_submit()
 */
function filter_admin_overview($form) {
  // Overview of all formats.
  $formats = filter_formats();
  $fallback_format = filter_fallback_format();

  $form['#tree'] = TRUE;
  foreach ($formats as $id => $format) {
    // Check whether this is the fallback text format. This format is available
    // to all roles and cannot be deleted via the admin interface.
    $form['formats'][$id]['#is_fallback'] = ($id == $fallback_format);
    if ($form['formats'][$id]['#is_fallback']) {
      $form['formats'][$id]['name'] = array('#markup' => theme('placeholder', $format->name));
      $roles_markup = theme('placeholder', t('All roles may use this format'));
    }
    else {
      $form['formats'][$id]['name'] = array('#markup' => check_plain($format->name));
      $roles = filter_get_roles_by_format($format);
      $roles_markup = $roles ? implode(', ', $roles) : t('No roles may use this format');
    }
    $form['formats'][$id]['roles'] = array('#markup' => $roles_markup);
    $form['formats'][$id]['configure'] = array('#markup' => l(t('configure'), 'admin/config/content/formats/' . $id));
    $form['formats'][$id]['delete'] = array('#markup' => $form['formats'][$id]['#is_fallback'] ? '' : l(t('delete'), 'admin/config/content/formats/delete/' . $id));
    $form['formats'][$id]['weight'] = array('#type' => 'weight', '#default_value' => $format->weight);
  }
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save changes'));
  return $form;
}

function filter_admin_overview_submit($form, &$form_state) {
  foreach ($form_state['values']['formats'] as $id => $data) {
    if (is_array($data) && isset($data['weight'])) {
      // Only update if this is a form element with weight.
      db_update('filter_format')
        ->fields(array('weight' => $data['weight']))
        ->condition('format', $id)
        ->execute();
    }
  }
  filter_formats_reset();
  drupal_set_message(t('The text format ordering has been saved.'));
}

/**
 * Theme the text format administration overview form.
 *
 * @ingroup themeable
 */
function theme_filter_admin_overview($form) {
  $rows = array();
  foreach (element_children($form['formats']) as $id) {
    $form['formats'][$id]['weight']['#attributes']['class'] = array('text-format-order-weight');
    $rows[] = array(
      'data' => array(
        drupal_render($form['formats'][$id]['name']),
        drupal_render($form['formats'][$id]['roles']),
        drupal_render($form['formats'][$id]['weight']),
        drupal_render($form['formats'][$id]['configure']),
        drupal_render($form['formats'][$id]['delete']),
      ),
      'class' => array('draggable'),
    );
  }
  $header = array(t('Name'), t('Roles'), t('Weight'), array('data' => t('Operations'), 'colspan' => 2));
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
    $format = (object)array('name' => '', 'format' => 0);
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
function filter_admin_format_form($form, &$form_state, $format) {
  $is_fallback = ($format->format == filter_fallback_format());
  if ($is_fallback) {
    $help = t('All roles for this text format must be enabled and cannot be changed.');
  }

  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $format->name,
    '#description' => t('Specify a unique name for this text format.'),
    '#required' => TRUE,
  );

  // Add a row of checkboxes for form group.
  $form['roles'] = array('#type' => 'fieldset',
    '#title' => t('Roles'),
    '#description' => $is_fallback ? $help : t('Choose which roles may use this text format. Note that roles with the "administer filters" permission can always use all text formats.'),
    '#tree' => TRUE,
  );
  $checked = filter_get_roles_by_format($format);
  foreach (user_roles() as $rid => $name) {
    $form['roles'][$rid] = array('#type' => 'checkbox',
      '#title' => $name,
      '#default_value' => ($is_fallback || isset($checked[$rid])),
    );
    if ($is_fallback) {
      $form['roles'][$rid]['#disabled'] = TRUE;
    }
  }
  // Table with filters
  $filter_info = filter_get_filters();
  $filters = filter_list_format($format->format, TRUE);

  $form['filters'] = array('#type' => 'fieldset',
    '#title' => t('Filters'),
    '#description' => t('Choose the filters that will be used in this text format.'),
    '#tree' => TRUE,
  );
  foreach ($filter_info as $name => $filter) {
    $form['filters'][$name]['status'] = array(
      '#type' => 'checkbox',
      '#title' => $filter['title'],
      '#default_value' => !empty($filters[$name]->status),
      '#description' => $filter['description'],
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
    $format_name = trim($form_state['values']['name']);
    $result = db_query("SELECT format FROM {filter_format} WHERE name = :name", array(':name' => $format_name))->fetchField();
    if ($result) {
      form_set_error('name', t('Text format names must be unique. A format named %name already exists.', array('%name' => $format_name)));
    }
  }
}

/**
 * Process text format form submissions.
 */
function filter_admin_format_form_submit($form, &$form_state) {
  $format = (object) $form_state['values'];
  $format->format = isset($form_state['values']['format']) ? $form_state['values']['format'] : NULL;
  $status = filter_format_save($format);

  if ($permission = filter_permission_name($format)) {
    foreach ($format->roles as $rid => $enabled) {
      user_role_change_permissions($rid, array($permission => $enabled));
    }
  }

  switch ($status) {
    case SAVED_NEW:
      drupal_set_message(t('Added text format %format.', array('%format' => $format->name)));
      break;

    case SAVED_UPDATED:
      drupal_set_message(t('The text format %format has been updated.', array('%format' => $format->name)));
      break;
  }
}

/**
 * Menu callback; confirm deletion of a format.
 *
 * @ingroup forms
 * @see filter_admin_delete_submit()
 */
function filter_admin_delete($form, &$form_state, $format) {
  $form['#format'] = $format;

  return confirm_form($form,
    t('Are you sure you want to delete the text format %format?', array('%format' => $format->name)),
    'admin/config/content/formats',
    t('If you have any content left in this text format, it will be switched to the %fallback text format. This action cannot be undone.', array('%fallback' => filter_fallback_format_title())),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Process filter delete form submission.
 */
function filter_admin_delete_submit($form, &$form_state) {
  $format = $form['#format'];
  filter_format_delete($format);
  drupal_set_message(t('Deleted text format %format.', array('%format' => $format->name)));

  $form_state['redirect'] = 'admin/config/content/formats';
}

/**
 * Menu callback; display settings defined by a format's filters.
 */
function filter_admin_configure_page($format) {
  drupal_set_title(t("Configure %format", array('%format' => $format->name)), PASS_THROUGH);
  return drupal_get_form('filter_admin_configure', $format);
}

/**
 * Build a form to change the settings for filters in a text format.
 *
 * The form is built by merging the results of 'settings callback' for each
 * enabled filter in the given format.
 *
 * @ingroup forms
 */
function filter_admin_configure($form, &$form_state, $format) {
  $filters = filter_list_format($format->format);
  $filter_info = filter_get_filters();

  $form['#format'] = $format;
  foreach ($filters as $name => $filter) {
    if (isset($filter_info[$name]['settings callback']) && function_exists($filter_info[$name]['settings callback'])) {
      // Pass along stored filter settings and default settings, but also the
      // format object and all filters to allow for complex implementations.
      $defaults = (isset($filter_info[$name]['default settings']) ? $filter_info[$name]['default settings'] : array());
      $settings_form = $filter_info[$name]['settings callback']($form, $form_state, $filters[$name], $defaults, $format, $filters);
      if (isset($settings_form) && is_array($settings_form)) {
        $form['settings'][$name] = array(
          '#type' => 'fieldset',
          '#title' => check_plain($filter->title),
        );
        $form['settings'][$name] += $settings_form;
      }
    }
  }

  if (empty($form['settings'])) {
    $form['error'] = array('#markup' => t('No settings are available.'));
    return $form;
  }
  $form['settings']['#tree'] = TRUE;
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save configuration'));

  return $form;
}

/**
 * Form submit handler for text format filter configuration form.
 *
 * @see filter_admin_configure()
 */
function filter_admin_configure_submit($form, &$form_state) {
  $format = $form['#format'];

  foreach ($form_state['values']['settings'] as $name => $settings) {
    db_update('filter')
      ->fields(array(
        'settings' => serialize($settings),
      ))
      ->condition('format', $format->format)
      ->condition('name', $name)
      ->execute();
  }

  // Clear the filter's cache when configuration settings are saved.
  cache_clear_all($format->format . ':', 'cache_filter', TRUE);

  drupal_set_message(t('The configuration options have been saved.'));
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
function filter_admin_order($form, &$form_state, $format = NULL) {
  // Get list (with forced refresh).
  $filters = filter_list_format($format->format);

  $form['weights'] = array('#tree' => TRUE);
  foreach ($filters as $id => $filter) {
    $form['names'][$id] = array('#markup' => $filter->title);
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
      $form['weights'][$id]['#attributes']['class'] = array('filter-order-weight');
      $rows[] = array(
        'data' => array(drupal_render($form['names'][$id]), drupal_render($form['weights'][$id])),
        'class' => array('draggable'),
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
  foreach ($form_state['values']['weights'] as $name => $weight) {
    db_merge('filter')
      ->key(array(
        'format' => $form_state['values']['format'],
        'name' => $name,
      ))
      ->fields(array(
        'weight' => $weight,
      ))
      ->execute();
  }
  drupal_set_message(t('The filter ordering has been saved.'));

  cache_clear_all($form_state['values']['format'] . ':', 'cache_filter', TRUE);
}
