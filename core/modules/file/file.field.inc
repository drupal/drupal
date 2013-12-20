<?php

/**
 * @file
 * Field module functionality for the File module.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\field\FieldInterface;

/**
 * Implements hook_field_info_alter().
 *
 * Cannot annotate in FieldItem plugin the settings.uri_scheme meta data key
 * with a dynamic value. We need to alter the value here.
 */
function file_field_info_alter(&$info) {
  $info['file']['settings']['uri_scheme'] = file_default_scheme();
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
  $entity_type = $element['#entity_type'];
  $field_name = $element['#field_name'];
  $values = NestedArray::getValue($form_state['values'], $parents);

  array_pop($parents);
  $current = count(element_children(NestedArray::getValue($form, $parents))) - 1;

  $field = field_info_field($entity_type, $field_name);
  $cardinality = $field->getCardinality();
  $uploaded = count($values['fids']);
  $count = $uploaded + $current;
  if ($count > $cardinality) {
    $keep = $uploaded - $count + $cardinality;
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
          '%field' => $field_name,
          '@max' => $cardinality,
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
    $config = \Drupal::config('file.settings');
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
    $new_path = 'file/ajax';
    $new_options = array(
      'query' => array(
        'element_parents' => implode('/', $parents),
        'form_build_id' => $form['form_build_id']['#value'],
      ),
    );
    $field_element = NestedArray::getValue($form, $parents);
    $new_wrapper = $field_element['#id'] . '-ajax-wrapper';
    foreach (element_children($element) as $key) {
      if (isset($element[$key]['#ajax'])) {
        $element[$key]['#ajax']['path'] = $new_path;
        $element[$key]['#ajax']['options'] = $new_options;
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
  $field_state = field_form_get_state($parents, $field_name, $form_state);
  $field_state['items'] = $submitted_values;
  field_form_set_state($parents, $field_name, $form_state, $field_state);
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

  $build = array(
    '#type' => 'table',
    '#header' => $headers,
    '#rows' => $rows,
    '#attributes' => array(
      'id' => $table_id,
    ),
    '#tabledrag' => array(
      array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => $weight_class,
      ),
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
  if (isset($cardinality)) {
    if ($cardinality == -1) {
      $descriptions[] = t('Unlimited number of files can be uploaded to this field.');
    }
    else {
      $descriptions[] = format_plural($cardinality, 'One file only.', 'Maximum @count files.');
    }
  }
  if (isset($upload_validators['file_validate_size'])) {
    $descriptions[] = t('!size limit.', array('!size' => format_size($upload_validators['file_validate_size'][0])));
  }
  if (isset($upload_validators['file_validate_extensions'])) {
    $descriptions[] = t('Allowed types: !extensions.', array('!extensions' => check_plain($upload_validators['file_validate_extensions'][0])));
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

  return implode(' ', $descriptions);
}

/**
 * Determine whether a field references files stored in {file_managed}.
 *
 * @param Drupal\field\FieldInterface $field
 *   A field definition.
 *
 * @return
 *   The field column if the field references {file_managed}.fid, typically
 *   fid, FALSE if it doesn't.
 */
function file_field_find_file_reference_column(FieldInterface $field) {
  $schema = $field->getSchema();
  foreach ($schema['foreign keys'] as $data) {
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
    if ($item->isDisplayed() && $item->entity) {
      $rows[] = array(
        array(
          'data' => array(
            '#theme' => 'file_link',
            '#file' => $item->entity,
          ),
        ),
        format_size($item->entity->getSize()),
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
