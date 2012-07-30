<?php

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
        'uri_scheme' => variable_get('file_default_scheme', 'public'),
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
    '#description' => t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.'),
    '#element_validate' => array('_file_generic_settings_file_directory_validate'),
    '#weight' => 3,
  );

  // Make the extension list a little more human-friendly by comma-separation.
  $extensions = str_replace(' ', ', ', $settings['file_extensions']);
  $form['file_extensions'] = array(
    '#type' => 'textfield',
    '#title' => t('Allowed file extensions'),
    '#default_value' => $extensions,
    '#description' => t('Separate extensions with a space or comma and do not include the leading dot.'),
    '#element_validate' => array('_file_generic_settings_extensions'),
    '#weight' => 1,
    // By making this field required, we prevent a potential security issue
    // that would allow files of any type to be uploaded.
    '#required' => TRUE,
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
  form_set_value($element, $value, $form_state);
}

/**
 * Implements hook_field_load().
 */
function file_field_load($entity_type, $entities, $field, $instances, $langcode, &$items, $age) {

  $fids = array();
  foreach ($entities as $id => $entity) {
    // Load the files from the files table.
    foreach ($items[$id] as $delta => $item) {
      if (!empty($item['fid'])) {
        $fids[] = $item['fid'];
      }
    }
  }
  $files = file_load_multiple($fids);

  foreach ($entities as $id => $entity) {
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
    }
    // Ensure consecutive deltas.
    $items[$id] = array_values($items[$id]);
  }
}

/**
 * Implements hook_field_presave().
 */
function file_field_presave($entity_type, $entity, $field, $instance, $langcode, &$items) {
  // Make sure that each file which will be saved with this object has a
  // permanent status, so that it will not be removed when temporary files are
  // cleaned up.
  foreach ($items as $item) {
    $file = file_load($item['fid']);
    if (!$file->status) {
      $file->status = FILE_STATUS_PERMANENT;
      file_save($file);
    }
  }
}

/**
 * Implements hook_field_insert().
 */
function file_field_insert($entity_type, $entity, $field, $instance, $langcode, &$items) {
  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);

  // Add a new usage of each uploaded file.
  foreach ($items as $item) {
    $file = (object) $item;
    file_usage_add($file, 'file', $entity_type, $id);
  }
}

/**
 * Implements hook_field_update().
 *
 * Checks for files that have been removed from the object.
 */
function file_field_update($entity_type, $entity, $field, $instance, $langcode, &$items) {
  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);

  // On new revisions, all files are considered to be a new usage and no
  // deletion of previous file usages are necessary.
  if (!empty($entity->revision)) {
    foreach ($items as $item) {
      $file = (object) $item;
      file_usage_add($file, 'file', $entity_type, $id);
    }
    return;
  }

  // Build a display of the current FIDs.
  $current_fids = array();
  foreach ($items as $item) {
    $current_fids[] = $item['fid'];
  }

  // Compare the original field values with the ones that are being saved.
  $original = $entity->original;
  $original_fids = array();
  if (!empty($original->{$field['field_name']}[$langcode])) {
    foreach ($original->{$field['field_name']}[$langcode] as $original_item) {
      $original_fids[] = $original_item['fid'];
      if (isset($original_item['fid']) && !in_array($original_item['fid'], $current_fids)) {
        // Decrement the file usage count by 1 and delete the file if possible.
        file_field_delete_file($original_item, $field, $entity_type, $id);
      }
    }
  }

  // Add new usage entries for newly added files.
  foreach ($items as $item) {
    if (!in_array($item['fid'], $original_fids)) {
      $file = (object) $item;
      file_usage_add($file, 'file', $entity_type, $id);
    }
  }
}

/**
 * Implements hook_field_delete().
 */
function file_field_delete($entity_type, $entity, $field, $instance, $langcode, &$items) {
  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);

  // Delete all file usages within this entity.
  foreach ($items as $delta => $item) {
    file_field_delete_file($item, $field, $entity_type, $id, 0);
  }
}

/**
 * Implements hook_field_delete_revision().
 */
function file_field_delete_revision($entity_type, $entity, $field, $instance, $langcode, &$items) {
  list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);
  foreach ($items as $delta => $item) {
    // Decrement the file usage count by 1 and delete the file if possible.
    if (file_field_delete_file($item, $field, $entity_type, $id)) {
      $items[$delta] = NULL;
    }
  }
}

