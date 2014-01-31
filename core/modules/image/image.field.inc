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
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - image_style: An optional image style.
 *   - path: An optional array containing the link 'path' and link 'options'.
 *
 * @ingroup themeable
 */
function theme_image_formatter($variables) {
  if ($variables['image_style']) {
    $image = array(
      '#theme' => 'image_style',
      '#style_name' => $variables['image_style'],
    );
  }
  else {
    $image = array(
      '#theme' => 'image',
    );
  }
  $image['#attributes'] = $variables['item_attributes'];

  $item = $variables['item'];

  // Do not output an empty 'title' attribute.
  if (drupal_strlen($item->title) != 0) {
    $image['#title'] = $item->title;
  }

  if (($entity = $item->entity) && empty($item->uri)) {
    $image['#uri'] = $entity->getFileUri();
  }
  else {
    $image['#uri'] = $item->uri;
  }

  foreach (array('width', 'height', 'alt') as $key) {
    $image["#$key"] = $item->$key;
  }

  // The link path and link options are both optional, but for the options to be
  // processed, the link path must at least be an empty string.
  // @todo Add support for route names.
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
