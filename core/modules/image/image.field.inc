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
