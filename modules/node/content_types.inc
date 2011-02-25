<?php

/**
 * @file
 * Content type editing UI.
 */

/**
 * Displays the content type admin overview page.
 */
function node_overview_types() {
  $types = node_get_types();
  $names = node_get_types('names');
  $header = array(t('Name'), t('Type'), t('Description'), array('data' => t('Operations'), 'colspan' => '2'));
  $rows = array();

  foreach ($names as $key => $name) {
    $type = $types[$key];
    if (node_hook($type, 'form')) {
      $type_url_str = str_replace('_', '-', $type->type);
      $row = array(
        l($name, 'admin/content/node-type/'. $type_url_str),
        check_plain($type->type),
        filter_xss_admin($type->description),
      );
      // Set the edit column.
      $row[] = array('data' => l(t('edit'), 'admin/content/node-type/'. $type_url_str));

      // Set the delete column.
      if ($type->custom) {
        $row[] = array('data' => l(t('delete'), 'admin/content/node-type/'. $type_url_str .'/delete'));
      }
      else {
        $row[] = array('data' => '');
      }
      $rows[] = $row;
    }
  }

  if (empty($rows)) {
    $rows[] = array(array('data' => t('No content types available.'), 'colspan' => '5', 'class' => 'message'));
  }

  return theme('table', $header, $rows);
}

/**
 * Generates the node type editing form.
 */
function node_type_form(&$form_state, $type = NULL) {
  if (!isset($type->type)) {
    $type = new stdClass();
    $type->type = $type->name = $type->module = $type->description = $type->help = '';
    $type->min_word_count = 0;
    $type->has_title = TRUE;
    $type->has_body = TRUE;
    $type->title_label = t('Title');
    $type->body_label = t('Body');
    $type->custom = TRUE;
    $type->modified = FALSE;
    $type->locked = FALSE;
  }

  $form['#node_type'] = $type; // Make the type object available to implementations of hook_form_alter.

  $form['identity'] = array(
    '#type' => 'fieldset',
    '#title' => t('Identification'),
  );
  $form['identity']['name'] = array(
    '#title' => t('Name'),
    '#type' => 'textfield',
    '#default_value' => $type->name,
    '#description' => t('The human-readable name of this content type. This text will be displayed as part of the list on the <em>create content</em> page. It is recommended that this name begin with a capital letter and contain only letters, numbers, and <strong>spaces</strong>. This name must be unique.'),
    '#required' => TRUE,
  );

  if (!$type->locked) {
    $form['identity']['type'] = array(
      '#title' => t('Type'),
      '#type' => 'textfield',
      '#default_value' => $type->type,
      '#maxlength' => 32,
      '#required' => TRUE,
      '#description' => t('The machine-readable name of this content type. This text will be used for constructing the URL of the <em>create content</em> page for this content type. This name must contain only lowercase letters, numbers, and underscores. Underscores will be converted into hyphens when constructing the URL of the <em>create content</em> page. This name must be unique.'),
    );
  }
  else {
    $form['identity']['type'] = array(
      '#type' => 'value',
      '#value' => $type->type,
    );
    $form['identity']['type_display'] = array(
      '#title' => t('Type'),
      '#type' => 'item',
      '#value' => theme('placeholder', $type->type),
      '#description' => t('The machine-readable name of this content type. This field cannot be modified for system-defined content types.'),
    );
  }

  $form['identity']['description'] = array(
    '#title' => t('Description'),
    '#type' => 'textarea',
    '#default_value' => $type->description,
    '#description' => t('A brief description of this content type. This text will be displayed as part of the list on the <em>create content</em> page.'),
    );

  $form['submission'] = array(
    '#type' => 'fieldset',
    '#title' => t('Submission form settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['submission']['title_label'] = array(
    '#title' => t('Title field label'),
    '#type' => 'textfield',
    '#default_value' => $type->title_label,
    '#required' => TRUE,
  );
  if (!$type->has_title) {
    // Avoid overwriting a content type that intentionally does not have a
    // title field.
    $form['submission']['title_label']['#attributes'] = array('disabled' => 'disabled');
    $form['submission']['title_label']['#description'] = t('This content type does not have a title field.');
    $form['submission']['title_label']['#required'] = FALSE;
  }
  $form['submission']['body_label'] = array(
    '#title' => t('Body field label'),
    '#type' => 'textfield',
    '#default_value' => isset($type->body_label) ? $type->body_label : '',
    '#description' => t('To omit the body field for this content type, remove any text and leave this field blank.'),
  );
  $form['submission']['min_word_count'] = array(
    '#type' => 'select',
    '#title' => t('Minimum number of words'),
    '#default_value' => $type->min_word_count,
    '#options' => drupal_map_assoc(array(0, 1, 10, 25, 50, 75, 100, 125, 150, 175, 200)),
    '#description' => t('The minimum number of words for the body field to be considered valid for this content type. This can be useful to rule out submissions that do not meet the site\'s standards, such as short test posts.')
  );
  $form['submission']['help']  = array(
    '#type' => 'textarea',
    '#title' => t('Explanation or submission guidelines'),
    '#default_value' => $type->help,
    '#description' => t('This text will be displayed at the top of the submission form for this content type. It is useful for helping or instructing your users.')
  );
  $form['workflow'] = array(
    '#type' => 'fieldset',
    '#title' => t('Workflow settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['workflow']['node_options'] = array('#type' => 'checkboxes',
    '#title' => t('Default options'),
    '#default_value' => variable_get('node_options_'. $type->type, array('status', 'promote')),
    '#options' => array(
      'status' => t('Published'),
      'promote' => t('Promoted to front page'),
      'sticky' => t('Sticky at top of lists'),
      'revision' => t('Create new revision'),
    ),
    '#description' => t('Users with the <em>administer nodes</em> permission will be able to override these options.'),
  );

  $form['old_type'] = array(
    '#type' => 'value',
    '#value' => $type->type,
  );
  $form['orig_type'] = array(
    '#type' => 'value',
    '#value' => isset($type->orig_type) ? $type->orig_type : '',
  );
  $form['module'] = array(
    '#type' => 'value',
    '#value' => $type->module,
  );
  $form['custom'] = array(
    '#type' => 'value',
    '#value' => $type->custom,
  );
  $form['modified'] = array(
    '#type' => 'value',
    '#value' => $type->modified,
  );
  $form['locked'] = array(
    '#type' => 'value',
    '#value' => $type->locked,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save content type'),
    '#weight' => 40,
  );

  if ($type->custom) {
    if (!empty($type->type)) {
      $form['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete content type'),
        '#weight' => 45,
      );
    }
  }
  else {
    $form['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset to defaults'),
      '#weight' => 50,
    );
  }

  return $form;
}

