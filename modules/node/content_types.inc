<?php
// $Id: content_types.inc,v 1.9 2006/09/10 07:36:37 dries Exp $

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
    if (function_exists($type->module .'_form')) {
      $name = check_plain($name);
      $type_url_str = str_replace('_', '-', $type->type);
      // Populate the operations field.
      $operations = array();

      // Set the edit column.
      $operations[] = array('data' => l(t('edit'), 'admin/content/types/'. $type_url_str));

      // Set the delete column.
      if ($type->custom) {
        $operations[] = array('data' => l(t('delete'), 'admin/content/types/'. $type_url_str .'/delete'));
      }
      else {
        $operations[] = array('data' => '');
      }

      $row = array(array('data' => l($name, 'admin/content/types/'. $type_url_str), 'class' => $class), array('data' => check_plain($type->type), 'class' => $class), array('data' => check_plain($type->description), 'class' => $class));
      foreach ($operations as $operation) {
        $operation['class'] = $class;
        $row[] = $operation;
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
function node_type_form($type = NULL) {
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

  $form['identity'] = array(
    '#type' => 'fieldset',
    '#title' => t('Identification'),
  );
  $form['identity']['name'] = array(
    '#title' => t('Name'),
    '#type' => 'textfield',
    '#default_value' => $type->name,
    '#description' => t('The human-readable name of this content type. This text will be displayed as part of the list on the <em>create content</em> page. It is recommended that this name consists only of lowercase letters, numbers, and <strong>spaces</strong>. The name must be unique to this content type.'),
    '#required' => TRUE,
  );

  if (!$type->locked) {
    $form['identity']['type'] = array(
      '#title' => t('Type'),
      '#type' => 'textfield',
      '#default_value' => $type->type,
      '#maxlength' => 32,
      '#required' => TRUE,
      '#description' => t('The machine-readable name of this content type. This text will be used for constructing the URL of the <em>create content</em> page for this content type. It is recommended that this name consists only of lowercase letters, numbers, and <strong>underscores</strong>. Dashes are not allowed. Underscores will be converted into dashes when constructing the URL of the <em>create content</em> page. The name must be unique to this content type.'),
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

  $form['submission'] = array(
    '#type' => 'fieldset',
    '#title' =>t('Submission form'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  if ($type->has_title) {
    $form['submission']['title_label'] = array(
      '#title' => t('Title field label'),
      '#type' => 'textfield',
      '#default_value' => $type->title_label,
      '#description' => t('The label for the title field of this content type.'),
      '#required' => TRUE,
    );
  }
  if ($type->has_body) {
    $form['submission']['body_label'] = array(
      '#title' => t('Body field label'),
      '#type' => 'textfield',
      '#default_value' => $type->body_label,
      '#description' => t('The label for the body field of this content type.'),
      '#required' => TRUE,
    );
  }
  $form['submission']['description'] = array(
    '#title' => t('Description'),
    '#type' => 'textarea',
    '#default_value' => $type->description,
    '#description' => t('A brief description of this content type. This text will be displayed as part of the list on the <em>create content</em> page.'),
  );
  $form['submission']['help']  = array(
    '#type' => 'textarea',
    '#title' => t('Explanation or submission guidelines'),
    '#default_value' =>  $type->help,
    '#description' => t('This text will be displayed at the top of the submission form for this content type. It is useful for helping or instructing your users.')
  );
  $form['submission']['min_word_count'] = array(
    '#type' => 'select',
    '#title' => t('Minimum number of words'),
    '#default_value' => $type->min_word_count,
    '#options' => drupal_map_assoc(array(0, 10, 25, 50, 75, 100, 125, 150, 175, 200)),
    '#description' => t('The minimum number of words for the body field to be considered valid for this content type. This can be useful to rule out submissions that do not meet the site\'s standards, such as short test posts.')
  );
  $form['workflow'] = array(
    '#type' => 'fieldset',
    '#title' =>t('Workflow'),
    '#collapsible' => TRUE,
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
    '#value' => $type->orig_type,
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
  );

  if ($type->custom) {
    if (!empty($type->type)) {
      $form['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete content type'),
      );
    }
  }
  else {
    $form['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset to defaults'),
    );
  }

  return $form;
}

/**
 * Implementation of hook_form_validate().
 */
function node_type_form_validate($form_id, $form_values) {
  $type = new stdClass();
  $type->type = trim($form_values['type']);
  $type->name = trim($form_values['name']);

  // Work out what the type was before the user submitted this form
  $old_type = trim($form_values['old_type']);
  if (empty($old_type)) {
    $old_type = $type->type;
  }

  $types = node_get_types('names');

  if (!$form_values['locked']) {
    if (isset($types[$type->type]) && $type->type != $old_type) {
      form_set_error('type', t('The machine-readable name %type is already taken.', array('%type' => $type->type)));
    }
    if (strpos($type->type, '-') !== FALSE) {
      form_set_error('type', t('The machine-readable name cannot contain dashes.', array('%type' => $type->type)));
    }
  }

  $names = array_flip($types);

  if (isset($names[$type->name]) && $names[$type->name] != $old_type) {
    form_set_error('name', t('The human-readable name %name is already taken.', array('%name' => $names[$type->name])));
    break;
  }
}

/**
 * Implementation of hook_form_submit().
 */
function node_type_form_submit($form_id, $form_values) {
  $op = isset($form_values['op']) ? $form_values['op'] : '';

  $type = new stdClass();

  $type->type = trim($form_values['type']);
  $type->name = trim($form_values['name']);
  $type->orig_type = trim($form_values['orig_type']);
  $type->old_type = isset($form_values['old_type']) ? $form_values['old_type'] : $type->type;

  $type->description = $form_values['description'];
  $type->help = $form_values['help'];
  $type->min_word_count = $form_values['min_word_count'];
  $type->title_label = $form_values['title_label'];
  $type->body_label = $form_values['body_label'];

  $type->module = !empty($form_values['module']) ? $form_values['module'] : 'node';
  $type->has_title = $type->has_body = TRUE;
  $type->custom = $form_values['custom'];
  $type->modified = TRUE;
  $type->locked = $form_values['locked'];

  if ($op == t('Reset to defaults')) {
    node_type_reset($type);
  }
  elseif ($op == t('Delete content type')) {
    return 'admin/content/types/'. str_replace('_', '-', $type->old_type) .'/delete';
  }

  if (!empty($form_values['old_type'])) {
    if ($type->old_type != $type->type) {
      $update_count = node_type_update_nodes($type->old_type, $type->type);

      if ($update_count) {
        drupal_set_message(t('Changed the content type of %update_count %posts from %old_type to %type.', array('%update_count' => $update_count, '%posts' => format_plural($update_count, 'post', 'posts'), '%old_type' => $type->old_type, '%type' => $type->type)));
      }
    }
  }

  $status = node_type_save($type);

  // Remove everything that's been saved already - whatever's left is assumed
  // to be a persistent variable.
  foreach ($form_values as $key => $value) {
    if (isset($type->$key)) {
      unset($form_values[$key]);
    }
  }

  unset($form_values['type_display'], $form_values['old_type'], $form_values['orig_type'], $form_values['submit'], $form_values['delete'], $form_values['reset'], $form_values['form_id']);

  // Save or reset persistent variable values.
  foreach ($form_values as $key => $value) {
    $key .= '_'. $type->type;
    if ($op == t('Reset to defaults')) {
      variable_del($key);
    }
    else {
      if (is_array($value)) {
        $value = array_keys(array_filter($value));
      }
      variable_set($key, $value);

      if ($type->old_type != $type->type) {
        $key = str_replace($type->type, $type->old_type, $key);
        variable_del($key);
      }
    }
  }

  node_types_rebuild();
  // menu_rebuild clears the cache, too
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
    watchdog('node', t('Added content type %name.', $t_args), WATCHDOG_NOTICE, l(t('view'), 'admin/content/types'));
  }

  return 'admin/content/types';
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
  $info_array = module_invoke($type->module, 'node_info');
  if (isset($info_array[$type->orig_type])) {
    $info = _node_type_set_defaults($info_array[$type->orig_type]);
    $info['type'] = $type->orig_type;

    foreach ($info as $field => $value) {
      $type->$field = $value;
    }
  }
}

/**
 * Menu callback; delete a single content type.
 */
function node_type_delete_confirm($type) {
  $form['type'] = array('#type' => 'value', '#value' => $type->type);
  $form['name'] = array('#type' => 'value', '#value' => $type->name);

  $message = t('Are you sure you want to delete the content type %type?', array('%type' => $type->name));
  $caption = '';

  $num_nodes = db_num_rows(db_query("SELECT * FROM {node} WHERE type = '%s'", $type->type));
  if ($num_nodes) {
    $caption .= '<p>'. t('<strong>Warning:</strong> there %are currently %num_nodes %type %nodes on your site. %they may not be able to be displayed or edited correctly, once you have removed this content type.', array('%are' => format_plural($num_nodes, 'is', 'are'), '%num_nodes' => $num_nodes, '%type' => $type->name, '%nodes' => format_plural($num_nodes, 'node', 'nodes'), '%they' => format_plural($num_nodes, 'It', 'They'))) .'</p>';
  }

  $caption .= '<p>'. t('This action cannot be undone.') .'</p>';

  return confirm_form($form, $message, 'admin/content/types', $caption, t('Delete'));
}

/**
 * Process content type delete confirm submissions.
 */
function node_type_delete_confirm_submit($form_id, $form_values) {
  db_query("DELETE FROM {node_type} WHERE type = '%s'", $form_values['type']);

  $t_args = array('%name' => $form_values['name']);
  drupal_set_message(t('The content type %name has been deleted.', $t_args));
  watchdog('menu', t('Deleted content type %name.', $t_args), WATCHDOG_NOTICE);

  node_types_rebuild();
  menu_rebuild();

  return 'admin/content/types';
}

