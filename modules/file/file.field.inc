<?php
// $Id: file.field.inc,v 1.24 2010/03/11 22:44:44 dries Exp $

/**
 * @file
 * Field module functionality for the File module.
 */

/**
 * Implements hook_field_info().
 */
function file_field_info() {
  return array(
    'file' => array(
      'label' => t('File'),
      'description' => t('This field stores the ID of a file as an integer value.'),
      'settings' => array(
        'display_field' => 0,
        'display_default' => 0,
        'uri_scheme' => 'public',
      ),
      'instance_settings' => array(
        'file_extensions' => 'txt',
        'file_directory' => '',
        'max_filesize' => '',
        'description_field' => 0,
      ),
      'default_widget' => 'file_generic',
      'default_formatter' => 'file_default',
    ),
  );
}

/**
 * Implements hook_field_schema().
 */
function file_field_schema($field) {
  return array(
    'columns' => array(
      'fid' => array(
        'description' => 'The {files}.fid being referenced in this field.',
        'type' => 'int',
        'not null' => FALSE,
        'unsigned' => TRUE,
      ),
      'display' => array(
        'description' => 'Flag to control whether this file should be displayed when viewing content.',
        'type' => 'int',
        'size' => 'tiny',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 1,
      ),
      'description' => array(
        'description' => 'A description of the file.',
        'type' => 'text',
        'not null' => FALSE,
      ),
    ),
    'indexes' => array(
      'fid' => array('fid'),
    ),
  );
}

/**
 * Implements hook_field_settings_form().
 */
function file_field_settings_form($field, $instance, $has_data) {
  $defaults = field_info_field_settings($field['type']);
  $settings = array_merge($defaults, $field['settings']);

  $form['#attached']['js'][] = drupal_get_path('module', 'file') . '/file.js';

  $form['display_field'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable <em>Display</em> field'),
    '#default_value' => $settings['display_field'],
    '#description' => t('The display option allows users to choose if a file should be shown when viewing the content.'),
  );
  $form['display_default'] = array(
    '#type' => 'checkbox',
    '#title' => t('Files displayed by default'),
    '#default_value' => $settings['display_default'],
    '#description' => t('This setting only has an effect if the display option is enabled.'),
  );

  $scheme_options = array();
  foreach (file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE) as $scheme => $stream_wrapper) {
    $scheme_options[$scheme] = $stream_wrapper['name'];
  }
  $form['uri_scheme'] = array(
    '#type' => 'radios',
    '#title' => t('Upload destination'),
    '#options' => $scheme_options,
    '#default_value' => $settings['uri_scheme'],
    '#description' => t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
    '#disabled' => $has_data,
  );

  return $form;
}

/**
 * Implements hook_field_instance_settings_form().
 */
function file_field_instance_settings_form($field, $instance) {
  $settings = $instance['settings'];

  $form['file_directory'] = array(
    '#type' => 'textfield',
    '#title' => t('File directory'),
    '#default_value' => $settings['file_directory'],
    '#description' => t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.', array('%directory' => variable_get('file_directory_path', 'files') . '/')),
    '#element_validate' => array('_file_generic_settings_file_directory_validate'),
    '#weight' => 3,
  );

  // Make the extension list a little more human-friendly by comma-separation.
  $extensions = str_replace(' ', ', ', $settings['file_extensions']);
  $form['file_extensions'] = array(
    '#type' => 'textfield',
    '#title' => t('Allowed file extensions'),
    '#default_value' => $extensions,
    '#description' => t('Separate extensions with a space or comma and do not include the leading dot. Leaving this blank will allow users to upload a file with any extension.'),
    '#element_validate' => array('_file_generic_settings_extensions'),
    '#weight' => 1,
  );

  $form['max_filesize'] = array(
    '#type' => 'textfield',
    '#title' => t('Maximum upload size'),
    '#default_value' => $settings['max_filesize'],
    '#description' => t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', array('%limit' => format_size(file_upload_max_size()))),
    '#size' => 10,
    '#element_validate' => array('_file_generic_settings_max_filesize'),
    '#weight' => 5,
  );

  $form['description_field'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable <em>Description</em> field'),
    '#default_value' => isset($settings['description_field']) ? $settings['description_field'] : '',
    '#description' => t('The description field allows users to enter a description about the uploaded file.'),
    '#parents' => array('instance', 'settings', 'description_field'),
    '#weight' => 11,
  );

  return $form;
}