/**
 * Validates the content type submission form generated by node_type_form().
 */
function node_type_form_validate($form, &$form_state) {
  $type = new stdClass();
  $type->type = trim($form_state['values']['type']);
  $type->name = trim($form_state['values']['name']);

  // Work out what the type was before the user submitted this form
  $old_type = trim($form_state['values']['old_type']);

  $types = node_get_types('names');

  if (!$form_state['values']['locked']) {
    if (isset($types[$type->type]) && $type->type != $old_type) {
      form_set_error('type', t('The machine-readable name %type is already taken.', array('%type' => $type->type)));
    }
    if (!preg_match('!^[a-z0-9_]+$!', $type->type)) {
      form_set_error('type', t('The machine-readable name must contain only lowercase letters, numbers, and underscores.'));
    }
    // 'theme' conflicts with theme_node_form().
    // '0' is invalid, since elsewhere we check it using empty().
    if (in_array($type->type, array('0', 'theme'))) {
      form_set_error('type', t("Invalid machine-readable name. Please enter a name other than %invalid.", array('%invalid' => $type->type)));
    }
  }

  $names = array_flip($types);

  if (isset($names[$type->name]) && $names[$type->name] != $old_type) {
    form_set_error('name', t('The human-readable name %name is already taken.', array('%name' => $type->name)));
  }
}

/**
 * Implementation of hook_form_submit().
 */