/**
 * Decrements the usage count for a file and attempts to delete it.
 *
 * This function only has an effect if the file being deleted is used only by
 * File module.
 *
 * @param $item
 *   The field item that contains a file array.
 * @param $field
 *   The field structure for the operation.
 * @param $entity_type
 *   The type of $entity.
 * @param $id
 *   The entity ID which contains the file being deleted.
 * @param $count
 *   (optional) The number of references to decrement from the object
 *   containing the file. Defaults to 1.
 *
 * @return
 *   Boolean TRUE if the file was deleted, or an array of remaining references
 *   if the file is still in use by other modules. Boolean FALSE if an error
 *   was encountered.
 */
function file_field_delete_file($item, $field, $entity_type, $id, $count = 1) {
  // To prevent the file field from deleting files it doesn't know about, check
  // the file reference count. Temporary files can be deleted because they
  // are not yet associated with any content at all.
  $file = (object) $item;
  $file_usage = file_usage_list($file);
  if ($file->status == 0 || !empty($file_usage['file'])) {
    file_usage_delete($file, 'file', $entity_type, $id, $count);
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
 * Determines whether a file should be displayed when outputting field content.
 *
 * @param $item
 *   A field item array.
 * @param $field
 *   A field array.
 *
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
    '#description' => t('The throbber display does not show the status of uploads but takes up less space. The progress bar is helpful for monitoring progress on large uploads.'),
    '#weight' => 16,
    '#access' => file_progress_implementation(),
  );

  return $form;
}

/**
 * Implements hook_field_widget_form().
 */
