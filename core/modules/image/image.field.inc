<?php

/**
 * @file
 * Implement an image field, based on the file module's file field.
 */

use Drupal\Component\Utility\NestedArray;

/**
 * Implements hook_field_info_alter().
 */
function image_field_info_alter(&$info) {
  $info['image']['settings']['uri_scheme'] = file_default_scheme();
}

/**
 * An element #process callback for the image_image field type.
 *
 * Expands the image_image type to include the alt and title fields.
 */
function image_field_widget_process($element, &$form_state, $form) {
  $item = $element['#value'];
  $item['fids'] = $element['fids']['#value'];

  $element['#theme'] = 'image_widget';
  $element['#attached']['css'][] = drupal_get_path('module', 'image') . '/css/image.theme.css';

  // Add the image preview.
  if (!empty($element['#files']) && $element['#preview_image_style']) {
    $file = reset($element['#files']);
    $variables = array(
      'style_name' => $element['#preview_image_style'],
      'uri' => $file->getFileUri(),
    );

    // Determine image dimensions.
    if (isset($element['#value']['width']) && isset($element['#value']['height'])) {
      $variables['width'] = $element['#value']['width'];
      $variables['height'] = $element['#value']['height'];
    }
    else {
      $image = \Drupal::service('image.factory')->get($file->getFileUri());
      if ($image->isSupported()) {
        $variables['width'] = $image->getWidth();
        $variables['height'] = $image->getHeight();
      }
      else {
        $variables['width'] = $variables['height'] = NULL;
      }
    }

    $element['preview'] = array(
      '#theme' => 'image_style',
      '#width' => $variables['width'],
      '#height' => $variables['height'],
      '#style_name' => $variables['style_name'],
      '#uri' => $variables['uri'],
    );

    // Store the dimensions in the form so the file doesn't have to be accessed
    // again. This is important for remote files.
    $element['width'] = array(
      '#type' => 'hidden',
      '#value' => $variables['width'],
    );
    $element['height'] = array(
      '#type' => 'hidden',
      '#value' => $variables['height'],
    );
  }

  // Add the additional alt and title fields.
  $element['alt'] = array(
    '#title' => t('Alternate text'),
    '#type' => 'textfield',
    '#default_value' => isset($item['alt']) ? $item['alt'] : '',
    '#description' => t('This text will be used by screen readers, search engines, or when the image cannot be loaded.'),
    // @see https://drupal.org/node/465106#alt-text
    '#maxlength' => 512,
    '#weight' => -2,
    '#access' => (bool) $item['fids'] && $element['#alt_field'],
    '#element_validate' => $element['#alt_field_required'] == 1 ? array('_image_field_required_fields_validate') : array(),
  );
  $element['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => isset($item['title']) ? $item['title'] : '',
    '#description' => t('The title is used as a tool tip when the user hovers the mouse over the image.'),
    '#maxlength' => 1024,
    '#weight' => -1,
    '#access' => (bool) $item['fids'] && $element['#title_field'],
    '#element_validate' => $element['#alt_field_required'] == 1 ? array('_image_field_required_fields_validate') : array(),
  );

  return $element;
}

/**
 * Validate callback for alt and title field, if the user wants them required.
 *
 * This is separated in a validate function instead of a #required flag to avoid
 * being validated on the process callback.
 */
function _image_field_required_fields_validate($element, &$form_state) {
  // Only do validation if the function is triggered from other places than
  // the image process form.
  if (!in_array('file_managed_file_submit', $form_state['triggering_element']['#submit'])) {
    // If the image is not there, we do not check for empty values.
    $parents = $element['#parents'];
    $field = array_pop($parents);
    $image_field = NestedArray::getValue($form_state['input'], $parents);
    // We check for the array key, so that it can be NULL (like if the user
    // submits the form without using the "upload" button).
    if (!array_key_exists($field, $image_field)) {
      return;
    }
    // Check if field is left emtpy.
    elseif (empty($image_field[$field])) {
      form_error($element, $form_state, t('The field !title is required', array('!title' => $element['#title'])));
      return;
    }
  }
}

/**
 * Returns HTML for an image field widget.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the image field widget.
 *
 * @ingroup themeable
 */
function theme_image_widget($variables) {
  $element = $variables['element'];
  $output = '';
  $output .= '<div class="image-widget form-managed-file clearfix">';

  if (isset($element['preview'])) {
    $output .= '<div class="image-preview">';
    $output .= drupal_render($element['preview']);
    $output .= '</div>';
  }

  $output .= '<div class="image-widget-data">';
  if (!empty($element['fids']['#value'])) {
    $file = reset($element['#files']);
    $element['file_' . $file->id()]['filename']['#suffix'] = ' <span class="file-size">(' . format_size($file->getSize()) . ')</span> ';
  }
  $output .= drupal_render_children($element);
  $output .= '</div>';
  $output .= '</div>';

  return $output;
}

/**
 * Returns HTML for an image field formatter.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An array of image data.
 *   - image_style: An optional image style.
 *   - path: An optional array containing the link 'path' and link 'options'.
 *
 * @ingroup themeable
 */
function theme_image_formatter($variables) {
  $item = $variables['item'];
  $image = array();

  // Do not output an empty 'title' attribute.
  if (isset($item['title']) && drupal_strlen($item['title']) != 0) {
    $image['#title'] = $item['title'];
  }

  if (isset($item['entity']) && empty($item['uri'])) {
    $image['#uri'] = $item['entity']->getFileUri();
  }
  else {
    $image['#uri'] = $item['uri'];
  }

  foreach (array('width', 'height', 'alt', 'attributes') as $key) {
    if (isset($item[$key]) || array_key_exists($key, $item)) {
      $image["#$key"] = $item[$key];
    }
  }

  if ($variables['image_style']) {
    $image['#theme'] = 'image_style';
    $image['#style_name'] = $variables['image_style'];
  }
  else {
    $image['#theme'] = 'image';
  }

  // The link path and link options are both optional, but for the options to be
  // processed, the link path must at least be an empty string.
  if (isset($variables['path']['path'])) {
    $path = $variables['path']['path'];
    $options = isset($variables['path']['options']) ? $variables['path']['options'] : array();
    // When displaying an image inside a link, the html option must be TRUE.
    $options['html'] = TRUE;
    $output = l($image, $path, $options);
  }
  else {
    $output = drupal_render($image);
  }

  return $output;
}
