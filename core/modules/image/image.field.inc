<?php

/**
 * @file
 */

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
function template_preprocess_image_widget(&$variables): void {
  $element = $variables['element'];

  $variables['attributes'] = ['class' => ['image-widget', 'js-form-managed-file', 'form-managed-file', 'clearfix']];

  $variables['data'] = [];
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
 *   - url: An optional \Drupal\Core\Url object.
 */
function template_preprocess_image_formatter(&$variables): void {
  if ($variables['image_style']) {
    $variables['image'] = [
      '#theme' => 'image_style',
      '#style_name' => $variables['image_style'],
    ];
  }
  else {
    $variables['image'] = [
      '#theme' => 'image',
    ];
  }
  $variables['image']['#attributes'] = $variables['item_attributes'];

  $item = $variables['item'];

  // Do not output an empty 'title' attribute.
  if (!is_null($item->title) && mb_strlen($item->title) != 0) {
    $variables['image']['#title'] = $item->title;
  }

  if (($entity = $item->entity) && empty($item->uri)) {
    $variables['image']['#uri'] = $entity->getFileUri();
  }
  else {
    $variables['image']['#uri'] = $item->uri;
  }

  foreach (['width', 'height', 'alt'] as $key) {
    $variables['image']["#$key"] = $item->$key;
  }
}
