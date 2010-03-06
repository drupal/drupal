<?php
// $Id: image.field.inc,v 1.18 2010/03/06 07:19:57 dries Exp $

/**
 * @file
 * Implement an image field, based on the file module's file field.
 */

/**
 * Implements hook_field_info().
 */
function image_field_info() {
  return array(
    'image' => array(
      'label' => t('Image'),
      'description' => t('This field stores the ID of an image file as an integer value.'),
      'settings' => array(
        'uri_scheme' => 'public',
        'default_image' => 0,
      ),
      'instance_settings' => array(
        'file_extensions' => 'png gif jpg jpeg',
        'file_directory' => '',
        'max_filesize' => '',
        'alt_field' => 0,
        'title_field' => 0,
        'max_resolution' => '',
        'min_resolution' => '',
      ),
      'default_widget' => 'image_image',
      'default_formatter' => 'image',
    ),
  );
}

/**
 * Implements hook_field_schema().
 */
function image_field_schema($field) {
  return array(
    'columns' => array(
      'fid' => array(
        'description' => 'The {files}.fid being referenced in this field.',
        'type' => 'int',
        'not null' => FALSE,
        'unsigned' => TRUE,
      ),
      'alt' => array(
        'description' => "Alternative image text, for the image's 'alt' attribute.",
        'type' => 'varchar',
        'length' => 128,
        'not null' => FALSE,
      ),
      'title' => array(
        'description' => "Image title text, for the image's 'title' attribute.",
        'type' => 'varchar',
        'length' => 128,
        'not null' => FALSE,
      ),
    ),
    'indexes' => array(
      'fid' => array('fid'),
    ),
  );
}

/**
 * Implements hook_field_settings_form().
 */
function image_field_settings_form($field, $instance) {
  $defaults = field_info_field_settings($field['type']);
  $settings = array_merge($defaults, $field['settings']);

  $scheme_options = array();
  foreach (file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE) as $scheme => $stream_wrapper) {
    $scheme_options[$scheme] = $stream_wrapper['name'];
  }
  $form['uri_scheme'] = array(
    '#type' => 'radios',
    '#title' => t('Upload destination'),
    '#options' => $scheme_options,
    '#default_value' => $settings['uri_scheme'],
    '#description' => t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
  );

  $form['default_image'] = array(
    '#title' => t('Default image'),
    '#type' => 'managed_file',
    '#description' => t('If no image is uploaded, this image will be shown on display.'),
    '#default_value' => $field['settings']['default_image'],
    '#upload_location' => 'public://default_images/',
  );

  return $form;

}

/**
 * Implements hook_field_instance_settings_form().
 */
