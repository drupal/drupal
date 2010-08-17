<?php
// $Id: filter.admin.inc,v 1.64 2010/08/17 13:50:52 dries Exp $

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
      $form['formats'][$id]['name'] = array('#markup' => drupal_placeholder($format->name));
      $roles_markup = drupal_placeholder(t('All roles may use this format'));
    }
    else {
      $form['formats'][$id]['name'] = array('#markup' => check_plain($format->name));
      $roles = array_map('check_plain', filter_get_roles_by_format($format));
      $roles_markup = $roles ? implode(', ', $roles) : t('No roles may use this format');
    }
    $form['formats'][$id]['roles'] = array('#markup' => $roles_markup);
    $form['formats'][$id]['configure'] = array('#type' => 'link', '#title' => t('configure'), '#href' => 'admin/config/content/formats/' . $id);
    $form['formats'][$id]['delete'] = array('#type' => 'link', '#title' => t('delete'), '#href' => 'admin/config/content/formats/' . $id . '/delete', '#access' => !$form['formats'][$id]['#is_fallback']);
    $form['formats'][$id]['weight'] = array('#type' => 'weight', '#default_value' => $format->weight);
  }
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save changes'));
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
 * Returns HTML for the text format administration overview form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_filter_admin_overview($variables) {
  $form = $variables['form'];

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
  $output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'text-format-order')));
  $output .= drupal_render_children($form);

  drupal_add_tabledrag('text-format-order', 'order', 'sibling', 'text-format-order-weight');

  return $output;
}

/**
 * Menu callback; Display a text format form.
 */
function filter_admin_format_page($format = NULL) {
  if (!isset($format->name)) {
    drupal_set_title(t('Add text format'));
    $format = (object) array('name' => '', 'format' => 0);
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

  $form['#format'] = $format;
  $form['#tree'] = TRUE;
  $form['#attached']['js'][] = drupal_get_path('module', 'filter') . '/filter.admin.js';
  $form['#attached']['css'][] = drupal_get_path('module', 'filter') . '/filter.css';

  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $format->name,
    '#required' => TRUE,
  );

  // Add user role access selection.
  $form['roles'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Roles'),
    '#options' => array_map('check_plain', user_roles()),
    '#disabled' => $is_fallback,
  );
  if ($is_fallback) {
    $form['roles']['#description'] = t('All roles for this text format must be enabled and cannot be changed.');
  }
  if (!empty($format->format)) {
    // If editing an existing text format, pre-select its current permissions.
    $form['roles']['#default_value'] = array_keys(filter_get_roles_by_format($format));
  }
  elseif ($admin_role = variable_get('user_admin_role', 0)) {
    // If adding a new text format and the site has an administrative role,
    // pre-select that role so as to grant administrators access to the new
    // text format permission by default.
    $form['roles']['#default_value'] = array($admin_role);
  }

  // Retrieve available filters and load all configured filters for existing
  // text formats.
  $filter_info = filter_get_filters();
  $filters = !empty($format->format) ? filter_list_format($format->format) : array();

  // Prepare filters for form sections.
  foreach ($filter_info as $name => $filter) {
    // Create an empty filter object for new/unconfigured filters.
    if (!isset($filters[$name])) {
      $filters[$name] = new stdClass();
      $filters[$name]->status = 0;
      $filters[$name]->weight = $filter['weight'];
      $filters[$name]->settings = array();
    }
  }
  $form['#filters'] = $filters;

  // Filter status.
  $form['filters']['status'] = array(
    '#type' => 'item',
    '#title' => t('Enabled filters'),
    '#prefix' => '<div id="filters-status-wrapper">',
    '#suffix' => '</div>',
  );
  foreach ($filter_info as $name => $filter) {
    $form['filters']['status'][$name] = array(
      '#type' => 'checkbox',
      '#title' => $filter['title'],
      '#default_value' => $filters[$name]->status,
      '#parents' => array('filters', $name, 'status'),
      '#description' => $filter['description'],
      '#weight' => $filter['weight'],
    );
  }

  // Filter order (tabledrag).
  $form['filters']['order'] = array(
    '#type' => 'item',
    '#title' => t('Filter processing order'),
    '#theme' => 'filter_admin_format_filter_order',
  );
  foreach ($filter_info as $name => $filter) {
    $form['filters']['order'][$name]['filter'] = array(
      '#markup' => $filter['title'],
    );
    $form['filters']['order'][$name]['weight'] = array(
      '#type' => 'weight',
      '#delta' => 50,
      '#default_value' => $filters[$name]->weight,
      '#parents' => array('filters', $name, 'weight'),
    );
    $form['filters']['order'][$name]['#weight'] = $filters[$name]->weight;
  }

  // Filter settings.
  $form['filter_settings_title'] = array(
    '#type' => 'item',
    '#title' => t('Filter settings'),
  );
  $form['filter_settings'] = array(
    '#type' => 'vertical_tabs',
  );

  foreach ($filter_info as $name => $filter) {
    if (isset($filter['settings callback']) && function_exists($filter['settings callback'])) {
      $function = $filter['settings callback'];
      // Pass along stored filter settings and default settings, but also the
      // format object and all filters to allow for complex implementations.
      $defaults = (isset($filter['default settings']) ? $filter['default settings'] : array());
      $settings_form = $function($form, $form_state, $filters[$name], $format, $defaults, $filters);
      if (!empty($settings_form)) {
        $form['filters']['settings'][$name] = array(
          '#type' => 'fieldset',
          '#title' => $filter['title'],
          '#parents' => array('filters', $name, 'settings'),
          '#weight' => $filter['weight'],
          '#group' => 'filter_settings',
        );
        $form['filters']['settings'][$name] += $settings_form;
      }
    }
  }

  if (!empty($format->format)) {
    $form['format'] = array('#type' => 'value', '#value' => $format->format);
  }
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save configuration'));

  return $form;
}

