<?php

/**
 * @file
 * Administration functions for editor.module.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\editor\Entity\Editor;

/**
 * Subform constructor to configure the text editor's image upload settings.
 *
 * Each text editor plugin that is configured to offer the ability to insert
 * images and uses EditorImageDialog for that, should use this form to update
 * the text editor's configuration so that EditorImageDialog knows whether it
 * should allow the user to upload images.
 *
 * @param \Drupal\editor\Entity\Editor $editor
 *   The text editor entity that is being edited.
 *
 * @return array
 *   The image upload settings form.
 *
 * @see \Drupal\editor\Form\EditorImageDialog
 */
function editor_image_upload_settings_form(Editor $editor) {
  // Defaults.
  $image_upload = $editor->getImageUploadSettings();
  $image_upload += array(
    'status' => FALSE,
    'scheme' => file_default_scheme(),
    'directory' => 'inline-images',
    'max_size' => '',
    'max_dimensions' => array('width' => '', 'height' => ''),
  );

  $form['status'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable image uploads'),
    '#default_value' => $image_upload['status'],
    '#attributes' => array(
      'data-editor-image-upload' => 'status',
    ),
  );
  $show_if_image_uploads_enabled = array(
    'visible' => array(
      ':input[data-editor-image-upload="status"]' => array('checked' => TRUE),
    ),
  );

  // Any visible, writable wrapper can potentially be used for uploads,
  // including a remote file system that integrates with a CDN.
  $stream_wrappers = file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE);
  foreach ($stream_wrappers as $scheme => $info) {
    $options[$scheme] = $info['description'];
  }
  if (!empty($options)) {
    $form['scheme'] = array(
      '#type' => 'radios',
      '#title' => t('File storage'),
      '#default_value' => $image_upload['scheme'],
      '#options' => $options,
      '#states' => $show_if_image_uploads_enabled,
      '#access' => count($options) > 1,
    );
  }
  // Set data- attributes with human-readable names for all possible stream
  // wrappers, so that drupal.ckeditor.drupalimage.admin's summary rendering
  // can use that.
  foreach ($stream_wrappers as $scheme => $info) {
    $form['scheme'][$scheme]['#attributes']['data-label'] = t('Storage: @name', array('@name' => $info['name']));
  }

  $form['directory'] = array(
    '#type' => 'textfield',
    '#default_value' => $image_upload['directory'],
    '#title' => t('Upload directory'),
    '#description' => t("A directory relative to Drupal's files directory where uploaded images will be stored."),
    '#states' => $show_if_image_uploads_enabled,
  );

  $default_max_size = format_size(file_upload_max_size());
  $form['max_size'] = array(
    '#type' => 'textfield',
    '#default_value' => $image_upload['max_size'],
    '#title' => t('Maximum file size'),
    '#description' => t('If this is left empty, then the file size will be limited by the PHP maximum upload size of @size.', array('@size' => $default_max_size)),
    '#maxlength' => 20,
    '#size' => 10,
    '#placeholder' => $default_max_size,
    '#states' => $show_if_image_uploads_enabled,
  );

  $form['max_dimensions'] = array(
    '#type' => 'item',
    '#title' => t('Maximum dimensions'),
    '#field_prefix' => SafeMarkup::set('<div class="container-inline clearfix">'),
    '#field_suffix' => SafeMarkup::set('</div>'),
    '#description' => t('Images larger than these dimensions will be scaled down.'),
    '#states' => $show_if_image_uploads_enabled,
  );
  $form['max_dimensions']['width'] = array(
    '#title' => t('Width'),
    '#title_display' => 'invisible',
    '#type' => 'number',
    '#default_value' => (empty($image_upload['max_dimensions']['width'])) ? '' : $image_upload['max_dimensions']['width'],
    '#size' => 8,
    '#maxlength' => 8,
    '#min' => 1,
    '#max' => 99999,
    '#placeholder' => t('width'),
    '#field_suffix' => ' x ',
    '#states' => $show_if_image_uploads_enabled,
  );
  $form['max_dimensions']['height'] = array(
    '#title' => t('Height'),
    '#title_display' => 'invisible',
    '#type' => 'number',
    '#default_value' => (empty($image_upload['max_dimensions']['height'])) ? '' : $image_upload['max_dimensions']['height'],
    '#size' => 8,
    '#maxlength' => 8,
    '#min' => 1,
    '#max' => 99999,
    '#placeholder' => t('height'),
    '#field_suffix' => t('pixels'),
    '#states' => $show_if_image_uploads_enabled,
  );

  return $form;
}
