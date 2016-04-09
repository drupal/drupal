<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formatter for a text field on a file entity that links the field to the file.
 *
 * @FieldFormatter(
 *   id = "file_link",
 *   label = @Translation("File link"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class DefaultFileFormatter extends BaseFieldFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['link_to_file'] = TRUE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // We don't call the parent in order to bypass the link to file form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    return $item->value;
  }

}
