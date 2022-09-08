<?php

/**
 * @file
 * Administration functions for editor.module.
 */

use Drupal\Component\Utility\Environment;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
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
  $image_upload += [
    'status' => FALSE,
    'scheme' => \Drupal::config('system.file')->get('default_scheme'),
    'directory' => 'inline-images',
    'max_size' => '',
    'max_dimensions' => ['width' => '', 'height' => ''],
  ];

  $form['status'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable image uploads'),
    '#default_value' => $image_upload['status'],
    '#attributes' => [
      'data-editor-image-upload' => 'status',
    ],
  ];
  $show_if_image_uploads_enabled = [
    'visible' => [
      ':input[data-editor-image-upload="status"]' => ['checked' => TRUE],
    ],
  ];

  // Any visible, writable wrapper can potentially be used for uploads,
  // including a remote file system that integrates with a CDN.
  $options = \Drupal::service('stream_wrapper_manager')->getDescriptions(StreamWrapperInterface::WRITE_VISIBLE);
  if (!empty($options)) {
    $form['scheme'] = [
      '#type' => 'radios',
      '#title' => t('File storage'),
      '#default_value' => $image_upload['scheme'],
      '#options' => $options,
      '#states' => $show_if_image_uploads_enabled,
      '#access' => count($options) > 1,
    ];
  }
  // Set data- attributes with human-readable names for all possible stream
  // wrappers, so that it can be used by the summary rendering of other code.
  foreach (\Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE) as $scheme => $name) {
    $form['scheme'][$scheme]['#attributes']['data-label'] = t('Storage: @name', ['@name' => $name]);
  }

  $form['directory'] = [
    '#type' => 'textfield',
    '#default_value' => $image_upload['directory'],
    '#title' => t('Upload directory'),
    '#description' => t("A directory relative to Drupal's files directory where uploaded images will be stored."),
    '#states' => $show_if_image_uploads_enabled,
  ];

  $default_max_size = format_size(Environment::getUploadMaxSize());
  $form['max_size'] = [
    '#type' => 'textfield',
    '#default_value' => $image_upload['max_size'],
    '#title' => t('Maximum file size'),
    '#description' => t('If this is left empty, then the file size will be limited by the PHP maximum upload size of @size.', ['@size' => $default_max_size]),
    '#maxlength' => 20,
    '#size' => 10,
    '#placeholder' => $default_max_size,
    '#states' => $show_if_image_uploads_enabled,
  ];

  $form['max_dimensions'] = [
    '#type' => 'item',
    '#title' => t('Maximum dimensions'),
    '#description' => t('Images larger than these dimensions will be scaled down.'),
    '#states' => $show_if_image_uploads_enabled,
  ];
  $form['max_dimensions']['width'] = [
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
    '#prefix' => '<div class="form--inline clearfix">',
  ];
  $form['max_dimensions']['height'] = [
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
    '#suffix' => '</div>',
  ];

  return $form;
}