/**
 * Element validate callback for the maximum upload size field.
 *
 * Ensure a size that can be parsed by parse_size() has been entered.
 */
function _file_generic_settings_max_filesize($element, &$form_state) {
  if (!empty($element['#value']) && !is_numeric(parse_size($element['#value']))) {
    form_error($element, t('The "!name" option must contain a valid value. You may either leave the text field empty or enter a string like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes).', array('!name' => t($element['title']))));
  }
}

/**
 * Element validate callback for the allowed file extensions field.
 *
 * This doubles as a convenience clean-up function and a validation routine.
 * Commas are allowed by the end-user, but ultimately the value will be stored
 * as a space-separated list for compatibility with file_validate_extensions().
 */
function _file_generic_settings_extensions($element, &$form_state) {
  if (!empty($element['#value'])) {
    $extensions = preg_replace('/([, ]+\.?)/', ' ', trim(strtolower($element['#value'])));
    $extensions = array_filter(explode(' ', $extensions));
    $extensions = implode(' ', array_unique($extensions));
    if (!preg_match('/^([a-z0-9]+([.][a-z0-9])* ?)+$/', $extensions)) {
      form_error($element, t('The list of allowed extensions is not valid, be sure to exclude leading dots and to separate extensions with a comma or space.'));
    }
    else {
      form_set_value($element, $extensions, $form_state);
    }
  }
}

/**
 * Element validate callback for the file destination field.
 *
 * Remove slashes from the beginning and end of the destination value and ensure
 * that the file directory path is not included at the beginning of the value.
 */
function _file_generic_settings_file_directory_validate($element, &$form_state) {
  // Strip slashes from the beginning and end of $widget['file_directory'].
  $value = trim($element['#value'], '\\/');

  // Do not allow the file path to be the same as the file_directory_path().
  // This causes all sorts of problems with things like file_create_url().
  if (strpos($value, file_directory_path()) === 0) {
    form_error($element, t('The file directory (@file_directory) cannot start with the system files directory (@files_directory), as this may cause conflicts when building file URLs.', array('@file_directory' => $form_state['values']['file_directory'], '@files_directory' => file_directory_path())));
  }
  else {
    form_set_value($element, $value, $form_state);
  }
}

/**
 * Implements hook_field_load().
 */
function file_field_load($entity_type, $entities, $field, $instances, $langcode, &$items, $age) {
  foreach ($entities as $id => $entity) {
    // Load the files from the files table.
    $fids = array();
    foreach ($items[$id] as $delta => $item) {
      if (!empty($item['fid'])) {
        $fids[] = $item['fid'];
      }
    }
    $files = file_load_multiple($fids);

    foreach ($items[$id] as $delta => $item) {
      // If the file does not exist, mark the entire item as empty.
      if (empty($item['fid']) || !isset($files[$item['fid']])) {
        $items[$id][$delta] = NULL;
      }
      else {
        $items[$id][$delta] = array_merge($item, (array) $files[$item['fid']]);
      }
    }
  }
}

/**
 * Implements hook_field_prepare_view().
 */
function file_field_prepare_view($entity_type, $entities, $field, $instances, $langcode, &$items) {
  // Remove files specified to not be displayed.
  foreach ($entities as $id => $entity) {
    foreach ($items[$id] as $delta => $item) {
      if (!file_field_displayed($item, $field)) {
        unset($items[$id][$delta]);
      }
      // Ensure consecutive deltas.
      $items[$id] = array_values($items[$id]);
    }
  }
}

/**
 * Implements hook_field_presave().
 */
function file_field_presave($obj_type, $object, $field, $instance, $langcode, &$items) {
  // Make sure that each file which will be saved with this object has a
  // permanent status, so that it will not be removed when temporary files are
  // cleaned up.
  foreach ($items as $item) {
    $file = file_load($item['fid']);
    if (($file->status & FILE_STATUS_PERMANENT) == 0) {
      $file->status |= FILE_STATUS_PERMANENT;
      file_save($file);
    }
  }
}