function file_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {

  $defaults = array(
    'fid' => 0,
    'display' => !empty($field['settings']['display_default']),
    'description' => '',
  );

  // Load the items for form rebuilds from the field state as they might not be
  // in $form_state['values'] because of validation limitations. Also, they are
  // only passed in as $items when editing existing entities.
  $field_state = field_form_get_state($element['#field_parents'], $field['field_name'], $langcode, $form_state);
  if (isset($field_state['items'])) {
    $items = $field_state['items'];
  }

  // Essentially we use the managed_file type, extended with some enhancements.
  $element_info = element_info('managed_file');
  $element += array(
    '#type' => 'managed_file',
    '#upload_location' => file_field_widget_uri($field, $instance),
    '#upload_validators' => file_field_widget_upload_validators($field, $instance),
    '#value_callback' => 'file_field_widget_value',
    '#process' => array_merge($element_info['#process'], array('file_field_widget_process')),
    '#progress_indicator' => $instance['widget']['settings']['progress_indicator'],
    // Allows this field to return an array instead of a single value.
    '#extended' => TRUE,
  );

  if ($field['cardinality'] == 1) {
    // Set the default value.
    $element['#default_value'] = !empty($items) ? $items[0] : $defaults;
    // If there's only one field, return it as delta 0.
    if (empty($element['#default_value']['fid'])) {
      $element['#description'] = theme('file_upload_help', array('description' => $element['#description'], 'upload_validators' => $element['#upload_validators']));
    }
    $elements = array($element);
  }
  else {
    // If there are multiple values, add an element for each existing one.
    foreach ($items as $item) {
      $elements[$delta] = $element;
      $elements[$delta]['#default_value'] = $item;
      $elements[$delta]['#weight'] = $delta;
      $delta++;
    }
    // And then add one more empty row for new uploads except when this is a
    // programmed form as it is not necessary.
    if (($field['cardinality'] == FIELD_CARDINALITY_UNLIMITED || $delta < $field['cardinality']) && empty($form_state['programmed'])) {
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
 * Retrieves the upload validators for a file field.
 *
 * @param $field
 *   A field array.
 *
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
 * Determines the URI for a file field instance.
 *
 * @param $field
 *   A field array.
 * @param $instance
 *   A field instance array.
 * @param $data
 *   An array of token objects to pass to token_replace().
 *
 * @return
 *   A file directory URI with tokens replaced.
 *
 * @see token_replace()
 */
function file_field_widget_uri($field, $instance, $data = array()) {
  $destination = trim($instance['settings']['file_directory'], '/');

  // Replace tokens.
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
    $field = field_widget_field($element, $form_state);
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

  $field = field_widget_field($element, $form_state);
  $instance = field_widget_instance($element, $form_state);
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
      '#type' => variable_get('file_description_type', 'textfield'),
      '#title' => t('Description'),
      '#value' => isset($item['description']) ? $item['description'] : '',
      '#maxlength' => variable_get('file_description_length', 128),
      '#description' => t('The description may be used as the label of the link to the file.'),
    );
  }

  // Adjust the Ajax settings so that on upload and remove of any individual
  // file, the entire group of file fields is updated together.
  if ($field['cardinality'] != 1) {
    $parents = array_slice($element['#array_parents'], 0, -1);
    $new_path = 'file/ajax/' . implode('/', $parents) . '/' . $form['form_build_id']['#value'];
    $field_element = drupal_array_get_nested_value($form, $parents);
    $new_wrapper = $field_element['#id'] . '-ajax-wrapper';
    foreach (element_children($element) as $key) {
      if (isset($element[$key]['#ajax'])) {
        $element[$key]['#ajax']['path'] = $new_path;
        $element[$key]['#ajax']['wrapper'] = $new_wrapper;
      }
    }
    unset($element['#prefix'], $element['#suffix']);
  }

  // Add another submit handler to the upload and remove buttons, to implement
  // functionality needed by the field widget. This submit handler, along with
  // the rebuild logic in file_field_widget_form() requires the entire field,
  // not just the individual item, to be valid.
  foreach (array('upload_button', 'remove_button') as $key) {
    $element[$key]['#submit'][] = 'file_field_widget_submit';
    $element[$key]['#limit_validation_errors'] = array(array_slice($element['#parents'], 0, -1));
  }

  return $element;
}

/**
 * An element #process callback for a group of file_generic fields.
 *
 * Adds the weight field to each row so it can be ordered and adds a new Ajax
 * wrapper around the entire group so it can be replaced all at once.
 */
function file_field_widget_process_multiple($element, &$form_state, $form) {
  $element_children = element_children($element, TRUE);
  $count = count($element_children);

  foreach ($element_children as $delta => $key) {
    if ($key != $element['#file_upload_delta']) {
      $description = _file_field_get_description_from_element($element[$key]);
      $element[$key]['_weight'] = array(
        '#type' => 'weight',
        '#title' => $description ? t('Weight for @title', array('@title' => $description)) : t('Weight for new file'),
        '#title_display' => 'invisible',
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

  // Add a new wrapper around all the elements for Ajax replacement.
  $element['#prefix'] = '<div id="' . $element['#id'] . '-ajax-wrapper">';
  $element['#suffix'] = '</div>';

  return $element;
}

/**
 * Retrieves the file description from a field field element.
 *
 * This helper function is used by file_field_widget_process_multiple().
 *
 * @param $element
 *   The element being processed.
 *
 * @return
 *   A description of the file suitable for use in the administrative interface.
 */
function _file_field_get_description_from_element($element) {
  // Use the actual file description, if it's available.
  if (!empty($element['#default_value']['description'])) {
    return $element['#default_value']['description'];
  }
  // Otherwise, fall back to the filename.
  if (!empty($element['#default_value']['filename'])) {
    return $element['#default_value']['filename'];
  }
  // This is probably a newly uploaded file; no description is available.
  return FALSE;
}

/**
 * Form submission handler for upload/remove button of file_field_widget_form().
 *
 * This runs in addition to and after file_managed_file_submit().
 *
 * @see file_managed_file_submit()
 * @see file_field_widget_form()
 * @see file_field_widget_process()
 */
function file_field_widget_submit($form, &$form_state) {
  // During the form rebuild, file_field_widget_form() will create field item
  // widget elements using re-indexed deltas, so clear out $form_state['input']
  // to avoid a mismatch between old and new deltas. The rebuilt elements will
  // have #default_value set appropriately for the current state of the field,
  // so nothing is lost in doing this.
  $parents = array_slice($form_state['triggering_element']['#parents'], 0, -2);
  drupal_array_set_nested_value($form_state['input'], $parents, NULL);

  $button = $form_state['triggering_element'];

  // Go one level up in the form, to the widgets container.
  $element = drupal_array_get_nested_value($form, array_slice($button['#array_parents'], 0, -1));
  $field_name = $element['#field_name'];
  $langcode = $element['#language'];
  $parents = $element['#field_parents'];

  $submitted_values = drupal_array_get_nested_value($form_state['values'], array_slice($button['#array_parents'], 0, -2));
  foreach ($submitted_values as $delta => $submitted_value) {
    if (!$submitted_value['fid']) {
      unset($submitted_values[$delta]);
    }
  }

  // Re-index deltas after removing empty items.
  $submitted_values = array_values($submitted_values);

  // Update form_state values.
  drupal_array_set_nested_value($form_state['values'], array_slice($button['#array_parents'], 0, -2), $submitted_values);

  // Update items.
  $field_state = field_form_get_state($parents, $field_name, $langcode, $form_state);
  $field_state['items'] = $submitted_values;
  field_form_set_state($parents, $field_name, $langcode, $form_state, $field_state);
}

/**
 * Returns HTML for an individual file upload widget.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render element representing the widget.
 *
 * @ingroup themeable
 */
function theme_file_widget($variables) {
  $element = $variables['element'];
  $output = '';

  // The "form-managed-file" class is required for proper Ajax functionality.
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
 * Returns HTML for a group of file upload widgets.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render element representing the widgets.
 *
 * @ingroup themeable
 */
function theme_file_widget_multiple($variables) {
  $element = $variables['element'];

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

  // Get our list of widgets in order (needed when the form comes back after
  // preview or failed validation).
  $widgets = array();
  foreach (element_children($element) as $key) {
    $widgets[] = &$element[$key];
  }
  usort($widgets, '_field_sort_items_value_helper');

  $rows = array();
  foreach ($widgets as $key => &$widget) {
    // Save the uploading row for last.
    if ($widget['#file'] == FALSE) {
      $widget['#title'] = $element['#file_upload_title'];
      $widget['#description'] = $element['#file_upload_description'];
      continue;
    }

    // Delay rendering of the buttons, so that they can be rendered later in the
    // "operations" column.
    $operations_elements = array();
    foreach (element_children($widget) as $sub_key) {
      if (isset($widget[$sub_key]['#type']) && $widget[$sub_key]['#type'] == 'submit') {
        hide($widget[$sub_key]);
        $operations_elements[] = &$widget[$sub_key];
      }
    }

    // Delay rendering of the "Display" option and the weight selector, so that
    // each can be rendered later in its own column.
    if ($element['#display_field']) {
      hide($widget['display']);
    }
    hide($widget['_weight']);

    // Render everything else together in a column, without the normal wrappers.
    $widget['#theme_wrappers'] = array();
    $information = drupal_render($widget);

    // Render the previously hidden elements, using render() instead of
    // drupal_render(), to undo the earlier hide().
    $operations = '';
    foreach ($operations_elements as $operation_element) {
      $operations .= render($operation_element);
    }
    $display = '';
    if ($element['#display_field']) {
      unset($widget['display']['#title']);
      $display = array(
        'data' => render($widget['display']),
        'class' => array('checkbox'),
      );
    }
    $widget['_weight']['#attributes']['class'] = array($weight_class);
    $weight = render($widget['_weight']);

    // Arrange the row with all of the rendered columns.
    $row = array();
    $row[] = $information;
    if ($element['#display_field']) {
      $row[] = $display;
    }
    $row[] = $weight;
    $row[] = $operations;
    $rows[] = array(
      'data' => $row,
      'class' => isset($widget['#attributes']['class']) ? array_merge($widget['#attributes']['class'], array('draggable')) : array('draggable'),
    );
  }

  drupal_add_tabledrag($table_id, 'order', 'sibling', $weight_class);

  $output = '';
  $output = empty($rows) ? '' : theme('table', array('header' => $headers, 'rows' => $rows, 'attributes' => array('id' => $table_id)));
  $output .= drupal_render_children($element);
  return $output;
}


/**
 * Returns HTML for help text based on file upload validators.
 *
 * @param $variables
 *   An associative array containing:
 *   - description: The normal description for this field, specified by the
 *     user.
 *   - upload_validators: An array of upload validators as used in
 *     $element['#upload_validators'].
 *
 * @ingroup themeable
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
      if (!empty($items)) {
        // Display all values in a single element..
        $element[0] = array(
          '#theme' => 'file_formatter_table',
          '#items' => $items,
        );
      }
      break;
  }

  return $element;
}

/**
 * Returns HTML for a file attachments table.
 *
 * @param $variables
 *   An associative array containing:
 *   - items: An array of file attachments.
 *
 * @ingroup themeable
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