function image_field_instance_settings_form($field, $instance) {
  $settings = $instance['settings'];

  // Use the file field instance settings form as a basis.
  $form = file_field_instance_settings_form($field, $instance);

  // Add maximum and minimum resolution settings.
  $max_resolution = explode('x', $settings['max_resolution']) + array('', '');
  $form['max_resolution'] = array(
    '#title' => t('Maximum image resolution'),
    '#element_validate' => array('_image_field_resolution_validate'),
    '#theme_wrappers' => array('form_element'),
    '#weight' => 4.1,
    '#description' => t('The maximum allowed image size expressed as WIDTHxHEIGHT (e.g. 640x480). Leave blank for no restriction. If a larger image is uploaded, it will be resized to reflect the given width and height. Resizing images on upload will cause the loss of <a href="http://en.wikipedia.org/wiki/Exchangeable_image_file_format">EXIF data</a> in the image.'),
  );
  $form['max_resolution']['x'] = array(
    '#type' => 'textfield',
    '#default_value' => $max_resolution[0],
    '#size' => 5,
    '#maxlength' => 5,
    '#field_suffix' => ' x ',
    '#theme_wrappers' => array(),
  );
  $form['max_resolution']['y'] = array(
    '#type' => 'textfield',
    '#default_value' => $max_resolution[1],
    '#size' => 5,
    '#maxlength' => 5,
    '#field_suffix' => ' ' . t('pixels'),
    '#theme_wrappers' => array(),
  );

  $min_resolution = explode('x', $settings['min_resolution']) + array('', '');
  $form['min_resolution'] = array(
    '#title' => t('Minimum image resolution'),
    '#element_validate' => array('_image_field_resolution_validate'),
    '#theme_wrappers' => array('form_element'),
    '#weight' => 4.2,
    '#description' => t('The minimum allowed image size expressed as WIDTHxHEIGHT (e.g. 640x480). Leave blank for no restriction. If a smaller image is uploaded, it will be rejected.'),
  );
  $form['min_resolution']['x'] = array(
    '#type' => 'textfield',
    '#default_value' => $min_resolution[0],
    '#size' => 5,
    '#maxlength' => 5,
    '#field_suffix' => ' x ',
    '#theme_wrappers' => array(),
  );
  $form['min_resolution']['y'] = array(
    '#type' => 'textfield',
    '#default_value' => $min_resolution[1],
    '#size' => 5,
    '#maxlength' => 5,
    '#field_suffix' => ' ' . t('pixels'),
    '#theme_wrappers' => array(),
  );

  // Remove the description option.
  unset($form['description_field']);

  // Add title and alt configuration options.
  $form['alt_field'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable <em>Alt</em> field'),
    '#default_value' => $settings['alt_field'],
    '#description' => t('The alt attribute may be used by search engines, screen readers, and when the image cannot be loaded.'),
    '#weight' => 10,
  );
  $form['title_field'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable <em>Title</em> field'),
    '#default_value' => $settings['title_field'],
    '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image.'),
    '#weight' => 11,
  );

  return $form;
}

/**
 * Element validate function for resolution fields.
 */
function _image_field_resolution_validate($element, &$form_state) {
  if (!empty($element['x']['#value']) || !empty($element['y']['#value'])) {
    foreach (array('x', 'y') as $dimension) {
      $value = $element[$dimension]['#value'];
      if (!is_numeric($value)) {
        form_error($element[$dimension], t('Height and width values must be numeric.'));
        return;
      }
      if (intval($value) == 0) {
        form_error($element[$dimension], t('Both a height and width value must be specified in the !name field.', array('!name' => $element['#title'])));
        return;
      }
    }
    form_set_value($element, intval($element['x']['#value']) . 'x' . intval($element['y']['#value']), $form_state);
  }
  else {
    form_set_value($element, '', $form_state);
  }
}

/**
 * Implements hook_field_load().
 */
function image_field_load($entity_type, $entities, $field, $instances, $langcode, &$items, $age) {
  file_field_load($entity_type, $entities, $field, $instances, $langcode, $items, $age);
}

/**
 * Implements hook_field_prepare_view().
 */
function image_field_prepare_view($entity_type, $entities, $field, $instances, $langcode, &$items) {
  // If there are no files specified at all, use the default.
  foreach ($entities as $id => $entity) {
    if (empty($items[$id]) && $field['settings']['default_image']) {
      if ($file = file_load($field['settings']['default_image'])) {
        $items[$id][0] = (array) $file + array(
          'is_default' => TRUE,
          'alt' => '',
          'title' => '',
        );
      }
    }
  }
}

/**
 * Implements hook_field_presave().
 */
function image_field_presave($obj_type, $object, $field, $instance, $langcode, &$items) {
  file_field_presave($obj_type, $object, $field, $instance, $langcode, $items);
}

/**
 * Implements hook_field_update().
 */
function image_field_update($entity_type, $entity, $field, $instance, $langcode, &$items) {
  file_field_update($entity_type, $entity, $field, $instance, $langcode, $items);
}

/**
 * Implements hook_field_delete().
 */
function image_field_delete($entity_type, $entity, $field, $instance, $langcode, &$items) {
  file_field_delete($entity_type, $entity, $field, $instance, $langcode, $items);
}

/**
 * Implements hook_field_delete_revision().
 */
