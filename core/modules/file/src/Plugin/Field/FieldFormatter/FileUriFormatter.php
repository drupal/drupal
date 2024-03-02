<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formatter to render the file URI to its download path.
 */
#[FieldFormatter(
  id: 'file_uri',
  label: new TranslatableMarkup('File URI'),
  field_types: [
    'uri',
    'file_uri',
  ],
)]
class FileUriFormatter extends BaseFieldFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    $settings['file_download_path'] = FALSE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['file_download_path'] = [
      '#title' => $this->t('Display the file download URI'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('file_download_path'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    $value = $item->value;
    if ($this->getSetting('file_download_path')) {
      $value = $this->fileUrlGenerator->generateString($value);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return parent::isApplicable($field_definition) && $field_definition->getName() === 'uri';
  }

}
