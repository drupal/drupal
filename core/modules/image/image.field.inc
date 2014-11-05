<?php

/**
 * @file
 * Implement an image field, based on the file module's file field.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\Element;

/**
 * Prepares variables for image widget templates.
 *
 * Default template: image-widget.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the image field widget.
 */
function template_preprocess_image_widget(&$variables) {
  $element = $variables['element'];

  $variables['attributes'] = array('class' => array('image-widget', 'form-managed-file', 'clearfix'));

  if (!empty($element['fids']['#value'])) {
    $file = reset($element['#files']);
    $element['file_' . $file->id()]['filename']['#suffix'] = ' <span class="file-size">(' . format_size($file->getSize()) . ')</span> ';
  }

  $variables['data'] = array();
  foreach (Element::children($element) as $child) {
    $variables['data'][$child] = $element[$child];
  }

}

/**
 * Prepares variables for image formatter templates.
 *
 * Default template: image-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - image_style: An optional image style.
 *   - path: An optional array containing the link 'path' and link 'options'.
 */
function template_preprocess_image_formatter(&$variables) {
  if ($variables['image_style']) {
    $variables['image'] = array(
      '#theme' => 'image_style',
      '#style_name' => $variables['image_style'],
    );
  }
  else {
    $variables['image'] = array(
      '#theme' => 'image',
    );
  }
  $variables['image']['#attributes'] = $variables['item_attributes'];

  $item = $variables['item'];

  // Do not output an empty 'title' attribute.
  if (Unicode::strlen($item->title) != 0) {
    $variables['image']['#title'] = $item->title;
  }

  if (($entity = $item->entity) && empty($item->uri)) {
    $variables['image']['#uri'] = $entity->getFileUri();
  }
  else {
    $variables['image']['#uri'] = $item->uri;
  }

  foreach (array('width', 'height', 'alt') as $key) {
    $variables['image']["#$key"] = $item->$key;
  }

  // The link path and link options are both optional, but for the options to be
  // processed, the link path must at least be an empty string.
  // @todo Add support for route names.
  $variables['url'] = NULL;
  if (isset($variables['path']['path'])) {
    $path = $variables['path']['path'];
    $options = isset($variables['path']['options']) ? $variables['path']['options'] : array();
    $variables['url'] = _url($path, $options);
  }
}
