<?php

/**
 * @file
 * Administrative page callbacks for the Filter module.
 */

/**
 * Page callback: Form constructor for a form to list and reorder text formats.
 *
 * @see filter_menu()
 * @see filter_admin_overview_submit()
 * @ingroup forms
 */
function filter_admin_overview($form) {
  // Overview of all formats.
  $formats = filter_formats();
  $fallback_format = filter_fallback_format();

  $form['#tree'] = TRUE;
  $form['formats'] = array(
    '#type' => 'table',
    '#header' => array(t('Name'), t('Roles'), t('Weight'), t('Operations')),
    '#tabledrag' => array(
      array('order', 'sibling', 'text-format-order-weight'),
    ),
  );
  foreach ($formats as $id => $format) {
    $form['formats'][$id]['#attributes']['class'][] = 'draggable';
    $form['formats'][$id]['#weight'] = $format->weight;

    $links = array();
    $links['configure'] = array(
      'title' => t('configure'),
      'href' => "admin/config/content/formats/$id",
    );
    // Check whether this is the fallback text format. This format is available
    // to all roles and cannot be disabled via the admin interface.
    $form['formats'][$id]['#is_fallback'] = ($id == $fallback_format);
    if ($form['formats'][$id]['#is_fallback']) {
      $form['formats'][$id]['name'] = array('#markup' => drupal_placeholder($format->name));
      if (config('filter.settings')->get('always_show_fallback_choice')) {
        $roles_markup = drupal_placeholder(t('All roles may use this format'));
      }
      else {
        $roles_markup = drupal_placeholder(t('This format is shown when no other formats are available'));
      }
    }
    else {
      $form['formats'][$id]['name'] = array('#markup' => check_plain($format->name));
      $roles = array_map('check_plain', filter_get_roles_by_format($format));
      $roles_markup = $roles ? implode(', ', $roles) : t('No roles may use this format');
      $links['disable'] = array(
        'title' => t('disable'),
        'href' => "admin/config/content/formats/$id/disable",
      );
    }

    $form['formats'][$id]['roles'] = array('#markup' => $roles_markup);

    $form['formats'][$id]['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight for @title', array('@title' => $format->name)),
      '#title_display' => 'invisible',
      '#default_value' => $format->weight,
      '#attributes' => array('class' => array('text-format-order-weight')),
    );

    $form['formats'][$id]['operations'] = array(
      '#type' => 'operations',
      '#links' => $links,
    );
  }
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save changes'));
  return $form;
}

/**
 * Form submission handler for filter_admin_overview().
 */
function filter_admin_overview_submit($form, &$form_state) {
  $filter_formats = filter_formats();
  foreach ($form_state['values']['formats'] as $id => $data) {
    // Only update if this is a form element with weight.
    if (is_array($data) && isset($data['weight'])) {
      $filter_formats[$id]->set('weight', $data['weight']);
      $filter_formats[$id]->save();
    }
  }
  filter_formats_reset();
  drupal_set_message(t('The text format ordering has been saved.'));
}

/**
 * Page callback: Displays the text format add/edit form.
 *
 * @param object|null $format
 *   (optional) An object representing a format, with the following properties:
 *   - format: A machine-readable name representing the ID of the text format
 *     to save. If this corresponds to an existing text format, that format
 *     will be updated; otherwise, a new format will be created.
 *   - name: The title of the text format.
 *   - cache: An integer indicating whether the text format is cacheable (1) or
 *     not (0). Defaults to 1.
 *   - status: (optional) An integer indicating whether the text format is
 *     enabled (1) or not (0). Defaults to 1.
 *   - weight: (optional) The weight of the text format, which controls its
 *     placement in text format lists. If omitted, the weight is set to 0.
 *     Defaults to NULL.
 *
 * @return
 *   A form array.
 *
 * @see filter_menu()
 */
function filter_admin_format_page($format = NULL) {
  if (!isset($format->name)) {
    drupal_set_title(t('Add text format'));

    $format = entity_create('filter_format', array());
  }
  return drupal_get_form('filter_admin_format_form', $format);
}

/**
 * Form constructor for the text format add/edit form.
 *
 * @param $format
 *   A format object having the properties:
 *   - format: A machine-readable name representing the ID of the text format to
 *     save. If this corresponds to an existing text format, that format will be
 *     updated; otherwise, a new format will be created.
 *   - name: The title of the text format.
 *   - cache: (optional) An integer indicating whether the text format is
 *     cacheable (1) or not (0). Defaults to 1.
 *   - status: (optional) An integer indicating whether the text format is
 *     enabled (1) or not (0). Defaults to 1.
 *   - weight: (optional) The weight of the text format, which controls its
 *     placement in text format lists. If omitted, the weight is set to 0.
 *
 * @see filter_admin_format_form_validate()
 * @see filter_admin_format_form_submit()
 * @ingroup forms
 */
