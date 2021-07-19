<?php

namespace Drupal\config_translation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure config translation settings for this site.
 *
 * @internal
 */
class ConfigTranslationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_translation_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['config_translation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['config_translation_translate_source'] = [
      '#title' => t('Enable configuration translation for source language'),
      '#type' => 'checkbox',
      '#default_value' => \Drupal::configFactory()->getEditable('config_translation.settings')->get('translate_source'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $config = $this->config('config_translation.settings');
    $config->set('translate_source', $values['config_translation_translate_source'])->save();

    parent::submitForm($form, $form_state);
  }

}