/**
 * Returns HTML for a text format's filter order form.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_filter_admin_format_filter_order($variables) {
  $element = $variables['element'];

  // Filter order (tabledrag).
  $rows = array();
  foreach (element_children($element, TRUE) as $name) {
    $element[$name]['weight']['#attributes']['class'][] = 'filter-order-weight';
    $rows[] = array(
      'data' => array(
        drupal_render($element[$name]['filter']),
        drupal_render($element[$name]['weight']),
      ),
      'class' => array('draggable'),
    );
  }
  $output = drupal_render_children($element);
  $output .= theme('table', array('rows' => $rows, 'attributes' => array('id' => 'filter-order')));
  drupal_add_tabledrag('filter-order', 'order', 'sibling', 'filter-order-weight', NULL, NULL, TRUE);

  return $output;
}

/**
 * Validate text format form submissions.
 */
function filter_admin_format_form_validate($form, &$form_state) {
  if (!isset($form_state['values']['format'])) {
    $format_name = trim($form_state['values']['name']);
    form_set_value($form['name'], $format_name, $form_state);
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
  // Remove unnecessary values.
  form_state_values_clean($form_state);

  // Save text format.
  $format = (object) $form_state['values'];
  if (!isset($form_state['values']['format'])) {
    $format->format = NULL;
  }
  $status = filter_format_save($format);

  // Save user permissions.
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

  $fallback_options = array();
  foreach (filter_formats() as $id => $fallback_format) {
    if ($id != $format->format) {
      $fallback_options[$id] = $fallback_format->name;
    }
  }
  $form['fallback'] = array(
    '#type' => 'select',
    '#title' => t('Replacement text format'),
    '#options' => $fallback_options,
    '#default_value' => filter_fallback_format(),
    '#description' => t('Content assigned to the deleted text format will be reassigned to the chosen one.'),
  );

  return confirm_form($form,
    t('Are you sure you want to delete the text format %format?', array('%format' => $format->name)),
    'admin/config/content/formats',
    NULL,
    t('Delete')
  );
}

/**
 * Process filter delete form submission.
 */
function filter_admin_delete_submit($form, &$form_state) {
  $format = $form['#format'];
  filter_format_delete($format, $form_state['values']['fallback']);
  drupal_set_message(t('Deleted text format %format.', array('%format' => $format->name)));

  $form_state['redirect'] = 'admin/config/content/formats';
}

