<?php

/**
 * @file
 * Administration pages for image settings.
 */

use Drupal\Component\Utility\String;
use Drupal\image\ConfigurableImageEffectInterface;
use Drupal\image\ImageStyleInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Menu callback; Listing of all current image styles.
 */
function image_style_list() {
  $page = array();

  $styles = entity_load_multiple('image_style');
  $page['image_style_list'] = array(
    '#markup' => theme('image_style_list', array('styles' => $styles)),
    '#attached' => array(
      'css' => array(drupal_get_path('module', 'image') . '/css/image.admin.css' => array()),
    ),
  );

  return $page;

}

/**
 * Form builder; Edit an image style name and effects order.
 *
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $style
 *   An image style array.
 * @ingroup forms
 * @see image_style_form_submit()
 */
function image_style_form($form, &$form_state, ImageStyleInterface $style) {
  $title = t('Edit style %name', array('%name' => $style->label()));
  drupal_set_title($title, PASS_THROUGH);

  $form_state['image_style'] = $style;
  $form['#tree'] = TRUE;
  $form['#attached']['css'][drupal_get_path('module', 'image') . '/css/image.admin.css'] = array();

  // Show the thumbnail preview.
  $form['preview'] = array(
    '#type' => 'item',
    '#title' => t('Preview'),
    '#markup' => theme('image_style_preview', array('style' => $style)),
  );

  $form['label'] = array(
    '#type' => 'textfield',
    '#title' => t('Administrative label'),
    '#default_value' => $style->label(),
    '#required' => TRUE,
  );
  $form['name'] = array(
    '#type' => 'machine_name',
    '#default_value' => $style->id(),
    '#machine_name' => array(
      'exists' => 'image_style_load',
    ),
    '#required' => TRUE,
  );

  // Build the list of existing image effects for this image style.
  $form['effects'] = array(
    '#theme' => 'image_style_effects',
  );
  foreach ($style->getEffects()->sort() as $effect) {
    $key = $effect->getUuid();
    $form['effects'][$key]['#weight'] = isset($form_state['input']['effects']) ? $form_state['input']['effects'][$key]['weight'] : NULL;
    $form['effects'][$key]['label'] = array(
      '#markup' => String::checkPlain($effect->label()),
    );
    $form['effects'][$key]['summary'] = $effect->getSummary();
    $form['effects'][$key]['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight for @title', array('@title' => $effect->label())),
      '#title_display' => 'invisible',
      '#default_value' => $effect->getWeight(),
    );

    $links = array();
    $is_configurable = $effect instanceof ConfigurableImageEffectInterface;
    if ($is_configurable) {
      $links['edit'] = array(
        'title' => t('edit'),
        'href' => 'admin/config/media/image-styles/manage/' . $style->id() . '/effects/' . $key,
      );
    }
    $links['delete'] = array(
      'title' => t('delete'),
      'href' => 'admin/config/media/image-styles/manage/' . $style->id() . '/effects/' . $key . '/delete',
    );
    $form['effects'][$key]['operations'] = array(
      '#type' => 'operations',
      '#links' => $links,
    );
    $form['effects'][$key]['configure'] = array(
      '#type' => 'link',
      '#title' => t('edit'),
      '#href' => 'admin/config/media/image-styles/manage/' . $style->id() . '/effects/' . $key,
      '#access' => $is_configurable,
    );
    $form['effects'][$key]['remove'] = array(
      '#type' => 'link',
      '#title' => t('delete'),
      '#href' => 'admin/config/media/image-styles/manage/' . $style->id() . '/effects/' . $key . '/delete',
    );
  }

  // Build the new image effect addition form and add it to the effect list.
  $new_effect_options = array();
  $effects = Drupal::service('plugin.manager.image.effect')->getDefinitions();
  uasort($effects, function ($a, $b) {
    return strcasecmp($a['id'], $b['id']);
  });
  foreach ($effects as $effect => $definition) {
    $new_effect_options[$effect] = $definition['label'];
  }
  $form['effects']['new'] = array(
    '#tree' => FALSE,
    '#weight' => isset($form_state['input']['weight']) ? $form_state['input']['weight'] : NULL,
  );
  $form['effects']['new']['new'] = array(
    '#type' => 'select',
    '#title' => t('Effect'),
    '#title_display' => 'invisible',
    '#options' => $new_effect_options,
    '#empty_option' => t('Select a new effect'),
  );
  $form['effects']['new']['weight'] = array(
    '#type' => 'weight',
    '#title' => t('Weight for new effect'),
    '#title_display' => 'invisible',
    '#default_value' => count($form['effects']) - 1,
  );
  $form['effects']['new']['add'] = array(
    '#type' => 'submit',
    '#value' => t('Add'),
    '#validate' => array('image_style_form_add_validate'),
    '#submit' => array('image_style_form_submit', 'image_style_form_add_submit'),
  );

  // Show the Override or Submit button for this style.
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update style'),
  );

  return $form;
}