function filter_admin_format_form($form, &$form_state, $format) {
  $is_fallback = ($format->format == filter_fallback_format());

  $form['#format'] = $format;
  $form['#tree'] = TRUE;
  $form['#attached']['library'][] = array('filter', 'drupal.filter.admin');

  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $format->name,
    '#required' => TRUE,
    '#weight' => -30,
  );
  $form['format'] = array(
    '#type' => 'machine_name',
    '#required' => TRUE,
    '#default_value' => $format->format,
    '#maxlength' => 255,
    '#machine_name' => array(
      'exists' => 'filter_format_exists',
      'source' => array('name'),
    ),
    '#disabled' => !empty($format->format),
    '#weight' => -20,
  );
  // @todo Remove once moved to FilterFormatFormController.
  $form['langcode'] = array(
    '#type' => 'value',
    '#value' => !$format->isNew() ? $format->langcode : language_default()->langcode,
  );

  // Add user role access selection.
  $form['roles'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Roles'),
    '#options' => array_map('check_plain', user_role_names()),
    '#disabled' => $is_fallback,
    '#weight' => -10,
  );
  if ($is_fallback) {
    $form['roles']['#description'] = t('All roles for this text format must be enabled and cannot be changed.');
  }
  if (!empty($format->format)) {
    // If editing an existing text format, pre-select its current permissions.
    $form['roles']['#default_value'] = array_keys(filter_get_roles_by_format($format));
  }
  elseif ($admin_role = config('user.settings')->get('admin_role')) {
    // If adding a new text format and the site has an administrative role,
    // pre-select that role so as to grant administrators access to the new
    // text format permission by default.
    $form['roles']['#default_value'] = array($admin_role);
  }

  // Create filter plugin instances for all available filters, including both
  // enabled/configured ones as well as new and not yet unconfigured ones.
  $filters = $format->filters()->sort();

  // Filter status.
  $form['filters']['status'] = array(
    '#type' => 'item',
    '#title' => t('Enabled filters'),
    '#prefix' => '<div id="filters-status-wrapper">',
    '#suffix' => '</div>',
    // This item is used as a pure wrapping container with heading. Ignore its
    // value, since 'filters' should only contain filter definitions.
    // @see http://drupal.org/node/1829202
    '#input' => FALSE,
  );
  // Filter order (tabledrag).
  $form['filters']['order'] = array(
    '#type' => 'table',
    // For filter.admin.js
    '#attributes' => array('id' => 'filter-order'),
    '#title' => t('Filter processing order'),
    '#tabledrag' => array(
      array('order', 'sibling', 'filter-order-weight'),
    ),
    '#tree' => FALSE,
    '#input' => FALSE,
    '#theme_wrappers' => array('form_element'),
  );
  // Filter settings.
  $form['filter_settings'] = array(
    '#type' => 'vertical_tabs',
    '#title' => t('Filter settings'),
  );

  foreach ($filters as $name => $filter) {
    $form['filters']['status'][$name] = array(
      '#type' => 'checkbox',
      '#title' => $filter->getLabel(),
      '#default_value' => $filter->status,
      '#parents' => array('filters', $name, 'status'),
      '#description' => $filter->getDescription(),
      '#weight' => $filter->weight,
    );

    $form['filters']['order'][$name]['#attributes']['class'][] = 'draggable';
    $form['filters']['order'][$name]['#weight'] = $filter->weight;
    $form['filters']['order'][$name]['filter'] = array(
      '#markup' => $filter->getLabel(),
    );
    $form['filters']['order'][$name]['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight for @title', array('@title' => $filter->getLabel())),
      '#title_display' => 'invisible',
      '#delta' => 50,
      '#default_value' => $filter->weight,
      '#parents' => array('filters', $name, 'weight'),
      '#attributes' => array('class' => array('filter-order-weight')),
    );

    // Retrieve the settings form of the filter plugin. The plugin should not be
    // aware of the text format. Therefore, it only receives a set of minimal
    // base properties to allow advanced implementations to work.
    $settings_form = array(
      '#parents' => array('filters', $name, 'settings'),
      '#tree' => TRUE,
    );
    $settings_form = $filter->settingsForm($settings_form, $form_state);
    if (!empty($settings_form)) {
      $form['filters']['settings'][$name] = array(
        '#type' => 'details',
        '#title' => $filter->getLabel(),
        '#weight' => $filter->weight,
        '#parents' => array('filters', $name, 'settings'),
        '#group' => 'filter_settings',
      );
      $form['filters']['settings'][$name] += $settings_form;
    }
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save configuration'));

  return $form;
}

/**
 * Form validation handler for filter_admin_format_form().
 *
 * @see filter_admin_format_form_submit()
 */
function filter_admin_format_form_validate($form, &$form_state) {
  $format_format = trim($form_state['values']['format']);
  $format_name = trim($form_state['values']['name']);

  // Ensure that the values to be saved later are exactly the ones validated.
  form_set_value($form['format'], $format_format, $form_state);
  form_set_value($form['name'], $format_name, $form_state);

  $filter_formats = entity_load_multiple('filter_format');
  foreach ($filter_formats as $format) {
    if ($format->name == $format_name && $format->format != $format_format) {
      form_set_error('name', t('Text format names must be unique. A format named %name already exists.', array('%name' => $format_name)));
      break;
    }
  }
}

/**
 * Form submission handler for filter_admin_format_form().
 *
 * @see filter_admin_format_form_validate()
 */
function filter_admin_format_form_submit($form, &$form_state) {
  // Remove unnecessary values.
  form_state_values_clean($form_state);

  // Add the submitted form values to the text format, and save it.
  $format = $form['#format'];
  foreach ($form_state['values'] as $key => $value) {
    if ($key != 'filters') {
      $format->set($key, $value);
    }
    else {
      foreach ($value as $instance_id => $config) {
        $format->setFilterConfig($instance_id, $config);
      }
    }
  }
  $status = $format->save();

  // Save user permissions.
  if ($permission = filter_permission_name($format)) {
    foreach ($form_state['values']['roles'] as $rid => $enabled) {
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

  $form_state['redirect'] = 'admin/config/content/formats';
}