/**
 * Implements hook_field_update().
 *
 * Check for files that have been removed from the object.
 */
function file_field_update($entity_type, $entity, $field, $instance, $langcode, &$items) {
  // On new revisions, old files are always maintained in the previous revision.
  if (!empty($entity->revision)) {
    return;
  }

  // Build a display of the current FIDs.
  $fids = array();
  foreach ($items as $item) {
    $fids[] = $item['fid'];
  }

  // Get the current values in the entity, and delete files for removed items.
  list($id) = entity_extract_ids($entity_type, $entity);
  $original = clone $entity;
  field_attach_load($entity_type, array($id => $original), FIELD_LOAD_CURRENT, array('field_id' => $field['id']));

  if (!empty($original->{$field['field_name']}[$langcode])) {
    foreach ($original->{$field['field_name']}[$langcode] as $original_item) {
      if (isset($original_item['fid']) && !in_array($original_item['fid'], $fids)) {
        // For hook_file_references, remember that this is being deleted.
        $original_item['file_field_name'] = $field['field_name'];
        // Delete the file if possible.
        file_field_delete_file($original_item, $field);
      }
    }
  }
}

/**
 * Implements hook_field_delete().
 */
function file_field_delete($entity_type, $entity, $field, $instance, $langcode, &$items) {
  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);
  foreach ($items as $delta => $item) {
    // For hook_file_references(), remember that this is being deleted.
    $item['file_field_name'] = $field['field_name'];
    // Pass in the ID of the object that is being removed so all references can
    // be counted in hook_file_references().
    $item['file_field_type'] = $entity_type;
    $item['file_field_id'] = $id;
    file_field_delete_file($item, $field);
  }
}

/**
 * Implements hook_field_delete_revision().
 */
function file_field_delete_revision($entity_type, $entity, $field, $instance, $langcode, &$items) {
  foreach ($items as $delta => $item) {
    // For hook_file_references, remember that this file is being deleted.
    $item['file_field_name'] = $field['field_name'];
    if (file_field_delete_file($item, $field)) {
      $items[$delta] = NULL;
    }
  }
}

/**
 * Check that File controls a file before attempting to delete it.
 */
function file_field_delete_file($item, $field) {
  // Remove the file_field_name and file_field_id properties so that references
  // can be counted including the files to be deleted.
  $field_name = isset($item['file_field_name']) ? $item['file_field_name'] : NULL;
  $field_id = isset($item['file_field_id']) ? $item['file_field_id'] : NULL;
  unset($item['file_field_name'], $item['file_field_id']);

  // To prevent the file field from deleting files it doesn't know about, check
  // the file reference count. Temporary files can be deleted because they
  // are not yet associated with any content at all.
  $file = (object) $item;
  if ($file->status == 0 || file_get_file_reference_count($file, $field) > 0) {
    $file->file_field_name = $field_name;
    $file->file_field_id = $field_id;
    return file_delete($file);
  }

  // Even if the file is not deleted, return TRUE to indicate the file field
  // record can be removed from the field database tables.
  return TRUE;
}

/**
 * Implements hook_field_is_empty().
 */
function file_field_is_empty($item, $field) {
  return empty($item['fid']);
}

/**
 * Determine whether a file should be displayed when outputting field content.
 *
 * @param $item
 *   A field item array.
 * @param $field
 *   A field array.
 * @return
 *   Boolean TRUE if the file will be displayed, FALSE if the file is hidden.
 */
function file_field_displayed($item, $field) {
  if (!empty($field['settings']['display_field'])) {
    return (bool) $item['display'];
  }
  return TRUE;
}

/**
 * Implements hook_field_formatter_info().
 */
function file_field_formatter_info() {
  return array(
    'file_default' => array(
      'label' => t('Generic file'),
      'field types' => array('file'),
    ),
    'file_table' => array(
      'label' => t('Table of files'),
      'field types' => array('file'),
    ),
    'file_url_plain' => array(
      'label' => t('URL to file'),
      'field types' => array('file'),
    ),
  );
}