/**
 * Validate handler for adding a new image effect to an image style.
 */
function image_style_form_add_validate($form, &$form_state) {
  if (!$form_state['values']['new']) {
    form_error($form['effects']['new']['new'], t('Select an effect to add.'));
  }
}

/**
 * Submit handler for adding a new image effect to an image style.
 */
function image_style_form_add_submit($form, &$form_state) {
  $style = $form_state['image_style'];
  // Check if this field has any configuration options.
  $effect = Drupal::service('plugin.manager.image.effect')->getDefinition($form_state['values']['new']);

  // Load the configuration form for this option.
  if (is_subclass_of($effect['class'], '\Drupal\image\ConfigurableImageEffectInterface')) {
    $path = 'admin/config/media/image-styles/manage/' . $style->id() . '/add/' . $form_state['values']['new'];
    $form_state['redirect'] = array($path, array('query' => array('weight' => $form_state['values']['weight'])));
  }
  // If there's no form, immediately add the image effect.
  else {
    $effect = array(
      'id' => $effect['id'],
      'data' => array(),
      'weight' => $form_state['values']['weight'],
    );
    $style->saveImageEffect($effect);
    drupal_set_message(t('The image effect was successfully applied.'));
  }
}

/**
 * Submit handler for saving an image style.
 */
function image_style_form_submit($form, &$form_state) {
  $style = $form_state['image_style'];

  // Update image effect weights.
  if (!empty($form_state['values']['effects'])) {
    foreach ($form_state['values']['effects'] as $uuid => $effect_data) {
      if ($style->getEffects()->has($uuid)) {
        $style->getEffect($uuid)->setWeight($effect_data['weight']);
      }
    }
  }

  $style->set('name', $form_state['values']['name']);
  $style->set('label', $form_state['values']['label']);
  $status = $style->save();

  if ($status == SAVED_UPDATED) {
    drupal_set_message(t('Changes to the style have been saved.'));
  }
  $form_state['redirect'] = 'admin/config/media/image-styles/manage/' . $style->id();
}

/**
 * Form builder; Form for adding a new image style.
 *
 * @ingroup forms
 * @see image_style_add_form_submit()
 */
function image_style_add_form($form, &$form_state) {
  $form['label'] = array(
    '#type' => 'textfield',
    '#title' => t('Administrative label'),
    '#default_value' => '',
    '#required' => TRUE,
  );
  $form['name'] = array(
    '#type' => 'machine_name',
    '#machine_name' => array(
      'exists' => 'image_style_load',
    ),
    '#default_value' => '',
    '#required' => TRUE,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Create new style'),
  );

  return $form;
}

/**
 * Submit handler for adding a new image style.
 */
function image_style_add_form_submit($form, &$form_state) {
  $style = entity_create('image_style', array(
    'name' => $form_state['values']['name'],
    'label' => $form_state['values']['label'],
  ));
  $style->save();
  drupal_set_message(t('Style %name was created.', array('%name' => $style->label())));
  $form_state['redirect'] = 'admin/config/media/image-styles/manage/' . $style->id();
}

/**
 * Returns HTML for the page containing the list of image styles.
 *
 * @param $variables
 *   An associative array containing:
 *   - styles: An array of all the image styles returned by image_get_styles().
 *
 * @see image_get_styles()
 * @ingroup themeable
 */