function node_type_form_submit($form, &$form_state) {
  $op = isset($form_state['values']['op']) ? $form_state['values']['op'] : '';

  $type = new stdClass();

  $type->type = trim($form_state['values']['type']);
  $type->name = trim($form_state['values']['name']);
  $type->orig_type = trim($form_state['values']['orig_type']);
  $type->old_type = isset($form_state['values']['old_type']) ? $form_state['values']['old_type'] : $type->type;

  $type->description = $form_state['values']['description'];
  $type->help = $form_state['values']['help'];
  $type->min_word_count = $form_state['values']['min_word_count'];
  $type->title_label = $form_state['values']['title_label'];
  $type->body_label = $form_state['values']['body_label'];

  // title_label is required in core; has_title will always be true, unless a
  // module alters the title field.
  $type->has_title = ($type->title_label != '');
  $type->has_body = ($type->body_label != '');

  $type->module = !empty($form_state['values']['module']) ? $form_state['values']['module'] : 'node';
  $type->custom = $form_state['values']['custom'];
  $type->modified = TRUE;
  $type->locked = $form_state['values']['locked'];

  if ($op == t('Reset to defaults')) {
    node_type_reset($type);
  }
  elseif ($op == t('Delete content type')) {
    $form_state['redirect'] = 'admin/content/node-type/'. str_replace('_', '-', $type->old_type) .'/delete';
    return;
  }

  $status = node_type_save($type);

  $variables = $form_state['values'];

  // Remove everything that's been saved already - whatever's left is assumed
  // to be a persistent variable.
  foreach ($variables as $key => $value) {
    if (isset($type->$key)) {
      unset($variables[$key]);
    }
  }

  unset($variables['form_token'], $variables['op'], $variables['submit'], $variables['delete'], $variables['reset'], $variables['form_id']);

  // Save or reset persistent variable values.
  foreach ($variables as $key => $value) {
    $variable_new = $key .'_'. $type->type;
    $variable_old = $key .'_'. $type->old_type;

    if ($op == t('Reset to defaults')) {
      variable_del($variable_old);
    }
    else {
      if (is_array($value)) {
        $value = array_keys(array_filter($value));
      }
      variable_set($variable_new, $value);

      if ($variable_new != $variable_old) {
        variable_del($variable_old);
      }
    }
  }

  node_types_rebuild();
  menu_rebuild();
  $t_args = array('%name' => $type->name);

  if ($op == t('Reset to defaults')) {
    drupal_set_message(t('The content type %name has been reset to its default values.', $t_args));
    return;
  }

  if ($status == SAVED_UPDATED) {
    drupal_set_message(t('The content type %name has been updated.', $t_args));
  }
  elseif ($status == SAVED_NEW) {
    drupal_set_message(t('The content type %name has been added.', $t_args));
    watchdog('node', 'Added content type %name.', $t_args, WATCHDOG_NOTICE, l(t('view'), 'admin/content/types'));
  }

  $form_state['redirect'] = 'admin/content/types';
  return;
}

/**
 * Implementation of hook_node_type().
 */
function node_node_type($op, $info) {
  if ($op != 'delete' && !empty($info->old_type) && $info->old_type != $info->type) {
    $update_count = node_type_update_nodes($info->old_type, $info->type);

    if ($update_count) {
      drupal_set_message(format_plural($update_count, 'Changed the content type of 1 post from %old-type to %type.', 'Changed the content type of @count posts from %old-type to %type.', array('%old-type' => $info->old_type, '%type' => $info->type)));
    }
  }
}

/**
 * Resets all of the relevant fields of a module-defined node type to their
 * default values.
 *
 * @param &$type
 *   The node type to reset. The node type is passed back by reference with its
 *   resetted values. If there is no module-defined info for this node type,
 *   then nothing happens.
 */
function node_type_reset(&$type) {
  $info_array = module_invoke_all('node_info');
  if (isset($info_array[$type->orig_type])) {
    $info_array[$type->orig_type]['type'] = $type->orig_type; 
    $info = _node_type_set_defaults($info_array[$type->orig_type]);

    foreach ($info as $field => $value) {
      $type->$field = $value;
    }
  }
}

/**
 * Menu callback; delete a single content type.
 */
function node_type_delete_confirm(&$form_state, $type) {
  $form['type'] = array('#type' => 'value', '#value' => $type->type);
  $form['name'] = array('#type' => 'value', '#value' => $type->name);

  $message = t('Are you sure you want to delete the content type %type?', array('%type' => $type->name));
  $caption = '';

  $num_nodes = db_result(db_query("SELECT COUNT(*) FROM {node} WHERE type = '%s'", $type->type));
  if ($num_nodes) {
    $caption .= '<p>'. format_plural($num_nodes, '<strong>Warning:</strong> there is currently 1 %type post on your site. It may not be able to be displayed or edited correctly, once you have removed this content type.', '<strong>Warning:</strong> there are currently @count %type posts on your site. They may not be able to be displayed or edited correctly, once you have removed this content type.', array('%type' => $type->name)) .'</p>';
  }

  $caption .= '<p>'. t('This action cannot be undone.') .'</p>';

  return confirm_form($form, $message, 'admin/content/types', $caption, t('Delete'));
}

/**
 * Process content type delete confirm submissions.
 */
function node_type_delete_confirm_submit($form, &$form_state) {
  node_type_delete($form_state['values']['type']);

  $t_args = array('%name' => $form_state['values']['name']);
  drupal_set_message(t('The content type %name has been deleted.', $t_args));
  watchdog('menu', 'Deleted content type %name.', $t_args, WATCHDOG_NOTICE);

  node_types_rebuild();
  menu_rebuild();

  $form_state['redirect'] = 'admin/content/types';
  return;
}
