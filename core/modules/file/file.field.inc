<?php

/**
 * @file
 * Field module functionality for the File module.
 */

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Render\Element;

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
  $headers = [];
  $headers[] = t('File information');
  if ($element['#display_field']) {
    $headers[] = [
      'data' => t('Display'),
      'class' => ['checkbox'],
    ];
  }
  $headers[] = t('Weight');
  $headers[] = t('Operations');

  // Get our list of widgets in order (needed when the form comes back after
  // preview or failed validation).
  $widgets = [];
  foreach (Element::children($element) as $key) {
    $widgets[] = &$element[$key];
  }
  usort($widgets, '_field_multiple_value_form_sort_helper');

  $rows = [];
  foreach ($widgets as $key => &$widget) {
    // Save the uploading row for last.
    if (empty($widget['#files'])) {
      $widget['#title'] = $element['#file_upload_title'];
      $widget['#description'] = \Drupal::service('renderer')->renderPlain($element['#file_upload_description']);
      continue;
    }

    // Delay rendering of the buttons, so that they can be rendered later in the
    // "operations" column.
    $operations_elements = [];
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
    $widget['#theme_wrappers'] = [];
    $information = drupal_render($widget);
    $display = '';
    if ($element['#display_field']) {
      unset($widget['display']['#title']);
      $display = [
        'data' => render($widget['display']),
        'class' => ['checkbox'],
      ];
    }
    $widget['_weight']['#attributes']['class'] = [$weight_class];
    $weight = render($widget['_weight']);

    // Arrange the row with all of the rendered columns.
    $row = [];
    $row[] = $information;
    if ($element['#display_field']) {
      $row[] = $display;
    }
    $row[] = $weight;

    // Show the buttons that had previously been marked as hidden in this
    // preprocess function. We use show() to undo the earlier hide().
    foreach (Element::children($operations_elements) as $key) {
      show($operations_elements[$key]);
    }
    $row[] = [
      'data' => $operations_elements,
    ];
    $rows[] = [
      'data' => $row,
      'class' => isset($widget['#attributes']['class']) ? array_merge($widget['#attributes']['class'], ['draggable']) : ['draggable'],
    ];
  }

  $variables['table'] = [
    '#type' => 'table',
    '#header' => $headers,
    '#rows' => $rows,
    '#attributes' => [
      'id' => $table_id,
    ],
    '#tabledrag' => [
      [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => $weight_class,
      ],
    ],
    '#access' => !empty($rows),
  ];

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

  $descriptions = [];

  if (!empty($description)) {
    $descriptions[] = FieldFilteredMarkup::create($description);
  }
  if (isset($cardinality)) {
    if ($cardinality == -1) {
      $descriptions[] = t('Unlimited number of files can be uploaded to this field.');
    }
    else {
      $descriptions[] = \Drupal::translation()->formatPlural($cardinality, 'One file only.', 'Maximum @count files.');
    }
  }
  if (isset($upload_validators['file_validate_size'])) {
    $descriptions[] = t('@size limit.', ['@size' => format_size($upload_validators['file_validate_size'][0])]);
  }
  if (isset($upload_validators['file_validate_extensions'])) {
    $descriptions[] = t('Allowed types: @extensions.', ['@extensions' => $upload_validators['file_validate_extensions'][0]]);
  }

  if (isset($upload_validators['file_validate_image_resolution'])) {
    $max = $upload_validators['file_validate_image_resolution'][0];
    $min = $upload_validators['file_validate_image_resolution'][1];
    if ($min && $max && $min == $max) {
      $descriptions[] = t('Images must be exactly <strong>@size</strong> pixels.', ['@size' => $max]);
    }
    elseif ($min && $max) {
      $descriptions[] = t('Images must be larger than <strong>@min</strong> pixels. Images larger than <strong>@max</strong> pixels will be resized.', ['@min' => $min, '@max' => $max]);
    }
    elseif ($min) {
      $descriptions[] = t('Images must be larger than <strong>@min</strong> pixels.', ['@min' => $min]);
    }
    elseif ($max) {
      $descriptions[] = t('Images larger than <strong>@max</strong> pixels will be resized.', ['@max' => $max]);
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
