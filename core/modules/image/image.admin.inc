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
 * Returns HTML for a preview of an image style.
 *
 * @param $variables
 *   An associative array containing:
 *   - style: \Drupal\image\ImageStyleInterface image style being previewed.
 *
 * @ingroup themeable
 */
function theme_image_style_preview($variables) {
  $style = $variables['style'];

  $sample_image = \Drupal::config('image.settings')->get('preview_image');
  $sample_width = 160;
  $sample_height = 160;

  // Set up original file information.
  $original_path = $sample_image;
  $image_factory = \Drupal::service('image.factory');
  $original_image = $image_factory->get($original_path);
  $original_image = array(
    'width' => $original_image->getWidth(),
    'height' => $original_image->getHeight(),
  );
  if ($original_image['width'] > $original_image['height']) {
    $original_width = min($original_image['width'], $sample_width);
    $original_height = round($original_width / $original_image['width'] * $original_image['height']);
  }
  else {
    $original_height = min($original_image['height'], $sample_height);
    $original_width = round($original_height / $original_image['height'] * $original_image['width']);
  }
  $original_image['style'] = 'width: ' . $original_width . 'px; height: ' . $original_height . 'px;';

  // Set up preview file information.
  $preview_file = $style->buildUri($original_path);
  if (!file_exists($preview_file)) {
    $style->createDerivative($original_path, $preview_file);
  }
  $preview_image = $image_factory->get($preview_file);
  $preview_image = array(
    'width' => $preview_image->getWidth(),
    'height' => $preview_image->getHeight(),
  );
  if ($preview_image['width'] > $preview_image['height']) {
    $preview_width = min($preview_image['width'], $sample_width);
    $preview_height = round($preview_width / $preview_image['width'] * $preview_image['height']);
  }
  else {
    $preview_height = min($preview_image['height'], $sample_height);
    $preview_width = round($preview_height / $preview_image['height'] * $preview_image['width']);
  }
  $preview_image['style'] = 'width: ' . $preview_width . 'px; height: ' . $preview_height . 'px;';

  // In the previews, timestamps are added to prevent caching of images.
  $output = '<div class="image-style-preview preview clearfix">';

  // Build the preview of the original image.
  $original_url = file_create_url($original_path);
  $image = array(
    '#theme' => 'image',
    '#uri' => $original_path,
    '#alt' => t('Sample original image'),
    '#title' => '',
    '#attributes' => $original_image,
  );
  $output .= '<div class="preview-image-wrapper">';
  $output .= t('original') . ' (' . l(t('view actual size'), $original_url) . ')';
  $output .= '<div class="preview-image original-image" style="' . $original_image['style'] . '">';
  $output .= '<a href="' . $original_url . '">' . drupal_render($image) . '</a>';
  $output .= '<div class="height" style="height: ' . $original_height . 'px"><span>' . $original_image['height'] . 'px</span></div>';
  $output .= '<div class="width" style="width: ' . $original_width . 'px"><span>' . $original_image['width'] . 'px</span></div>';
  $output .= '</div>'; // End preview-image.
  $output .= '</div>'; // End preview-image-wrapper.

  // Build the preview of the image style.
  $preview_url = file_create_url($preview_file) . '?cache_bypass=' . REQUEST_TIME;
  $image = array(
    '#theme' => 'image',
    '#uri' => $preview_url,
    '#alt' => t('Sample modified image'),
    '#title' => '',
    '#attributes' => $preview_image,
  );
  $output .= '<div class="preview-image-wrapper">';
  $output .= String::checkPlain($style->label()) . ' (' . l(t('view actual size'), file_create_url($preview_file) . '?' . time()) . ')';
  $output .= '<div class="preview-image modified-image" style="' . $preview_image['style'] . '">';
  $output .= '<a href="' . file_create_url($preview_file) . '?' . time() . '">' . drupal_render($image) . '</a>';
  $output .= '<div class="height" style="height: ' . $preview_height . 'px"><span>' . $preview_image['height'] . 'px</span></div>';
  $output .= '<div class="width" style="width: ' . $preview_width . 'px"><span>' . $preview_image['width'] . 'px</span></div>';
  $output .= '</div>'; // End preview-image.
  $output .= '</div>'; // End preview-image-wrapper.

  $output .= '</div>'; // End image-style-preview.

  return $output;
}

/**
 * Returns HTML for a 3x3 grid of checkboxes for image anchors.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render element containing radio buttons.
 *
 * @ingroup themeable
 */
function theme_image_anchor($variables) {
  $element = $variables['element'];

  $rows = array();
  $row = array();
  foreach (Element::children($element) as $n => $key) {
    $element[$key]['#attributes']['title'] = $element[$key]['#title'];
    unset($element[$key]['#title']);
    $row[] = drupal_render($element[$key]);
    if ($n % 3 == 3 - 1) {
      $rows[] = $row;
      $row = array();
    }
  }

  $table = array(
    '#type' => 'table',
    '#header' => array(),
    '#rows' => $rows,
    '#attributes' => array('class' => array('image-anchor')),
  );
  return drupal_render($table);
}

/**
 * Returns HTML for a summary of an image resize effect.
 *
 * @param $variables
 *   An associative array containing:
 *   - data: The current configuration for this resize effect.
 *
 * @ingroup themeable
 */
function theme_image_resize_summary($variables) {
  $data = $variables['data'];

  if ($data['width'] && $data['height']) {
    return String::checkPlain($data['width']) . 'x' . String::checkPlain($data['height']);
  }
  else {
    return ($data['width']) ? t('width @width', array('@width' => $data['width'])) : t('height @height', array('@height' => $data['height']));
  }
}

/**
 * Returns HTML for a summary of an image scale effect.
 *
 * @param $variables
 *   An associative array containing:
 *   - data: The current configuration for this scale effect.
 *
 * @ingroup themeable
 */
function theme_image_scale_summary($variables) {
  $image_resize_summary = array(
    '#theme' => 'image_resize_summary',
    '#data' => $variables['data'],
  );
  return drupal_render($image_resize_summary) . ' ' . ($variables['data']['upscale'] ? '(' . t('upscaling allowed') . ')' : '');
}

/**
 * Returns HTML for a summary of an image crop effect.
 *
 * @param $variables
 *   An associative array containing:
 *   - data: The current configuration for this crop effect.
 *
 * @ingroup themeable
 */
function theme_image_crop_summary($variables) {
  $image_resize_summary = array(
    '#theme' => 'image_resize_summary',
    '#data' => $variables['data'],
  );
  return drupal_render($image_resize_summary);
}

/**
 * Returns HTML for a summary of an image rotate effect.
 *
 * @param $variables
 *   An associative array containing:
 *   - data: The current configuration for this rotate effect.
 *
 * @ingroup themeable
 */
function theme_image_rotate_summary($variables) {
  $data = $variables['data'];
  return ($data['random']) ? t('random between -@degrees&deg and @degrees&deg', array('@degrees' => str_replace('-', '', $data['degrees']))) : t('@degrees&deg', array('@degrees' => $data['degrees']));
}