/**
 * Implements hook_field_widget_info().
 */
function file_field_widget_info() {
  return array(
    'file_generic' => array(
      'label' => t('File'),
      'field types' => array('file'),
      'settings' => array(
        'progress_indicator' => 'throbber',
      ),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_CUSTOM,
        'default value' => FIELD_BEHAVIOR_NONE,
      ),
    ),
  );
}

/**
 * Implements hook_field_widget_settings_form().
 */
function file_field_widget_settings_form($field, $instance) {
  $widget = $instance['widget'];
  $settings = $widget['settings'];

  $form['progress_indicator'] = array(
    '#type' => 'radios',
    '#title' => t('Progress indicator'),
    '#options' => array(
      'throbber' => t('Throbber'),
      'bar' => t('Bar with progress meter'),
    ),
    '#default_value' => $settings['progress_indicator'],
    '#description' => t('The throbber display does not show the status of uploads but takes up space. The progress bar is helpful for monitoring progress on large uploads.'),
    '#weight' => 16,
    '#access' => file_progress_implementation(),
  );

  return $form;
}

/**
 * Implements hook_field_widget_form().
 */
function file_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  $form['#attributes'] = array('enctype' => 'multipart/form-data');

  $defaults = array(
    'fid' => 0,
    'display' => !empty($field['settings']['display_default']),
    'description' => '',
  );

  // Retrieve any values set in $form_state, as will be the case during AJAX
  // rebuilds of this form.
  if (isset($form_state['values'][$field['field_name']][$langcode])) {
    $items = $form_state['values'][$field['field_name']][$langcode];
    unset($form_state['values'][$field['field_name']][$langcode]);
  }

  foreach ($items as $delta => $item) {
    $items[$delta] = array_merge($defaults, $items[$delta]);
    // Remove any items from being displayed that are not needed.
    if ($items[$delta]['fid'] == 0) {
      unset($items[$delta]);
    }
  }

  // Re-index deltas after removing empty items.
  $items = array_values($items);

  // Update order according to weight.
  $items = _field_sort_items($field, $items);

  // Essentially we use the managed_file type, extended with some enhancements.
  $element_info = element_info('managed_file');
  $element += array(
    '#type' => 'managed_file',
    '#default_value' => isset($items[$delta]) ? $items[$delta] : $defaults,
    '#upload_location' => file_field_widget_uri($field, $instance),
    '#upload_validators' => file_field_widget_upload_validators($field, $instance),
    '#value_callback' => 'file_field_widget_value',
    '#process' => array_merge($element_info['#process'], array('file_field_widget_process')),
    // Allows this field to return an array instead of a single value.
    '#extended' => TRUE,
  );

  if ($field['cardinality'] == 1) {
    // If there's only one field, return it as delta 0.
    if (empty($element['#default_value']['fid'])) {
      $element['#description'] = theme('file_upload_help', array('description' => $element['#description'], 'upload_validators' => $element['#upload_validators']));
    }
    $elements = array($element);
  }
  else {
    // If there are multiple values, add an element for each existing one.
    $delta = -1;
    foreach ($items as $delta => $item) {
      $elements[$delta] = $element;
      $elements[$delta]['#default_value'] = $item;
      $elements[$delta]['#weight'] = $delta;
    }
    // And then add one more empty row for new uploads.
    $delta++;
    if ($field['cardinality'] == FIELD_CARDINALITY_UNLIMITED || $delta < $field['cardinality']) {
      $elements[$delta] = $element;
      $elements[$delta]['#default_value'] = $defaults;
      $elements[$delta]['#weight'] = $delta;
      $elements[$delta]['#required'] = ($element['#required'] && $delta == 0);
    }
    // The group of elements all-together need some extra functionality
    // after building up the full list (like draggable table rows).
    $elements['#file_upload_delta'] = $delta;
    $elements['#theme'] = 'file_widget_multiple';
    $elements['#theme_wrappers'] = array('fieldset');
    $elements['#process'] = array('file_field_widget_process_multiple');
    $elements['#title'] = $element['#title'];
    $elements['#description'] = $element['#description'];
    $elements['#field_name'] = $element['#field_name'];
    $elements['#language'] = $element['#language'];
    $elements['#display_field'] = $field['settings']['display_field'];

    // Add some properties that will eventually be added to the file upload
    // field. These are added here so that they may be referenced easily through
    // a hook_form_alter().
    $elements['#file_upload_title'] = t('Add a new file');
    $elements['#file_upload_description'] = theme('file_upload_help', array('description' => '', 'upload_validators' => $elements[0]['#upload_validators']));
  }

  return $elements;
}