function image_field_delete_revision($entity_type, $entity, $field, $instance, $langcode, &$items) {
  file_field_delete_revision($entity_type, $entity, $field, $instance, $langcode, $items);
}

/**
 * Implements hook_field_is_empty().
 */
function image_field_is_empty($item, $field) {
  return file_field_is_empty($item, $field);
}

/**
 * Implements hook_field_widget_info().
 */
function image_field_widget_info() {
  return array(
    'image_image' => array(
      'label' => t('Image'),
      'field types' => array('image'),
      'settings' => array(
        'progress_indicator' => 'throbber',
        'preview_image_style' => 'thumbnail',
      ),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_CUSTOM,
        'default value' => FIELD_BEHAVIOR_NONE,
      ),
    ),
  );
}

/**
 * Implements hook_field_widget_settings_form().
 */
function image_field_widget_settings_form($field, $instance) {
  $widget = $instance['widget'];
  $settings = $widget['settings'];

  // Use the file widget settings form.
  $form = file_field_widget_settings_form($field, $instance);

  $form['preview_image_style'] = array(
    '#title' => t('Preview image style'),
    '#type' => 'select',
    '#options' => array('' => '<' . t('no preview') . '>') + image_style_options(FALSE),
    '#default_value' => $settings['preview_image_style'],
    '#description' => t('The preview image will be shown while editing the content.'),
    '#weight' => 15,
  );

  return $form;
}

/**
 * Implements hook_field_widget_form().
 */
function image_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {

  // Add display_field setting to field because file_field_widget_form() assumes it is set.
  $field['settings']['display_field'] = 0;

  $elements = file_field_widget_form($form, $form_state, $field, $instance, $langcode, $items, $delta, $element);
  $settings = $instance['settings'];

  foreach (element_children($elements) as $delta) {
    // Add upload resolution validation.
    if ($settings['max_resolution'] || $settings['min_resolution']) {
      $elements[$delta]['#upload_validators']['file_validate_image_resolution'] = array($settings['max_resolution'], $settings['min_resolution']);
    }

    // If not using custom extension validation, ensure this is an image.
    $supported_extensions = array('png', 'gif', 'jpg', 'jpeg');
    $extensions = isset($elements[$delta]['#upload_validators']['file_validate_extensions'][0]) ? $elements[$delta]['#upload_validators']['file_validate_extensions'][0] : implode(' ', $supported_extensions);
    $extensions = array_intersect(explode(' ', $extensions), $supported_extensions);
    $elements[$delta]['#upload_validators']['file_validate_extensions'][0] = implode(' ', $extensions);

    // Add all extra functionality provided by the image widget.
    $elements[$delta]['#process'][] = 'image_field_widget_process';
  }

  if ($field['cardinality'] == 1) {
    // If there's only one field, return it as delta 0.
    if (empty($elements[0]['#default_value']['fid'])) {
      $elements[0]['#description'] = theme('file_upload_help', array('description' => $instance['description'], 'upload_validators' => $elements[0]['#upload_validators']));
    }
  }
  else {
    $elements['#file_upload_description'] = theme('file_upload_help', array('upload_validators' => $elements[0]['#upload_validators']));
  }
  return $elements;
}

/**
 * An element #process callback for the image_image field type.
 *
 * Expands the image_image type to include the alt and title fields.
 */
