<?php
// $Id: image.admin.inc,v 1.28 2010/11/21 07:24:53 webchick Exp $

/**
 * @file
 * Administration pages for image settings.
 */

/**
 * Menu callback; Listing of all current image styles.
 */
function image_style_list() {
  $page = array();

  $styles = image_styles();
  $page['image_style_list'] = array(
    '#markup' => theme('image_style_list', array('styles' => $styles)),
    '#attached' => array(
      'css' => array(drupal_get_path('module', 'image') . '/image.admin.css' => array()),
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
 * @see image_style_name_validate()
 */
function image_style_form($form, &$form_state, $style) {
  $title = t('Edit %name style', array('%name' => $style['name']));
  drupal_set_title($title, PASS_THROUGH);

  // Adjust this form for styles that must be overridden to edit.
  $editable = (bool) ($style['storage'] & IMAGE_STORAGE_EDITABLE);

  if (!$editable && empty($form_state['input'])) {
    drupal_set_message(t('This image style is currently being provided by a module. Click the "Override defaults" button to change its settings.'), 'warning');
  }

  $form_state['image_style'] = $style;
  $form['#tree'] = TRUE;
  $form['#attached']['css'][drupal_get_path('module', 'image') . '/image.admin.css'] = array();

  // Show the thumbnail preview.
  $form['preview'] = array(
    '#type' => 'item',
    '#title' => t('Preview'),
    '#markup' => theme('image_style_preview', array('style' => $style)),
  );

  // Allow the name of the style to be changed, unless this style is
  // provided by a module's hook_default_image_styles().
  if ($style['storage'] & IMAGE_STORAGE_MODULE) {
    $form['name'] = array(
      '#type' => 'item',
      '#title' => t('Image style name'),
      '#markup' => $style['name'],
      '#description' => t('This image style is being provided by %module module and may not be renamed.', array('%module' => $style['module'])),
    );
  }
  else {
    $form['name'] = array(
      '#type' => 'textfield',
      '#size' => '64',
      '#title' => t('Image style name'),
      '#default_value' => $style['name'],
      '#description' => t('The name is used in URLs for generated images. Use only lowercase alphanumeric characters, underscores (_), and hyphens (-).'),
      '#element_validate' => array('image_style_name_validate'),
      '#required' => TRUE,
    );
  }

  // Build the list of existing image effects for this image style.
  $form['effects'] = array(
    '#theme' => 'image_style_effects',
  );
  foreach ($style['effects'] as $key => $effect) {
    $form['effects'][$key]['#weight'] = isset($form_state['input']['effects']) ? $form_state['input']['effects'][$key]['weight'] : NULL;
    $form['effects'][$key]['label'] = array(
      '#markup' => $effect['label'],
    );
    $form['effects'][$key]['summary'] = array(
      '#markup' => isset($effect['summary theme']) ? theme($effect['summary theme'], array('data' => $effect['data'])) : '',
    );
    $form['effects'][$key]['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight for @title', array('@title' => $effect['label'])),
      '#title_display' => 'invisible',
      '#default_value' => $effect['weight'],
      '#access' => $editable,
    );

    // Only attempt to display these fields for editable styles as the 'ieid'
    // key is not set for styles defined in code.
    if ($editable) {
      $form['effects'][$key]['configure'] = array(
        '#type' => 'link',
        '#title' => t('edit'),
        '#href' => 'admin/config/media/image-styles/edit/' . $style['name'] . '/effects/' . $effect['ieid'],
        '#access' => $editable && isset($effect['form callback']),
      );
      $form['effects'][$key]['remove'] = array(
        '#type' => 'link',
        '#title' => t('delete'),
        '#href' => 'admin/config/media/image-styles/edit/' . $style['name'] . '/effects/' . $effect['ieid'] . '/delete',
        '#access' => $editable,
      );
    }
  }

  // Build the new image effect addition form and add it to the effect list.
  $new_effect_options = array();
  foreach (image_effect_definitions() as $effect => $definition) {
    $new_effect_options[$effect] = check_plain($definition['label']);
  }
  $form['effects']['new'] = array(
    '#tree' => FALSE,
    '#weight' => isset($form_state['input']['weight']) ? $form_state['input']['weight'] : NULL,
    '#access' => $editable,
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
  $form['actions']['override'] = array(
    '#type' => 'submit',
    '#value' => t('Override defaults'),
    '#validate' => array(),
    '#submit' => array('image_style_form_override_submit'),
    '#access' => !$editable,
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update style'),
    '#access' => $editable,
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
  $effect = image_effect_definition_load($form_state['values']['new']);

  // Load the configuration form for this option.
  if (isset($effect['form callback'])) {
    $path = 'admin/config/media/image-styles/edit/' . $form_state['image_style']['name'] . '/add/' . $form_state['values']['new'];
    $form_state['redirect'] = array($path, array('query' => array('weight' => $form_state['values']['weight'])));
  }
  // If there's no form, immediately add the image effect.
  else {
    $effect['isid'] = $style['isid'];
    $effect['weight'] = $form_state['values']['weight'];
    image_effect_save($effect);
    drupal_set_message(t('The image effect was successfully applied.'));
  }
}

/**
 * Submit handler for overriding a module-defined style.
 */
function image_style_form_override_submit($form, &$form_state) {
  drupal_set_message(t('The %style style has been overridden, allowing you to change its settings.', array('%style' => $form_state['image_style']['name'])));
  image_default_style_save($form_state['image_style']);
}

/**
 * Submit handler for saving an image style.
 */
function image_style_form_submit($form, &$form_state) {
  // Update the image style name if it has changed.
  $style = $form_state['image_style'];
  if (isset($form_state['values']['name']) && $style['name'] != $form_state['values']['name']) {
    $style['name'] = $form_state['values']['name'];
  }

  // Update image effect weights.
  if (!empty($form_state['values']['effects'])) {
    foreach ($form_state['values']['effects'] as $ieid => $effect_data) {
      if (isset($style['effects'][$ieid])) {
        $effect = $style['effects'][$ieid];
        $effect['weight'] = $effect_data['weight'];
        image_effect_save($effect);
      }
    }
  }

  image_style_save($style);
  if ($form_state['values']['op'] == t('Update style')) {
    drupal_set_message('Changes to the style have been saved.');
  }
  $form_state['redirect'] = 'admin/config/media/image-styles/edit/' . $style['name'];
}

/**
 * Form builder; Form for adding a new image style.
 *
 * @ingroup forms
 * @see image_style_add_form_submit()
 * @see image_style_name_validate()
 */
function image_style_add_form($form, &$form_state) {
  $form['name'] = array(
    '#type' => 'textfield',
    '#size' => '64',
    '#title' => t('Style name'),
    '#default_value' => '',
    '#description' => t('The name is used in URLs for generated images. Use only lowercase alphanumeric characters, underscores (_), and hyphens (-).'),
    '#element_validate' => array('image_style_name_validate'),
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
  $style = array('name' => $form_state['values']['name']);
  $style = image_style_save($style);
  drupal_set_message(t('Style %name was created.', array('%name' => $style['name'])));
  $form_state['redirect'] = 'admin/config/media/image-styles/edit/' . $style['name'];
}

/**
 * Element validate function to ensure unique, URL safe style names.
 */
function image_style_name_validate($element, $form_state) {
  // Check for duplicates.
  $styles = image_styles();
  if (isset($styles[$element['#value']]) && (!isset($form_state['image_style']['isid']) || $styles[$element['#value']]['isid'] != $form_state['image_style']['isid'])) {
    form_set_error($element['#name'], t('The image style name %name is already in use.', array('%name' => $element['#value'])));
  }

  // Check for illegal characters in image style names.
  if (preg_match('/[^0-9a-z_\-]/', $element['#value'])) {
    form_set_error($element['#name'], t('Please only use lowercase alphanumeric characters, underscores (_), and hyphens (-) for style names.'));
  }
}

/**
 * Form builder; Form for deleting an image style.
 *
 * @param $style
 *   An image style array.
 *
 * @ingroup forms
 * @see image_style_delete_form_submit()
 */
function image_style_delete_form($form, $form_state, $style) {
  $form_state['image_style'] = $style;

  $replacement_styles = array_diff_key(image_style_options(), array($style['name'] => ''));
  $form['replacement'] = array(
    '#title' => t('Replacement style'),
    '#type' => 'select',
    '#options' => $replacement_styles,
    '#empty_option' => t('No replacement, just delete'),
  );

  return confirm_form(
    $form,
    t('Optionally select a style before deleting %style', array('%style' => $style['name'])),
    'admin/config/media/image-styles',
    t('If this style is in use on the site, you may select another style to replace it. All images that have been generated for this style will be permanently deleted.'),
    t('Delete'),  t('Cancel')
  );
}

/**
 * Submit handler to delete an image style.
 */
function image_style_delete_form_submit($form, &$form_state) {
  $style = $form_state['image_style'];

  image_style_delete($style, $form_state['values']['replacement']);
  drupal_set_message(t('Style %name was deleted.', array('%name' => $style['name'])));
  $form_state['redirect'] = 'admin/config/media/image-styles';
}

/**
 * Confirmation form to revert a database style to its default.
 */
function image_style_revert_form($form, $form_state, $style) {
  $form_state['image_style'] = $style;

  return confirm_form(
    $form,
    t('Revert the %style style?', array('%style' => $style['name'])),
    'admin/config/media/image-styles',
    t('Reverting this style will delete the customized settings and restore the defaults provided by the @module module.', array('@module' => $style['module'])),
    t('Revert'),  t('Cancel')
  );
}

/**
 * Submit handler to convert an overridden style to its default.
 */
function image_style_revert_form_submit($form, &$form_state) {
  drupal_set_message(t('The %style style has been revert to its defaults.', array('%style' => $form_state['image_style']['name'])));
  image_default_style_revert($form_state['image_style']);
  $form_state['redirect'] = 'admin/config/media/image-styles';
}

/**
 * Form builder; Form for adding and editing image effects.
 *
 * This form is used universally for editing all image effects. Each effect adds
 * its own custom section to the form by calling the form function specified in
 * hook_image_effects().
 *
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $style
 *   An image style array.
 * @param $effect
 *   An image effect array.
 *
 * @ingroup forms
 * @see hook_image_effects()
 * @see image_effects()
 * @see image_resize_form()
 * @see image_scale_form()
 * @see image_rotate_form()
 * @see image_crop_form()
 * @see image_effect_form_submit()
 */
function image_effect_form($form, &$form_state, $style, $effect) {
  if (!empty($effect['data'])) {
    $title = t('Edit %label effect', array('%label' => $effect['label']));
  }
  else{
    $title = t('Add %label effect', array('%label' => $effect['label']));
  }
  drupal_set_title($title, PASS_THROUGH);

  $form_state['image_style'] = $style;
  $form_state['image_effect'] = $effect;

  // If no configuration for this image effect, return to the image style page.
  if (!isset($effect['form callback'])) {
    drupal_goto('admin/config/media/image-styles/edit/' . $style['name']);
  }

  $form['#tree'] = TRUE;
  $form['#attached']['css'][drupal_get_path('module', 'image') . '/image.admin.css'] = array();
  if (function_exists($effect['form callback'])) {
    $form['data'] = call_user_func($effect['form callback'], $effect['data']);
  }

  // Check the URL for a weight, then the image effect, otherwise use default.
  $form['weight'] = array(
    '#type' => 'hidden',
    '#value' => isset($_GET['weight']) ? intval($_GET['weight']) : (isset($effect['weight']) ? $effect['weight'] : count($style['effects'])),
  );

  $form['actions'] = array('#tree' => FALSE, '#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => isset($effect['ieid']) ? t('Update effect') : t('Add effect'),
  );
  $form['actions']['cancel'] = array(
    '#type' => 'link',
    '#title' => t('Cancel'),
    '#href' => 'admin/config/media/image-styles/edit/' . $style['name'],
  );

  return $form;
}

/**
 * Submit handler for updating an image effect.
 */
function image_effect_form_submit($form, &$form_state) {
  $style = $form_state['image_style'];
  $effect = array_merge($form_state['image_effect'], $form_state['values']);
  $effect['isid'] = $style['isid'];
  image_effect_save($effect);
  drupal_set_message(t('The image effect was successfully applied.'));
  $form_state['redirect'] = 'admin/config/media/image-styles/edit/' . $style['name'];
}

/**
 * Form builder; Form for deleting an image effect.
 *
 * @param $style
 *   Name of the image style from which the image effect will be removed.
 * @param $effect
 *   Name of the image effect to remove.
 * @ingroup forms
 * @see image_effect_delete_form_submit()
 */
function image_effect_delete_form($form, &$form_state, $style, $effect) {
  $form_state['image_style'] = $style;
  $form_state['image_effect'] = $effect;

  $question = t('Are you sure you want to delete the @effect effect from the %style style?', array('%style' => $style['name'], '@effect' => $effect['label']));
  return confirm_form($form, $question, 'admin/config/media/image-styles/edit/' . $style['name'], '', t('Delete'));
}

/**
 * Submit handler to delete an image effect.
 */
function image_effect_delete_form_submit($form, &$form_state) {
  $style = $form_state['image_style'];
  $effect = $form_state['image_effect'];

  image_effect_delete($effect);
  drupal_set_message(t('The image effect %name has been deleted.', array('%name' => $effect['label'])));
  $form_state['redirect'] = 'admin/config/media/image-styles/edit/' . $style['name'];
}

/**
 * Element validate handler to ensure an integer pixel value.
 *
 * The property #allow_negative = TRUE may be set to allow negative integers.
 */
function image_effect_integer_validate($element, &$form_state) {
  $value = empty($element['#allow_negative']) ? $element['#value'] : preg_replace('/^-/', '', $element['#value']);
  if ($element['#value'] != '' && (!is_numeric($value) || intval($value) <= 0)) {
    if (empty($element['#allow_negative'])) {
      form_error($element, t('!name must be an integer.', array('!name' => $element['#title'])));
    }
    else {
      form_error($element, t('!name must be a positive integer.', array('!name' => $element['#title'])));
    }
  }
}

/**
 * Element validate handler to ensure a hexadecimal color value.
 */
function image_effect_color_validate($element, &$form_state) {
  if ($element['#value'] != '') {
    $hex_value = preg_replace('/^#/', '', $element['#value']);
    if (!preg_match('/^#[0-9A-F]{3}([0-9A-F]{3})?$/', $element['#value'])) {
      form_error($element, t('!name must be a hexadecimal color value.', array('!name' => $element['#title'])));
    }
  }
}

/**
 * Element validate handler to ensure that either a height or a width is
 * specified.
 */
function image_effect_scale_validate($element, &$form_state) {
  if (empty($element['width']['#value']) && empty($element['height']['#value'])) {
    form_error($element, t('Width and height can not both be blank.'));
  }
}

/**
 * Form structure for the image resize form.
 *
 * Note that this is not a complete form, it only contains the portion of the
 * form for configuring the resize options. Therefore it does not not need to
 * include metadata about the effect, nor a submit button.
 *
 * @param $data
 *   The current configuration for this resize effect.
 */
function image_resize_form($data) {
  $form['width'] = array(
    '#type' => 'textfield',
    '#title' => t('Width'),
    '#default_value' => isset($data['width']) ? $data['width'] : '',
    '#field_suffix' => ' ' . t('pixels'),
    '#required' => TRUE,
    '#size' => 10,
    '#element_validate' => array('image_effect_integer_validate'),
    '#allow_negative' => FALSE,
  );
  $form['height'] = array(
    '#type' => 'textfield',
    '#title' => t('Height'),
    '#default_value' => isset($data['height']) ? $data['height'] : '',
    '#field_suffix' => ' ' . t('pixels'),
    '#required' => TRUE,
    '#size' => 10,
    '#element_validate' => array('image_effect_integer_validate'),
    '#allow_negative' => FALSE,
  );
  return $form;
}

/**
 * Form structure for the image scale form.
 *
 * Note that this is not a complete form, it only contains the portion of the
 * form for configuring the scale options. Therefore it does not not need to
 * include metadata about the effect, nor a submit button.
 *
 * @param $data
 *   The current configuration for this scale effect.
 */
function image_scale_form($data) {
  $form = image_resize_form($data);
  $form['#element_validate'] = array('image_effect_scale_validate');
  $form['width']['#required'] = FALSE;
  $form['height']['#required'] = FALSE;
  $form['upscale'] = array(
    '#type' => 'checkbox',
    '#default_value' => (isset($data['upscale'])) ? $data['upscale'] : 0,
    '#title' => t('Allow Upscaling'),
    '#description' => t('Let scale make images larger than their original size'),
  );
  return $form;
}

/**
 * Form structure for the image crop form.
 *
 * Note that this is not a complete form, it only contains the portion of the
 * form for configuring the crop options. Therefore it does not not need to
 * include metadata about the effect, nor a submit button.
 *
 * @param $data
 *   The current configuration for this crop effect.
 */
function image_crop_form($data) {
  $data += array(
    'width' => '',
    'height' => '',
    'anchor' => 'center-center',
  );

  $form = image_resize_form($data);
  $form['anchor'] = array(
    '#type' => 'radios',
    '#title' => t('Anchor'),
    '#options' => array(
      'left-top'      => t('Top') . ' ' . t('Left'),
      'center-top'    => t('Top') . ' ' . t('Center'),
      'right-top'     => t('Top') . ' ' . t('Right'),
      'left-center'   => t('Center') . ' ' . t('Left'),
      'center-center' => t('Center'),
      'right-center'  => t('Center') . ' ' . t('Right'),
      'left-bottom'   => t('Bottom') . ' ' . t('Left'),
      'center-bottom' => t('Bottom') . ' ' . t('Center'),
      'right-bottom'  => t('Bottom') . ' ' . t('Right'),
    ),
    '#theme' => 'image_anchor',
    '#default_value' => $data['anchor'],
    '#description' => t('The part of the image that will be retained during the crop.'),
  );

  return $form;
}

/**
 * Form structure for the image rotate form.
 *
 * Note that this is not a complete form, it only contains the portion of the
 * form for configuring the rotate options. Therefore it does not not need to
 * include metadata about the effect, nor a submit button.
 *
 * @param $data
 *   The current configuration for this rotate effect.
 */
function image_rotate_form($data) {
  $form['degrees'] = array(
    '#type' => 'textfield',
    '#default_value' => (isset($data['degrees'])) ? $data['degrees'] : 0,
    '#title' => t('Rotation angle'),
    '#description' => t('The number of degrees the image should be rotated. Positive numbers are clockwise, negative are counter-clockwise.'),
    '#field_suffix' => '&deg;',
    '#required' => TRUE,
    '#size' => 6,
    '#maxlength' => 4,
    '#element_validate' => array('image_effect_integer_validate'),
    '#allow_negative' => TRUE,
  );
  $form['bgcolor'] = array(
    '#type' => 'textfield',
    '#default_value' => (isset($data['bgcolor'])) ? $data['bgcolor'] : '#FFFFFF',
    '#title' => t('Background color'),
    '#description' => t('The background color to use for exposed areas of the image. Use web-style hex colors (#FFFFFF for white, #000000 for black). Leave blank for transparency on image types that support it.'),
    '#size' => 7,
    '#maxlength' => 7,
    '#element_validate' => array('image_effect_color_validate'),
  );
  $form['random'] = array(
    '#type' => 'checkbox',
    '#default_value' => (isset($data['random'])) ? $data['random'] : 0,
    '#title' => t('Randomize'),
    '#description' => t('Randomize the rotation angle for each image. The angle specified above is used as a maximum.'),
  );
  return $form;
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

  $header = array(t('Style name'), t('Settings'), array('data' => t('Operations'), 'colspan' => 3));
  $rows = array();
  foreach ($styles as $style) {
    $row = array();
    $row[] = l($style['name'], 'admin/config/media/image-styles/edit/' . $style['name']);
    $link_attributes = array(
      'attributes' => array(
        'class' => array('image-style-link'),
      ),
    );
    if ($style['storage'] == IMAGE_STORAGE_NORMAL) {
      $row[] = t('Custom');
      $row[] = l(t('edit'), 'admin/config/media/image-styles/edit/' . $style['name'], $link_attributes);
      $row[] = l(t('delete'), 'admin/config/media/image-styles/delete/' . $style['name'], $link_attributes);
    }
    elseif ($style['storage'] == IMAGE_STORAGE_OVERRIDE) {
      $row[] = t('Overridden');
      $row[] = l(t('edit'), 'admin/config/media/image-styles/edit/' . $style['name'], $link_attributes);
      $row[] = l(t('revert'), 'admin/config/media/image-styles/revert/' . $style['name'], $link_attributes);
    }
    else {
      $row[] = t('Default');
      $row[] = l(t('edit'), 'admin/config/media/image-styles/edit/' . $style['name'], $link_attributes);
      $row[] = '';
    }
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
    if (is_numeric($key)) {
      $summary = drupal_render($form[$key]['summary']);
      $row[] = drupal_render($form[$key]['label']) . (empty($summary) ? '' : ' ' . $summary);
      $row[] = drupal_render($form[$key]['weight']);
      $row[] = drupal_render($form[$key]['configure']);
      $row[] = drupal_render($form[$key]['remove']);
    }
    else {
      // Add the row for adding a new image effect.
      $row[] = '<div class="image-style-new">' . drupal_render($form['new']['new']) . drupal_render($form['new']['add']) . '</div>';
      $row[] = drupal_render($form['new']['weight']);
      $row[] = array('data' => '', 'colspan' => 2);
    }

    if (!isset($form[$key]['#access']) || $form[$key]['#access']) {
      $rows[] = array(
        'data' => $row,
        'class' => !empty($form[$key]['weight']['#access']) || $key == 'new' ? array('draggable') : array(),
      );
    }
  }

  $header = array(
    t('Effect'),
    t('Weight'),
    array('data' => t('Operations'), 'colspan' => 2),
  );

  if (count($rows) == 1 && $form['new']['#access']) {
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
 *   - style: The image style array being previewed.
 *
 * @ingroup themeable
 */
function theme_image_style_preview($variables) {
  $style = $variables['style'];

  $sample_image = variable_get('image_style_preview_image', drupal_get_path('module', 'image') . '/sample.png');
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
  $preview_file = image_style_path($style['name'], $original_path);
  if (!file_exists($preview_file)) {
    image_style_create_derivative($style, $original_path, $preview_file);
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
  $output .= '<a href="' . $original_url . '">' . theme('image', array('path' => $original_path, 'alt' => t('Sample original image'), 'title' => '', 'attributes' => $original_attributes)) . '</a>';
  $output .= '<div class="height" style="height: ' . $original_height . 'px"><span>' . $original_image['height'] . 'px</span></div>';
  $output .= '<div class="width" style="width: ' . $original_width . 'px"><span>' . $original_image['width'] . 'px</span></div>';
  $output .= '</div>'; // End preview-image.
  $output .= '</div>'; // End preview-image-wrapper.

  // Build the preview of the image style.
  $preview_url = file_create_url($preview_file) . '?cache_bypass=' . REQUEST_TIME;
  $output .= '<div class="preview-image-wrapper">';
  $output .= check_plain($style['name']) . ' (' . l(t('view actual size'), file_create_url($preview_file) . '?' . time()) . ')';
  $output .= '<div class="preview-image modified-image" style="' . $preview_attributes['style'] . '">';
  $output .= '<a href="' . file_create_url($preview_file) . '?' . time() . '">' . theme('image', array('path' => $preview_url, 'alt' => t('Sample modified image'), 'title' => '', 'attributes' => $preview_attributes)) . '</a>';
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
  return theme('image_resize_summary', $variables);
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