/**
 * Get the upload validators for a file field.
 *
 * @param $field
 *   A field array.
 * @return
 *   An array suitable for passing to file_save_upload() or the file field
 *   element's '#upload_validators' property.
 */
function file_field_widget_upload_validators($field, $instance) {
  // Cap the upload size according to the PHP limit.
  $max_filesize = parse_size(file_upload_max_size());
  if (!empty($instance['settings']['max_filesize']) && parse_size($instance['settings']['max_filesize']) < $max_filesize) {
    $max_filesize = parse_size($instance['settings']['max_filesize']);
  }

  $validators = array();

  // There is always a file size limit due to the PHP server limit.
  $validators['file_validate_size'] = array($max_filesize);

  // Add the extension check if necessary.
  if (!empty($instance['settings']['file_extensions'])) {
    $validators['file_validate_extensions'] = array($instance['settings']['file_extensions']);
  }

  return $validators;
}

/**
 * Determine the URI for a file field instance.
 *
 * @param $field
 *   A field array.
 * @param $instance
 *   A field instance array.
 * @return
 *   A file directory URI with tokens replaced.
 */
function file_field_widget_uri($field, $instance, $account = NULL) {
  $destination = trim($instance['settings']['file_directory'], '/');

  // Replace tokens.
  $data = array('user' => isset($account) ? $account : $GLOBALS['user']);
  $destination = token_replace($destination, $data);

  return $field['settings']['uri_scheme'] . '://' . $destination;
}

/**
 * The #value_callback for the file_generic field element.
 */
function file_field_widget_value($element, $input = FALSE, $form_state) {
  if ($input) {
    // Checkboxes lose their value when empty.
    // If the display field is present make sure its unchecked value is saved.
    $field = $form_state['field'][$element['#field_name']][$element['#language']]['field'];
    if (empty($input['display'])) {
      $input['display'] = $field['settings']['display_field'] ? 0 : 1;
    }
  }

  // We depend on the managed file element to handle uploads.
  $return = file_managed_file_value($element, $input, $form_state);

  // Ensure that all the required properties are returned even if empty.
  $return += array(
    'fid' => 0,
    'display' => 1,
    'description' => '',
  );

  return $return;
}

/**
 * An element #process callback for the file_generic field type.
 *
 * Expands the file_generic type to include the description and display fields.
 */
function file_field_widget_process($element, &$form_state, $form) {
  $item = $element['#value'];
  $item['fid'] = $element['fid']['#value'];

  $field = $form_state['field'][$element['#field_name']][$element['#language']]['field'];
  $instance = $form_state['field'][$element['#field_name']][$element['#language']]['instance'];
  $settings = $instance['widget']['settings'];

  $element['#theme'] = 'file_widget';

  // Add the display field if enabled.
  if (!empty($field['settings']['display_field']) && $item['fid']) {
    $element['display'] = array(
      '#type' => empty($item['fid']) ? 'hidden' : 'checkbox',
      '#title' => t('Include file in display'),
      '#value' => isset($item['display']) ? $item['display'] : $field['settings']['display_default'],
      '#attributes' => array('class' => array('file-display')),
    );
  }
  else {
    $element['display'] = array(
      '#type' => 'hidden',
      '#value' => '1',
    );
  }

  // Add the description field if enabled.
  if (!empty($instance['settings']['description_field']) && $item['fid']) {
    $element['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#value' => isset($item['description']) ? $item['description'] : '',
      '#type' => variable_get('file_description_type', 'textfield'),
      '#maxlength' => variable_get('file_description_length', 128),
      '#description' => t('The description may be used as the label of the link to the file.'),
    );
  }

  // Adjust the AJAX settings so that on upload and remove of any individual
  // file, the entire group of file fields is updated together.
  if ($field['cardinality'] != 1) {
    $new_path = preg_replace('/\/\d+\//', '/', $element['remove_button']['#ajax']['path'], 1);
    $new_wrapper = preg_replace('/-\d+-/', '-', $element['remove_button']['#ajax']['wrapper'], 1);
    foreach (element_children($element) as $key) {
      if (isset($element[$key]['#ajax'])) {
        $element[$key]['#ajax']['path'] = $new_path;
        $element[$key]['#ajax']['wrapper'] = $new_wrapper;
      }
    }
    unset($element['#prefix'], $element['#suffix']);
  }

  return $element;
}

