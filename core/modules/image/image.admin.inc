<?php

/**
 * @file
 * Administration pages for image settings.
 */
use Drupal\Component\Utility\String;
use Drupal\Core\Render\Element;
/**
 * Returns HTML for a listing of the effects within a specific image style.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_image_style_effects($variables) {
  $form = $variables['form'];
  $rows = array();

  foreach (Element::children($form) as $key) {
    $row = array();
    $form[$key]['weight']['#attributes']['class'] = array('image-effect-order-weight');
    if ($key != 'new') {
      $summary = drupal_render($form[$key]['summary']);
      $row[] = drupal_render($form[$key]['label']) . (empty($summary) ? '' : ' ' . $summary);
      $row[] = drupal_render($form[$key]['weight']);
      $row[] = array('data' => $form[$key]['operations']);
    }
    else {
      // Add the row for adding a new image effect.
      $row[] = '<div class="image-style-new">' . drupal_render($form['new']['new']) . drupal_render($form['new']['add']) . '</div>';
      $row[] = drupal_render($form['new']['weight']);
      $row[] = '';
    }

    $rows[] = array(
      'data' => $row,
      'class' => array('draggable'),
    );
  }

  $header = array(
    t('Effect'),
    t('Weight'),
    t('Operations'),
  );

  if (count($rows) == 1 && (!isset($form['new']['#access']) || $form['new']['#access'])) {
    array_unshift($rows, array(array(
      'data' => t('There are currently no effects in this style. Add one by selecting an option below.'),
      'colspan' => 4,
    )));
  }

  $table = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array('id' => 'image-style-effects'),
    '#tabledrag' => array(
      array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'image-effect-order-weight',
      ),
    ),
  );
  return drupal_render($table);
}

/**
 * Prepares variables for image style preview templates.
 *
 * Default template: image-style-preview.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - style: \Drupal\image\ImageStyleInterface image style being previewed.
 */
function template_preprocess_image_style_preview(&$variables) {
  // Style information.
  $style = $variables['style'];
  $variables['style_id'] = String::checkPlain($style->id());
  $variables['style_name'] = String::checkPlain($style->label());

  // Cache bypass token.
  $variables['cache_bypass'] = REQUEST_TIME;

  // Sample image info.
  $sample_width = 160;
  $sample_height = 160;
  $image_factory = \Drupal::service('image.factory');

  // Set up original file information.
  $original_path = \Drupal::config('image.settings')->get('preview_image');
  $original_image = $image_factory->get($original_path);
  $variables['original'] = array(
    'url' => file_create_url($original_path),
    'width' => $original_image->getWidth(),
    'height' => $original_image->getHeight(),
  );
  if ($variables['original']['width'] > $variables['original']['height']) {
    $variables['preview']['original']['width'] = min($variables['original']['width'], $sample_width);
    $variables['preview']['original']['height'] = round($variables['preview']['original']['width'] / $variables['original']['width'] * $variables['original']['height']);
  }
  else {
    $variables['preview']['original']['height'] = min($variables['original']['height'], $sample_height);
    $variables['preview']['original']['width'] = round($variables['preview']['original']['height'] / $variables['original']['height'] * $variables['original']['width']);
  }

  // Set up derivative file information.
  $preview_file = $style->buildUri($original_path);
  // Create derivative if necessary.
  if (!file_exists($preview_file)) {
    $style->createDerivative($original_path, $preview_file);
  }
  $preview_image = $image_factory->get($preview_file);
  $variables['derivative'] = array(
    'url' => file_create_url($preview_file),
    'width' => $preview_image->getWidth(),
    'height' => $preview_image->getHeight(),
  );
  if ($variables['derivative']['width'] > $variables['derivative']['height']) {
    $variables['preview']['derivative']['width'] = min($variables['derivative']['width'], $sample_width);
    $variables['preview']['derivative']['height'] = round($variables['preview']['derivative']['width'] / $variables['derivative']['width'] * $variables['derivative']['height']);
  }
  else {
    $variables['preview']['derivative']['height'] = min($variables['derivative']['height'], $sample_height);
    $variables['preview']['derivative']['width'] = round($variables['preview']['derivative']['height'] / $variables['derivative']['height'] * $variables['derivative']['width']);
  }

  // Build the preview of the original image.
  $variables['original']['rendered'] = array(
    '#theme' => 'image',
    '#uri' => $original_path,
    '#alt' => t('Sample original image'),
    '#title' => '',
    '#attributes' => array(
      'width' => $variables['original']['width'],
      'height' => $variables['original']['height'],
      'style' => 'width: ' . $variables['preview']['original']['width'] . 'px; height: ' . $variables['preview']['original']['height'] . 'px;',
    ),
  );

  // Build the preview of the image style derivative. Timestamps are added
  // to prevent caching of images on the client side.
  $variables['derivative']['rendered'] = array(
    '#theme' => 'image',
    '#uri' => $variables['derivative']['url'] . '?cache_bypass=' . $variables['cache_bypass'],
    '#alt' => t('Sample modified image'),
    '#title' => '',
    '#attributes' => array(
      'width' => $variables['derivative']['width'],
      'height' => $variables['derivative']['height'],
      'style' => 'width: ' . $variables['preview']['derivative']['width'] . 'px; height: ' . $variables['preview']['derivative']['height'] . 'px;',
    ),
  );

}

/**
 * Prepares variables for image anchor templates.
 *
 * Default template: image-anchor.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the image.
 */
function template_preprocess_image_anchor(&$variables) {
  $element = $variables['element'];

  $rows = array();
  $row = array();
  foreach (Element::children($element) as $n => $key) {
    $element[$key]['#attributes']['title'] = $element[$key]['#title'];
    unset($element[$key]['#title']);
    $row[] = array(
      'data' => $element[$key],
    );
    if ($n % 3 == 3 - 1) {
      $rows[] = $row;
      $row = array();
    }
  }

  $variables['table'] = array(
    '#type' => 'table',
    '#header' => array(),
    '#rows' => $rows,
    '#attributes' => array(
      'class' => array('image-anchor'),
    ),
  );
}