function image_field_widget_process($element, &$form_state, $form) {
  $item = $element['#value'];
  $item['fid'] = $element['fid']['#value'];

  $instance = $form_state['field'][$element['#field_name']][$element['#language']]['instance'];

  $settings = $instance['settings'];
  $widget_settings = $instance['widget']['settings'];

  $element['#theme'] = 'image_widget';
  $element['#attached']['css'][] = drupal_get_path('module', 'image') . '/image.css';

  // Add the image preview.
  if ($element['#file'] && $widget_settings['preview_image_style']) {
    $element['preview'] = array(
      '#type' => 'markup',
      '#markup' => theme('image_style', array('style_name' => $widget_settings['preview_image_style'], 'path' => $element['#file']->uri, 'getsize' => FALSE)),
    );
  }

  // Add the additional alt and title fields.
  $element['alt'] = array(
    '#title' => t('Alternate text'),
    '#type' => 'textfield',
    '#default_value' => isset($item['alt']) ? $item['alt'] : '',
    '#description' => t('This text will be used by screen readers, search engines, or when the image cannot be loaded.'),
    '#maxlength' => variable_get('image_alt_length', 80), // See http://www.gawds.org/show.php?contentid=28.
    '#weight' => -2,
    '#access' => (bool) $item['fid'] && $settings['alt_field'],
  );
  $element['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => isset($item['title']) ? $item['title'] : '',
    '#description' => t('The title is used as a tool tip when the user hovers the mouse over the image.'),
    '#maxlength' => variable_get('image_title_length', 500),
    '#weight' => -1,
    '#access' => (bool) $item['fid'] && $settings['title_field'],
  );

  return $element;
}

/**
 * Theme the display of the image field widget.
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
  if ($element['fid']['#value'] != 0) {
    $element['filename']['#markup'] .= ' <span class="file-size">(' . format_size($element['#file']->filesize) . ')</span> ';
  }
  $output .= drupal_render_children($element);
  $output .= '</div>';
  $output .= '</div>';

  return $output;
}

/**
 * Implements hook_field_formatter_info().
 */
function image_field_formatter_info() {
  $formatters = array(
    'image' => array(
      'label' => t('Image'),
      'field types' => array('image'),
    ),
    'image_link_content' => array(
      'label' => t('Image linked to content'),
      'field types' => array('image'),
    ),
    'image_link_file' => array(
      'label' => t('Image linked to file'),
      'field types' => array('image'),
    ),
  );

  foreach (image_styles() as $style) {
    $formatters['image__' . $style['name']] = array(
      'label' => t('Image "@style"', array('@style' => $style['name'])),
      'field types' => array('image'),
    );
    $formatters['image_link_content__' . $style['name']] = array(
      'label' => t('Image "@style" linked to content', array('@style' => $style['name'])),
      'field types' => array('image'),
    );
    $formatters['image_link_file__' . $style['name']] = array(
      'label' => t('Image "@style" linked to file', array('@style' => $style['name'])),
      'field types' => array('image'),
    );
  }

  return $formatters;
}

/**
 * Implements hook_field_formatter_view().
 */
function image_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $element = array();

  // Check if the formatter involves a particular image style.
  $matches = array();
  if (preg_match('/__([a-z0-9_]+)/', $display['type'], $matches)) {
    $image_style = $matches[1];
  }

  // Check if the formatter involves a link.
  if (strpos($display['type'], 'image_link_content') === 0) {
    $uri = entity_uri($entity_type, $entity);
  }
  elseif (strpos($display['type'], 'image_link_file') === 0) {
    $link_file = TRUE;
  }

  foreach ($items as $delta => $item) {
    if (isset($link_file)) {
      $uri = array(
        'path' => file_create_url($item['uri']),
        'options' => array(),
      );
    }
    $element[$delta] = array(
      '#theme' => 'image_formatter',
      '#item' => $item,
      '#image_style' => isset($image_style) ? $image_style : '',
      '#path' => isset($uri) ? $uri : '',
    );
  }

  return $element;
}

/**
 * Theme function for image field formatters.
 */
function theme_image_formatter($variables) {
  $item = $variables['item'];
  $image = array(
    'path' => $item['uri'],
    'alt' => $item['alt'],
    'title' => $item['title'],
  );

  if ($variables['image_style']) {
    $image['style_name'] = $variables['image_style'];
    $output = theme('image_style', $image);
  }
  else {
    $output = theme('image', $image);
  }

  if ($variables['path']) {
    $path = $variables['path']['path'];
    $options = $variables['path']['options'];
    // When displaying an image inside a link, the html option must be TRUE.
    $options['html'] = TRUE;
    $output = l($output, $path, $options);
  }

  return $output;
}