/**
 * An element #process callback for a group of file_generic fields.
 *
 * Adds the weight field to each row so it can be ordered and adds a new AJAX
 * wrapper around the entire group so it can be replaced all at once.
 */
function file_field_widget_process_multiple($element, &$form_state, $form) {
  $element_children = element_children($element, TRUE);
  $count = count($element_children);

  foreach ($element_children as $delta => $key) {
    if ($key != $element['#file_upload_delta']) {
      $element[$key]['_weight'] = array(
        '#type' => 'weight',
        '#delta' => $count,
        '#default_value' => $delta,
      );
    }
    else {
      // The title needs to be assigned to the upload field so that validation
      // errors include the correct widget label.
      $element[$key]['#title'] = $element['#title'];
      $element[$key]['_weight'] = array(
        '#type' => 'hidden',
        '#default_value' => $delta,
      );
    }
  }

  // Add a new wrapper around all the elements for AJAX replacement.
  $element['#prefix'] = '<div id="' . $element['#id'] . '-ajax-wrapper">';
  $element['#suffix'] = '</div>';

  return $element;
}

/**
 * Theme an individual file upload widget.
 */
function theme_file_widget($variables) {
  $element = $variables['element'];
  $output = '';

  // The "form-managed-file" class is required for proper AJAX functionality.
  $output .= '<div class="file-widget form-managed-file clearfix">';
  if ($element['fid']['#value'] != 0) {
    // Add the file size after the file name.
    $element['filename']['#markup'] .= ' <span class="file-size">(' . format_size($element['#file']->filesize) . ')</span> ';
  }
  $output .= drupal_render_children($element);
  $output .= '</div>';

  return $output;
}

/**
 * Theme a group of file upload widgets.
 */
function theme_file_widget_multiple($variables) {
  $element = $variables['element'];

  // Get our list of widgets in order.
  $widgets = array();
  foreach (element_children($element) as $key) {
    $widgets[$key] = $element[$key];
  }
  usort($widgets, '_field_sort_items_value_helper');

  // Special ID and classes for draggable tables.
  $weight_class = $element['#id'] . '-weight';
  $table_id = $element['#id'] . '-table';

  // Build up a table of applicable fields.
  $headers = array();
  $headers[] = t('File information');
  if ($element['#display_field']) {
    $headers[] = array(
      'data' => t('Display'),
      'class' => array('checkbox'),
    );
  }
  $headers[] = t('Weight');
  $headers[] = t('Operations');

  $rows = array();
  foreach ($widgets as $key => $widget) {
    // Save the uploading row for last.
    if ($element[$key]['#file'] == FALSE) {
      $element[$key]['#title'] = $element['#file_upload_title'];
      $element[$key]['#description'] = $element['#file_upload_description'];
      continue;
    }

    // Render all the buttons in the field as an "operation".
    $operations = '';
    foreach (element_children($element[$key]) as $sub_key) {
      if (isset($element[$key][$sub_key]['#type']) && $element[$key][$sub_key]['#type'] == 'submit') {
        $operations .= drupal_render($element[$key][$sub_key]);
      }
    }

    // Render the "Display" option in its own own column.
    $display = '';
    if ($element['#display_field']) {
      unset($element[$key]['display']['#title']);
      $display = array(
        'data' => drupal_render($element[$key]['display']),
        'class' => array('checkbox'),
      );
    }

    // Render the weight in its own column.
    $element[$key]['_weight']['#attributes']['class'] = array($weight_class);
    $weight = drupal_render($element[$key]['_weight']);

    // Render everything else together in a column, without the normal wrappers.
    $element[$key]['#theme_wrappers'] = array();
    $information = drupal_render($element[$key]);

    $row = array();
    $row[] = $information;
    if ($element['#display_field']) {
      $row[] = $display;
    }
    $row[] = $weight;
    $row[] = $operations;
    $rows[] = array(
      'data' => $row,
      'class' => isset($element[$key]['#attributes']['class']) ? array_merge($element[$key]['#attributes']['class'], array('draggable')) : array('draggable'),
    );
  }

  drupal_add_tabledrag($table_id, 'order', 'sibling', $weight_class);

  $output = '';
  $output = empty($rows) ? '' : theme('table', array('header' => $headers, 'rows' => $rows, 'attributes' => array('id' => $table_id)));
  $output .= drupal_render_children($element);
  return $output;
}

