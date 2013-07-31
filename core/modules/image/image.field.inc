<?php

/**
 * @file
 * Implement an image field, based on the file module's file field.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_field_info().
 */
function image_field_info() {
  return array(
    'image' => array(
      'label' => t('Image'),
      'description' => t('This field stores the ID of an image file as an integer value.'),
      'settings' => array(
        'uri_scheme' => file_default_scheme(),
        'default_image' => 0,
        'column_groups' => array(
          'file' => array(
            'label' => t('File'),
            'columns' => array('target_id', 'width', 'height'),
          ),
          'alt' => array(
            'label' => t('Alt'),
            'translatable' => TRUE,
          ),
          'title' => array(
            'label' => t('Title'),
            'translatable' => TRUE,
          ),
        ),
      ),
      'instance_settings' => array(
        'file_extensions' => 'png gif jpg jpeg',
        'file_directory' => '',
        'max_filesize' => '',
        'alt_field' => 0,
        'alt_field_required' => 0,
        'title_field' => 0,
        'title_field_required' => 0,
        'max_resolution' => '',
        'min_resolution' => '',
        'default_image' => 0,
      ),
      'default_widget' => 'image_image',
      'default_formatter' => 'image',
      'class' => '\Drupal\image\Type\ImageItem',
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

  // When the user sets the scheme on the UI, even for the first time, it's
  // updating a field because fields are created on the "Manage fields"
  // page. So image_field_update_field() can handle this change.
  $form['default_image'] = array(
    '#title' => t('Default image'),
    '#type' => 'managed_file',
    '#description' => t('If no image is uploaded, this image will be shown on display.'),
    '#default_value' => empty($field['settings']['default_image']) ? array() : array($field['settings']['default_image']),
    '#upload_location' => $settings['uri_scheme'] . '://default_images/',
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
    '#type' => 'item',
    '#title' => t('Maximum image resolution'),
    '#element_validate' => array('_image_field_resolution_validate'),
    '#weight' => 4.1,
    '#field_prefix' => '<div class="container-inline">',
    '#field_suffix' => '</div>',
    '#description' => t('The maximum allowed image size expressed as WIDTHxHEIGHT (e.g. 640x480). Leave blank for no restriction. If a larger image is uploaded, it will be resized to reflect the given width and height. Resizing images on upload will cause the loss of <a href="@url">EXIF data</a> in the image.', array('@url' => 'http://en.wikipedia.org/wiki/Exchangeable_image_file_format')),
  );
  $form['max_resolution']['x'] = array(
    '#type' => 'number',
    '#title' => t('Maximum width'),
    '#title_display' => 'invisible',
    '#default_value' => $max_resolution[0],
    '#min' => 1,
    '#field_suffix' => ' x ',
  );
  $form['max_resolution']['y'] = array(
    '#type' => 'number',
    '#title' => t('Maximum height'),
    '#title_display' => 'invisible',
    '#default_value' => $max_resolution[1],
    '#min' => 1,
    '#field_suffix' => ' ' . t('pixels'),
  );

  $min_resolution = explode('x', $settings['min_resolution']) + array('', '');
  $form['min_resolution'] = array(
    '#type' => 'item',
    '#title' => t('Minimum image resolution'),
    '#element_validate' => array('_image_field_resolution_validate'),
    '#weight' => 4.2,
    '#field_prefix' => '<div class="container-inline">',
    '#field_suffix' => '</div>',
    '#description' => t('The minimum allowed image size expressed as WIDTHxHEIGHT (e.g. 640x480). Leave blank for no restriction. If a smaller image is uploaded, it will be rejected.'),
  );
  $form['min_resolution']['x'] = array(
    '#type' => 'number',
    '#title' => t('Minimum width'),
    '#title_display' => 'invisible',
    '#default_value' => $min_resolution[0],
    '#min' => 1,
    '#field_suffix' => ' x ',
  );
  $form['min_resolution']['y'] = array(
    '#type' => 'number',
    '#title' => t('Minimum height'),
    '#title_display' => 'invisible',
    '#default_value' => $min_resolution[1],
    '#min' => 1,
    '#field_suffix' => ' ' . t('pixels'),
  );

  // Remove the description option.
  unset($form['description_field']);

  // Add title and alt configuration options.
  $form['alt_field'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable <em>Alt</em> field'),
    '#default_value' => $settings['alt_field'],
    '#description' => t('The alt attribute may be used by search engines, screen readers, and when the image cannot be loaded.'),
    '#weight' => 9,
  );
  $form['alt_field_required'] = array(
    '#type' => 'checkbox',
    '#title' => t('<em>Alt</em> field required'),
    '#default_value' => $settings['alt_field_required'],
    '#weight' => 10,
    '#states' => array(
      'visible' => array(
        ':input[name="instance[settings][alt_field]"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['title_field'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable <em>Title</em> field'),
    '#default_value' => $settings['title_field'],
    '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image.'),
    '#weight' => 11,
  );
  $form['title_field_required'] = array(
    '#type' => 'checkbox',
    '#title' => t('<em>Title</em> field required'),
    '#default_value' => $settings['title_field_required'],
    '#weight' => 12,
    '#states' => array(
      'visible' => array(
        ':input[name="instance[settings][title_field]"]' => array('checked' => TRUE),
      ),
    ),
  );


  // Add the default image to the instance.
  $form['default_image'] = array(
    '#title' => t('Default image'),
    '#type' => 'managed_file',
    '#description' => t("If no image is uploaded, this image will be shown on display and will override the field's default image."),
    '#default_value' => empty($settings['default_image']) ? array() : array($settings['default_image']),
    '#upload_location' => $field['settings']['uri_scheme'] . '://default_images/',
  );

  return $form;
}

/**
 * Element validate function for resolution fields.
 */
function _image_field_resolution_validate($element, &$form_state) {
  if (!empty($element['x']['#value']) || !empty($element['y']['#value'])) {
    foreach (array('x', 'y') as $dimension) {
      if (!$element[$dimension]['#value']) {
        form_error($element[$dimension], t('Both a height and width value must be specified in the !name field.', array('!name' => $element['#title'])));
        return;
      }
    }
    form_set_value($element, $element['x']['#value'] . 'x' . $element['y']['#value'], $form_state);
  }
  else {
    form_set_value($element, '', $form_state);
  }
}

/**
 * Implements hook_field_presave().
 */
function image_field_presave(EntityInterface $entity, $field, $instance, $langcode, &$items) {

  // Determine the dimensions if necessary.
  foreach ($items as &$item) {
    if (!isset($item['width']) || !isset($item['height'])) {
      $info = image_get_info(file_load($item['target_id'])->getFileUri());

      if (is_array($info)) {
        $item['width'] = $info['width'];
        $item['height'] = $info['height'];
      }
    }
  }
}

/**
 * Implements hook_field_insert().
 */
function image_field_insert(EntityInterface $entity, $field, $instance, $langcode, &$items) {
  file_field_insert($entity, $field, $instance, $langcode, $items);
}

/**
 * Implements hook_field_update().
 */
function image_field_update(EntityInterface $entity, $field, $instance, $langcode, &$items) {
  file_field_update($entity, $field, $instance, $langcode, $items);
}

/**
 * Implements hook_field_delete().
 */
function image_field_delete(EntityInterface $entity, $field, $instance, $langcode, &$items) {
  file_field_delete($entity, $field, $instance, $langcode, $items);
}

/**
 * Implements hook_field_delete_revision().
 */
function image_field_delete_revision(EntityInterface $entity, $field, $instance, $langcode, &$items) {
  file_field_delete_revision($entity, $field, $instance, $langcode, $items);
}

/**
 * Implements hook_field_is_empty().
 */
function image_field_is_empty($item, $field_type) {
  return file_field_is_empty($item, $field_type);
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
      $info = image_get_info($file->getFileUri());

      if (is_array($info)) {
        $variables['width'] = $info['width'];
        $variables['height'] = $info['height'];
      }
      else {
        $variables['width'] = $variables['height'] = NULL;
      }
    }

    $element['preview'] = array(
      '#markup' => theme('image_style', $variables),
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
    // @see http://www.gawds.org/show.php?contentid=28
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
      form_error($element, t('The field !title is required', array('!title' => $element['#title'])));
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
    if (isset($item[$key])) {
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