function theme_image_style_list($variables) {
  $styles = $variables['styles'];

  $header = array(t('Style name'), t('Operations'));
  $rows = array();

  foreach ($styles as $style) {
    $row = array();
    $row[] = l($style->label(), 'admin/config/media/image-styles/manage/' . $style->id());
    $links = array();
    $links['edit'] = array(
      'title' => t('edit'),
      'href' => 'admin/config/media/image-styles/manage/' . $style->id(),
      'class' => array('image-style-link'),
    );
    $links['delete'] = array(
      'title' => t('delete'),
      'href' => 'admin/config/media/image-styles/manage/' . $style->id() . '/delete',
      'class' => array('image-style-link'),
    );
    $row[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
    );
    $rows[] = $row;
  }

  if (empty($rows)) {
    $rows[] = array(array(
      'colspan' => 4,
      'data' => t('There are currently no styles. <a href="!url">Add a new one</a>.', array('!url' => url('admin/config/media/image-styles/add'))),
    ));
  }

  return theme('table', array('header' => $header, 'rows' => $rows));
}

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

  foreach (element_children($form) as $key) {
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

  $output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'image-style-effects')));
  drupal_add_tabledrag('image-style-effects', 'order', 'sibling', 'image-effect-order-weight');
  return $output;
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

  $sample_image = config('image.settings')->get('preview_image');
  $sample_width = 160;
  $sample_height = 160;

  // Set up original file information.
  $original_path = $sample_image;
  $original_image = image_get_info($original_path);
  if ($original_image['width'] > $original_image['height']) {
    $original_width = min($original_image['width'], $sample_width);
    $original_height = round($original_width / $original_image['width'] * $original_image['height']);
  }
  else {
    $original_height = min($original_image['height'], $sample_height);
    $original_width = round($original_height / $original_image['height'] * $original_image['width']);
  }
  $original_attributes = array_intersect_key($original_image, array('width' => '', 'height' => ''));
  $original_attributes['style'] = 'width: ' . $original_width . 'px; height: ' . $original_height . 'px;';

  // Set up preview file information.
  $preview_file = $style->buildUri($original_path);
  if (!file_exists($preview_file)) {
    $style->createDerivative($original_path, $preview_file);
  }
  $preview_image = image_get_info($preview_file);
  if ($preview_image['width'] > $preview_image['height']) {
    $preview_width = min($preview_image['width'], $sample_width);
    $preview_height = round($preview_width / $preview_image['width'] * $preview_image['height']);
  }
  else {
    $preview_height = min($preview_image['height'], $sample_height);
    $preview_width = round($preview_height / $preview_image['height'] * $preview_image['width']);
  }
  $preview_attributes = array_intersect_key($preview_image, array('width' => '', 'height' => ''));
  $preview_attributes['style'] = 'width: ' . $preview_width . 'px; height: ' . $preview_height . 'px;';

  // In the previews, timestamps are added to prevent caching of images.
  $output = '<div class="image-style-preview preview clearfix">';

  // Build the preview of the original image.
  $original_url = file_create_url($original_path);
  $output .= '<div class="preview-image-wrapper">';
  $output .= t('original') . ' (' . l(t('view actual size'), $original_url) . ')';
  $output .= '<div class="preview-image original-image" style="' . $original_attributes['style'] . '">';
  $output .= '<a href="' . $original_url . '">' . theme('image', array('uri' => $original_path, 'alt' => t('Sample original image'), 'title' => '', 'attributes' => $original_attributes)) . '</a>';
  $output .= '<div class="height" style="height: ' . $original_height . 'px"><span>' . $original_image['height'] . 'px</span></div>';
  $output .= '<div class="width" style="width: ' . $original_width . 'px"><span>' . $original_image['width'] . 'px</span></div>';
  $output .= '</div>'; // End preview-image.
  $output .= '</div>'; // End preview-image-wrapper.

  // Build the preview of the image style.
  $preview_url = file_create_url($preview_file) . '?cache_bypass=' . REQUEST_TIME;
  $output .= '<div class="preview-image-wrapper">';
  $output .= check_plain($style->label()) . ' (' . l(t('view actual size'), file_create_url($preview_file) . '?' . time()) . ')';
  $output .= '<div class="preview-image modified-image" style="' . $preview_attributes['style'] . '">';
  $output .= '<a href="' . file_create_url($preview_file) . '?' . time() . '">' . theme('image', array('uri' => $preview_url, 'alt' => t('Sample modified image'), 'title' => '', 'attributes' => $preview_attributes)) . '</a>';
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
  foreach (element_children($element) as $n => $key) {
    $element[$key]['#attributes']['title'] = $element[$key]['#title'];
    unset($element[$key]['#title']);
    $row[] = drupal_render($element[$key]);
    if ($n % 3 == 3 - 1) {
      $rows[] = $row;
      $row = array();
    }
  }

  return theme('table', array('header' => array(), 'rows' => $rows, 'attributes' => array('class' => array('image-anchor'))));
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
    return check_plain($data['width']) . 'x' . check_plain($data['height']);
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
  $data = $variables['data'];
  return theme('image_resize_summary', array('data' => $data)) . ' ' . ($data['upscale'] ? '(' . t('upscaling allowed') . ')' : '');
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