/**
 * Generate help text based on upload validators.
 *
 * @param $variables
 *   An associative array containing:
 *   - description: The normal description for this field, specified by the
 *     user.
 *   - upload_validators: An array of upload validators as used in
 *     $element['#upload_validators'].
 *
 * @return
 *   A string suitable for a file field description.
 */
function theme_file_upload_help($variables) {
  $description = $variables['description'];
  $upload_validators = $variables['upload_validators'];

  $descriptions = array();

  if (strlen($description)) {
    $descriptions[] = $description;
  }
  if (isset($upload_validators['file_validate_size'])) {
    $descriptions[] = t('Files must be less than !size.', array('!size' => '<strong>' . format_size($upload_validators['file_validate_size'][0]) . '</strong>'));
  }
  if (isset($upload_validators['file_validate_extensions'])) {
    $descriptions[] = t('Allowed file types: !extensions.', array('!extensions' => '<strong>' . check_plain($upload_validators['file_validate_extensions'][0]) . '</strong>'));
  }
  if (isset($upload_validators['file_validate_image_resolution'])) {
    $max = $upload_validators['file_validate_image_resolution'][0];
    $min = $upload_validators['file_validate_image_resolution'][1];
    if ($min && $max && $min == $max) {
      $descriptions[] = t('Images must be exactly !size pixels.', array('!size' => '<strong>' . $max . '</strong>'));
    }
    elseif ($min && $max) {
      $descriptions[] = t('Images must be between !min and !max pixels.', array('!min' => '<strong>' . $min . '</strong>', '!max' => '<strong>' . $max . '</strong>'));
    }
    elseif ($min) {
      $descriptions[] = t('Images must be larger than !min pixels.', array('!min' => '<strong>' . $min . '</strong>'));
    }
    elseif ($max) {
      $descriptions[] = t('Images must be smaller than !max pixels.', array('!max' => '<strong>' . $max . '</strong>'));
    }
  }

  return implode('<br />', $descriptions);
}

/**
 * Implements hook_field_formatter_view().
 */
function file_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $element = array();

  switch ($display['type']) {
    case 'file_default':
      foreach ($items as $delta => $item) {
        $element[$delta] = array(
          '#theme' => 'file_link',
          '#file' => (object) $item,
        );
      }
      break;

    case 'file_url_plain':
      foreach ($items as $delta => $item) {
        $element[$delta] = array('#markup' => empty($item['uri']) ? '' : file_create_url($item['uri']));
      }
      break;

    case 'file_table':
      // Display all values in a single element..
      $element[0] = array(
        '#theme' => 'file_formatter_table',
        '#items' => $items,
      );
      break;
  }

  return $element;
}

/**
 * Theme function for the 'table' formatter.
 */
function theme_file_formatter_table($variables) {
  $header = array(t('Attachment'), t('Size'));
  $rows = array();
  foreach ($variables['items'] as $delta => $item) {
    $rows[] = array(
      theme('file_link', array('file' => (object) $item)),
      format_size($item['filesize']),
    );
  }

  return empty($rows) ? '' : theme('table', array('header' => $header, 'rows' => $rows));
}
