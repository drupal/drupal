<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Field\FieldFormatter\FilemimeFormatter.
 */

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formatter to render the file mime type, with an optional icon.
 *
 * @FieldFormatter(
 *   id = "file_filemime",
 *   label = @Translation("File mime"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class FilemimeFormatter extends BaseFieldFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return parent::isApplicable($field_definition) && $field_definition->getName() === 'filemime';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    $settings['filemime_image'] = FALSE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['filemime_image'] = array(
      '#title' => $this->t('Display an icon'),
      '#description' => $this->t('The icon is representing the file type, instead of the MIME text (such as "image/jpeg")'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('filemime_image'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    $value = $item->value;
    if ($this->getSetting('filemime_image') && $value) {
      $file_icon = [
        '#theme' => 'image__file_icon',
        '#file' => $item->getEntity(),
      ];
      return $file_icon;
    }
    return $value;
  }

}
