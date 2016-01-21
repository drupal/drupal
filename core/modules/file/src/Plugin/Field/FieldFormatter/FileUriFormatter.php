<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Field\FieldFormatter\FileUriFormatter.
 */

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formatter to render the file URI to its download path.
 *
 * @FieldFormatter(
 *   id = "file_uri",
 *   label = @Translation("File URI"),
 *   field_types = {
 *     "uri"
 *   }
 * )
 */
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
      // @todo Wrap in file_url_transform_relative(). This is currently
      // impossible. See BaseFieldFileFormatterBase::viewElements(). Fix in
      // https://www.drupal.org/node/2646744.
      $value = file_create_url($value);
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
