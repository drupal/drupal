<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for file formatters that have to deal with file descriptions.
 */
abstract class DescriptionAwareFileFormatterBase extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    $settings['use_description_as_link_text'] = TRUE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['use_description_as_link_text'] = [
      '#title' => $this->t('Use description as link text'),
      '#description' => $this->t('Replace the file name by its description when available'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('use_description_as_link_text'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('use_description_as_link_text')) {
      $summary[] = $this->t('Use description as link text');
    }

    return $summary;
  }

}
