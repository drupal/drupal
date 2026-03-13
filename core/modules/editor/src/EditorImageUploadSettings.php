<?php

declare(strict_types=1);

namespace Drupal\editor;

use Drupal\Component\Utility\Environment;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Subform helper to configure the text editor's image upload settings.
 *
 * Each text editor plugin configured to offer the ability to insert images,
 * should use this form to update the text editor's configuration so that it
 * knows whether it should allow the user to upload images.
 */
class EditorImageUploadSettings {

  use StringTranslationTrait;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected StreamWrapperManagerInterface $streamWrapperManager,
  ) {}

  /**
   * Returns the image upload settings subform render array.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   The text editor entity that is being edited.
   *
   * @return array
   *   The image upload settings form.
   */
  public function getForm(EditorInterface $editor): array {
    // Defaults.
    $imageUpload = $editor->getImageUploadSettings();
    $imageUpload += [
      'status' => FALSE,
      'scheme' => $this->configFactory->get('system.file')->get('default_scheme'),
      'directory' => 'inline-images',
      'max_size' => '',
      'max_dimensions' => ['width' => '', 'height' => ''],
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable image uploads'),
      '#default_value' => $imageUpload['status'],
      '#attributes' => [
        'data-editor-image-upload' => 'status',
      ],
      '#description' => $this->t('When enabled, images can only be uploaded. When disabled, images can only be added by URL.'),
    ];
    $showIfImageUploadsEnabled = [
      'visible' => [
        ':input[data-editor-image-upload="status"]' => ['checked' => TRUE],
      ],
    ];

    // Any visible, writable wrapper can potentially be used for uploads,
    // including a remote file system that integrates with a CDN.
    $options = $this->streamWrapperManager->getDescriptions(StreamWrapperInterface::WRITE_VISIBLE);
    if (!empty($options)) {
      $form['scheme'] = [
        '#type' => 'radios',
        '#title' => $this->t('File storage'),
        '#default_value' => $imageUpload['scheme'],
        '#options' => $options,
        '#states' => $showIfImageUploadsEnabled,
        '#access' => count($options) > 1,
      ];
    }
    // Set `data-*` attributes with human-readable names for all possible stream
    // wrappers so that it can be used by the summary rendering of other code.
    foreach ($this->streamWrapperManager->getNames(StreamWrapperInterface::WRITE_VISIBLE) as $scheme => $name) {
      $form['scheme'][$scheme]['#attributes']['data-label'] = $this->t('Storage: @name', ['@name' => $name]);
    }

    $form['directory'] = [
      '#type' => 'textfield',
      '#default_value' => $imageUpload['directory'],
      '#title' => $this->t('Upload directory'),
      '#description' => $this->t("A directory relative to Drupal's files directory where uploaded images will be stored."),
      '#states' => $showIfImageUploadsEnabled,
    ];

    $defaultMaxSize = ByteSizeMarkup::create(Environment::getUploadMaxSize());
    $form['max_size'] = [
      '#type' => 'textfield',
      '#default_value' => $imageUpload['max_size'],
      '#title' => $this->t('Maximum file size'),
      '#description' => $this->t('If this is left empty, then the file size will be limited by the PHP maximum upload size of @size.', ['@size' => $defaultMaxSize]),
      '#maxlength' => 20,
      '#size' => 10,
      '#placeholder' => $defaultMaxSize,
      '#states' => $showIfImageUploadsEnabled,
    ];

    $form['max_dimensions'] = [
      '#type' => 'item',
      '#title' => $this->t('Maximum dimensions'),
      '#description' => $this->t('Images larger than these dimensions will be scaled down.'),
      '#states' => $showIfImageUploadsEnabled,
    ];
    $form['max_dimensions']['width'] = [
      '#title' => $this->t('Width'),
      '#title_display' => 'invisible',
      '#type' => 'number',
      '#default_value' => (empty($imageUpload['max_dimensions']['width'])) ? '' : $imageUpload['max_dimensions']['width'],
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#placeholder' => $this->t('width'),
      '#field_suffix' => ' x ',
      '#states' => $showIfImageUploadsEnabled,
      '#prefix' => '<div class="form--inline clearfix">',
    ];
    $form['max_dimensions']['height'] = [
      '#title' => $this->t('Height'),
      '#title_display' => 'invisible',
      '#type' => 'number',
      '#default_value' => (empty($imageUpload['max_dimensions']['height'])) ? '' : $imageUpload['max_dimensions']['height'],
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#placeholder' => $this->t('height'),
      '#field_suffix' => $this->t('pixels'),
      '#states' => $showIfImageUploadsEnabled,
      '#suffix' => '</div>',
    ];

    return $form;
  }

}
