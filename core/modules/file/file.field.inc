<?php

/**
 * @file
 * Field module functionality for the File module.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;

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
        'uri_scheme' => file_default_scheme(),
      ),
      'instance_settings' => array(
        'file_extensions' => 'txt',
        'file_directory' => '',
        'max_filesize' => '',
        'description_field' => 0,
      ),
      'default_widget' => 'file_generic',
      'default_formatter' => 'file_default',
      'class' => '\Drupal\file\Type\FileItem',
    ),
  );
}

/**
 * Implements hook_field_settings_form().
 */
function file_field_settings_form($field, $instance) {
  $defaults = field_info_field_settings($field['type']);
  $settings = array_merge($defaults, $field['settings']);

  $form['#attached']['library'][] = array('file', 'drupal.file');

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
    '#states' => array(
      'visible' => array(
        ':input[name="field[settings][display_field]"]' => array('checked' => TRUE),
      ),
    ),
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
    '#disabled' => $field->hasData(),
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
 * Render API callback: Validates the maximum upload size field.
 *
 * Ensures that a size has been entered and that it can be parsed by
 * parse_size().
 *
 * This function is assigned as an #element_validate callback in
 * file_field_instance_settings_form().
 */
function _file_generic_settings_max_filesize($element, &$form_state) {
  if (!empty($element['#value']) && !is_numeric(parse_size($element['#value']))) {
    form_error($element, t('The "!name" option must contain a valid value. You may either leave the text field empty or enter a string like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes).', array('!name' => t($element['title']))));
  }
}

/**
 * Render API callback: Validates the allowed file extensions field.
 *
 * This function is assigned as an #element_validate callback in
 * file_field_instance_settings_form().
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
 * Render API callback: Validates the file destination field.
 *
 * Removes slashes from the beginning and end of the destination value and
 * ensures that the file directory path is not included at the beginning of the
 * value.
 *
 * This function is assigned as an #element_validate callback in
 * file_field_instance_settings_form().
 */
function _file_generic_settings_file_directory_validate($element, &$form_state) {
  // Strip slashes from the beginning and end of $widget['file_directory'].
  $value = trim($element['#value'], '\\/');
  form_set_value($element, $value, $form_state);
}

/**
 * Implements hook_field_insert().
 */
function file_field_insert(EntityInterface $entity, $field, $instance, $langcode, &$items) {
  // Add a new usage of each uploaded file.
  foreach ($items as $item) {
    file_usage()->add(file_load($item['target_id']), 'file', $entity->entityType(), $entity->id());
  }
}

/**
 * Implements hook_field_update().
 *
 * Checks for files that have been removed from the object.
 */
function file_field_update(EntityInterface $entity, $field, $instance, $langcode, &$items) {
  // On new revisions, all files are considered to be a new usage and no
  // deletion of previous file usages are necessary.
  if (!empty($entity->original) && $entity->getRevisionId() != $entity->original->getRevisionId()) {
    foreach ($items as $item) {
      file_usage()->add(file_load($item['target_id']), 'file', $entity->entityType(), $entity->id());
    }
    return;
  }

  // Build a display of the current FIDs.
  $current_fids = array();
  foreach ($items as $item) {
    $current_fids[] = $item['target_id'];
  }

  // Compare the original field values with the ones that are being saved.
  $original = $entity->original->getBCEntity();
  $original_fids = array();
  if (!empty($original->{$field['field_name']}[$langcode])) {
    foreach ($original->{$field['field_name']}[$langcode] as $original_item) {
      $original_fids[] = $original_item['target_id'];
      if (isset($original_item['target_id']) && !in_array($original_item['target_id'], $current_fids)) {
        // Decrement the file usage count by 1.
        file_usage()->delete(file_load($original_item['target_id']), 'file', $entity->entityType(), $entity->id());
      }
    }
  }

  // Add new usage entries for newly added files.
  foreach ($items as $item) {
    if (!in_array($item['target_id'], $original_fids)) {
      file_usage()->add(file_load($item['target_id']), 'file', $entity->entityType(), $entity->id());
    }
  }
}

/**
 * Implements hook_field_delete().
 */
function file_field_delete(EntityInterface $entity, $field, $instance, $langcode, &$items) {
  // Delete all file usages within this entity.
  foreach ($items as $delta => $item) {
    file_usage()->delete(file_load($item['target_id']), 'file', $entity->entityType(), $entity->id(), 0);
  }
}

/**
 * Implements hook_field_delete_revision().
 */
function file_field_delete_revision(EntityInterface $entity, $field, $instance, $langcode, &$items) {
  foreach ($items as $delta => $item) {
    // Decrement the file usage count by 1.
    file_usage()->delete(file_load($item['target_id']), 'file', $entity->entityType(), $entity->id());
  }
}

/**
 * Implements hook_field_is_empty().
 */
function file_field_is_empty($item, $field_type) {
  return empty($item['target_id']);
}

/**
 * Retrieves the upload validators for a file field.
 *
 * @param array $field_settings
 *   The field settings.
 *
 * @return
 *   An array suitable for passing to file_save_upload() or the file field
 *   element's '#upload_validators' property.
 */
function file_field_widget_upload_validators(array $field_settings) {
  // Cap the upload size according to the PHP limit.
  $max_filesize = parse_size(file_upload_max_size());
  if (!empty($field_settings['max_filesize']) && parse_size($field_settings['max_filesize']) < $max_filesize) {
    $max_filesize = parse_size($field_settings['max_filesize']);
  }

  $validators = array();

  // There is always a file size limit due to the PHP server limit.
  $validators['file_validate_size'] = array($max_filesize);

  // Add the extension check if necessary.
  if (!empty($field_settings['file_extensions'])) {
    $validators['file_validate_extensions'] = array($field_settings['file_extensions']);
  }

  return $validators;
}

/**
 * Determines the URI for a file field.
 *
 * @param array $field_settings
 *   The field settings.
 * @param $data
 *   An array of token objects to pass to
 *   \Drupal\Core\Utility\Token::replace().
 *
 * @return
 *   A file directory URI with tokens replaced.
 *
 * @see \Drupal\Core\Utility\Token::replace()
 */
function file_field_widget_uri(array $field_settings, $data = array()) {
  $destination = trim($field_settings['file_directory'], '/');

  // Replace tokens.
  $destination = Drupal::token()->replace($destination, $data);

  return $field_settings['uri_scheme'] . '://' . $destination;
}

/**
 * Render API callback: Retrieves the value for the file_generic field element.
 *
 * This function is assigned as a #value callback in file_field_widget_form().
 */
function file_field_widget_value($element, $input = FALSE, $form_state) {
  if ($input) {
    // Checkboxes lose their value when empty.
    // If the display field is present make sure its unchecked value is saved.
    if (empty($input['display'])) {
      $input['display'] = $element['#display_field'] ? 0 : 1;
    }
  }

  // We depend on the managed file element to handle uploads.
  $return = file_managed_file_value($element, $input, $form_state);

  // Ensure that all the required properties are returned even if empty.
  $return += array(
    'fids' => array(),
    'display' => 1,
    'description' => '',
  );

  return $return;
}

/**
 * Validation callback for upload element on file widget. Checks if user has
 * uploaded more files than allowed.
 *
 * This validator is used only when cardinality not set to 1 or unlimited.
 */
function file_field_widget_multiple_count_validate($element, &$form_state, $form) {
  $parents = $element['#parents'];
  $values = NestedArray::getValue($form_state['values'], $parents);

  array_pop($parents);
  $current = count(element_children(NestedArray::getValue($form, $parents))) - 1;

  $field = field_info_field($element['#field_name']);
  $uploaded = count($values['fids']);
  $count = $uploaded + $current;
  if ($count > $field['cardinality']) {
    $keep = $uploaded - $count + $field['cardinality'];
    $removed_files = array_slice($values['fids'], $keep);
    $removed_names = array();
    foreach ($removed_files as $fid) {
      $file = file_load($fid);
      $removed_names[] = $file->getFilename();
    }
    drupal_set_message(
      t(
        'Field %field can only hold @max values but there were @count uploaded. The following files have been omitted as a result: %list.',
        array(
          '%field' => $field['field_name'],
          '@max' => $field['cardinality'],
          '@count' => $keep,
          '%list' => implode(', ', $removed_names),
        )
      ),
      'warning'
    );
    $values['fids'] = array_slice($values['fids'], 0, $keep);
    NestedArray::setValue($form_state['values'], $element['#parents'], $values);
  }
}

/**
 * Render API callback: Processes a file_generic field element.
 *
 * Expands the file_generic type to include the description and display fields.
 *
 * This function is assigned as a #process callback in file_field_widget_form().
 */
function file_field_widget_process($element, &$form_state, $form) {
  $item = $element['#value'];
  $item['fids'] = $element['fids']['#value'];

  $element['#theme'] = 'file_widget';

  // Add the display field if enabled.
  if ($element['#display_field'] && $item['fids']) {
    $element['display'] = array(
      '#type' => empty($item['fids']) ? 'hidden' : 'checkbox',
      '#title' => t('Include file in display'),
      '#value' => isset($item['display']) ? $item['display'] : $element['#display_default'],
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
  if ($element['#description_field'] && $item['fids']) {
    $config = config('file.settings');
    $element['description'] = array(
      '#type' => $config->get('description.type'),
      '#title' => t('Description'),
      '#value' => isset($item['description']) ? $item['description'] : '',
      '#maxlength' => $config->get('description.length'),
      '#description' => t('The description may be used as the label of the link to the file.'),
    );
  }

  // Adjust the Ajax settings so that on upload and remove of any individual
  // file, the entire group of file fields is updated together.
  if ($element['#cardinality'] != 1) {
    $parents = array_slice($element['#array_parents'], 0, -1);
    $new_path = 'file/ajax/' . implode('/', $parents) . '/' . $form['form_build_id']['#value'];
    $field_element = NestedArray::getValue($form, $parents);
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
 * Render API callback: Processes a group of file_generic field elements.
 *
 * Adds the weight field to each row so it can be ordered and adds a new Ajax
 * wrapper around the entire group so it can be replaced all at once.
 *
 * This function is assigned as a #process callback in file_field_widget_form().
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
  NestedArray::setValue($form_state['input'], $parents, NULL);

  $button = $form_state['triggering_element'];

  // Go one level up in the form, to the widgets container.
  $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
  $field_name = $element['#field_name'];
  $langcode = $element['#language'];
  $parents = $element['#field_parents'];

  $submitted_values = NestedArray::getValue($form_state['values'], array_slice($button['#parents'], 0, -2));
  foreach ($submitted_values as $delta => $submitted_value) {
    if (empty($submitted_value['fids'])) {
      unset($submitted_values[$delta]);
    }
  }

  // If there are more files uploaded via the same widget, we have to separate
  // them, as we display each file in it's own widget.
  $new_values = array();
  foreach ($submitted_values as $delta => $submitted_value) {
    if (is_array($submitted_value['fids'])) {
      foreach ($submitted_value['fids'] as $fid) {
        $new_value = $submitted_value;
        $new_value['fids'] = array($fid);
        $new_values[] = $new_value;
      }
    }
    else {
      $new_value = $submitted_value;
    }
  }

  // Re-index deltas after removing empty items.
  $submitted_values = array_values($new_values);

  // Update form_state values.
  NestedArray::setValue($form_state['values'], array_slice($button['#parents'], 0, -2), $submitted_values);

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
  if (!empty($element['fids']['#value'])) {
    // Add the file size after the file name.
    $file = reset($element['#files']);
    $element['file_' . $file->id()]['filename']['#suffix'] = ' <span class="file-size">(' . format_size($file->getSize()) . ')</span> ';
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
    if (empty($widget['#files'])) {
      $widget['#title'] = $element['#file_upload_title'];
      $widget['#description'] = drupal_render($element['#file_upload_description']);
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

  $build = array(
    '#theme' => 'table',
    '#header' => $headers,
    '#rows' => $rows,
    '#attributes' => array(
      'id' => $table_id,
    ),
  );

  $output = empty($rows) ? '' : drupal_render($build);
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
  $cardinality = $variables['cardinality'];

  $descriptions = array();

  if (strlen($description)) {
    $descriptions[] = _filter_htmlcorrector($description);
  }
  if (isset($upload_validators['file_validate_size'])) {
    $descriptions[] = t('Files must be less than !size.', array('!size' => '<strong>' . format_size($upload_validators['file_validate_size'][0]) . '</strong>'));
  }
  if (isset($upload_validators['file_validate_extensions'])) {
    $descriptions[] = t('Allowed file types: !extensions.', array('!extensions' => '<strong>' . check_plain($upload_validators['file_validate_extensions'][0]) . '</strong>'));
  }
  if (isset($cardinality)) {
    if ($cardinality == -1) {
      $descriptions[] = t('Unlimited number of files can be uploaded to this field.');
    }
    else {
      $descriptions[] = format_plural($cardinality, 'This field can store only one file.', 'This field can store at most @count files.');
    }
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
 * Determine whether a field references files stored in {file_managed}.
 *
 * @param array $field
 *   A field array.
 *
 * @return
 *   The field column if the field references {file_managed}.fid, typically
 *   fid, FALSE if it doesn't.
 */
function file_field_find_file_reference_column($field) {
  foreach ($field['foreign keys'] as $data) {
    if ($data['table'] == 'file_managed') {
      foreach ($data['columns'] as $field_column => $column) {
        if ($column == 'fid') {
          return $field_column;
        }
      }
    }
  }
  return FALSE;
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
    if ($item['display'] && $item['entity']) {
      $rows[] = array(
        array(
          'data' => array(
            '#theme' => 'file_link',
            '#file' => $item['entity'],
          ),
        ),
        format_size($item['entity']->getSize()),
      );
    }
  }

  $build = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );

  return empty($rows) ? '' : drupal_render($build);
}
