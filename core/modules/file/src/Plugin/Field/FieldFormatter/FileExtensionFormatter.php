<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formatter to render a filename as file extension.
 */
#[FieldFormatter(
  id: 'file_extension',
  label: new TranslatableMarkup('File extension'),
  field_types: [
    'string',
  ],
)]
class FileExtensionFormatter extends BaseFieldFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    $settings['extension_detect_tar'] = FALSE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['extension_detect_tar'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include tar in extension'),
      '#description' => $this->t("If the part of the filename just before the extension is '.tar', include this in the extension output."),
      '#default_value' => $this->getSetting('extension_detect_tar'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    $filename = $item->value;
    if (!$this->getSetting('extension_detect_tar')) {
      return pathinfo($filename, PATHINFO_EXTENSION);
    }
    else {
      $file_parts = explode('.', basename($filename));
      if (count($file_parts) > 1) {
        $extension = array_pop($file_parts);
        $last_part_in_name = array_pop($file_parts);
        if ($last_part_in_name === 'tar') {
          $extension = 'tar.' . $extension;
        }
        return $extension;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Just show this file extension formatter on the filename field.
    return parent::isApplicable($field_definition) && $field_definition->getName() === 'filename';
  }

}
