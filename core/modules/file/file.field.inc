<?php

/**
 * @file
 * Field module functionality for the File module.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\String;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Render\Element;

/**
 * Returns HTML for an individual file upload widget.
 *
 * Default template: file-widget.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the file.
 */
function template_preprocess_file_widget(&$variables) {
  $element = $variables['element'];
  if (!empty($element['fids']['#value'])) {
    // Add the file size after the file name.
    $file = reset($element['#files']);
    $element['file_' . $file->id()]['filename']['#suffix'] = ' <span class="file-size">(' . format_size($file->getSize()) . ')</span> ';
  }
  $variables['element'] = $element;
  // The "form-managed-file" class is required for proper Ajax functionality.
  $variables['attributes'] = array('class' => array('file-widget', 'form-managed-file', 'clearfix'));
}

/**
 * Prepares variables for multi file form widget templates.
 *
 * Default template: file-widget-multiple.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the widgets.
 */
function template_preprocess_file_widget_multiple(&$variables) {
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
  foreach (Element::children($element) as $key) {
    $widgets[] = &$element[$key];
  }
  usort($widgets, '_field_multiple_value_form_sort_helper');

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
    foreach (Element::children($widget) as $sub_key) {
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
    $row[] = SafeMarkup::set($operations);
    $rows[] = array(
      'data' => $row,
      'class' => isset($widget['#attributes']['class']) ? array_merge($widget['#attributes']['class'], array('draggable')) : array('draggable'),
    );
  }

  $variables['table'] = array(
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

  $variables['element'] = $element;
}

/**
 * Prepares variables for file upload help text templates.
 *
 * Default template: file-upload-help.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - description: The normal description for this field, specified by the
 *     user.
 *   - upload_validators: An array of upload validators as used in
 *     $element['#upload_validators'].
 */
function template_preprocess_file_upload_help(&$variables) {
  $description = $variables['description'];
  $upload_validators = $variables['upload_validators'];
  $cardinality = $variables['cardinality'];

  $descriptions = array();

  if (!empty($description)) {
    $descriptions[] = Html::normalize($description);
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
    $descriptions[] = t('Allowed types: @extensions.', array('@extensions' => $upload_validators['file_validate_extensions'][0]));
  }

  if (isset($upload_validators['file_validate_image_resolution'])) {
    $max = $upload_validators['file_validate_image_resolution'][0];
    $min = $upload_validators['file_validate_image_resolution'][1];
    if ($min && $max && $min == $max) {
      $descriptions[] = t('Images must be exactly !size pixels.', array('!size' => '<strong>' . $max . '</strong>'));
    }
    elseif ($min && $max) {
      $descriptions[] = t('Images must be larger than !min pixels. Images larger than !max pixels will be resized.', array('!min' => '<strong>' . $min . '</strong>', '!max' => '<strong>' . $max . '</strong>'));
    }
    elseif ($min) {
      $descriptions[] = t('Images must be larger than !min pixels.', array('!min' => '<strong>' . $min . '</strong>'));
    }
    elseif ($max) {
      $descriptions[] = t('Images larger than !max pixels will be resized.', array('!max' => '<strong>' . $max . '</strong>'));
    }
  }

  $variables['descriptions'] = $descriptions;
}

/**
 * Determine whether a field references files stored in {file_managed}.
 *
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field
 *   A field definition.
 *
 * @return bool
 *   The field column if the field references {file_managed}.fid, typically
 *   fid, FALSE if it does not.
 */
function file_field_find_file_reference_column(FieldDefinitionInterface $field) {
  $schema = $field->getFieldStorageDefinition()->getSchema();
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
