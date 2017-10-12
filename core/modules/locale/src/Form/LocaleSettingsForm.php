<?php

namespace Drupal\locale\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure locale settings for this site.
 *
 * @internal
 */
class LocaleSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'locale_translate_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['locale.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('locale.settings');

    $form['update_interval_days'] = [
      '#type' => 'radios',
      '#title' => $this->t('Check for updates'),
      '#default_value' => $config->get('translation.update_interval_days'),
      '#options' => [
        '0' => $this->t('Never (manually)'),
        '7' => $this->t('Weekly'),
        '30' => $this->t('Monthly'),
      ],
      '#description' => $this->t('Select how frequently you want to check for new interface translations for your currently installed modules and themes. <a href=":url">Check updates now</a>.', [':url' => $this->url('locale.check_translation')]),
    ];

    if ($directory = $config->get('translation.path')) {
      $description = $this->t('Translation files are stored locally in the  %path directory. You can change this directory on the <a href=":url">File system</a> configuration page.', ['%path' => $directory, ':url' => $this->url('system.file_system_settings')]);
    }
    else {
      $description = $this->t('Translation files will not be stored locally. Change the Interface translation directory on the <a href=":url">File system configuration</a> page.', [':url' => $this->url('system.file_system_settings')]);
    }
    $form['#translation_directory'] = $directory;
    $form['use_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Translation source'),
      '#default_value' => $config->get('translation.use_source'),
      '#options' => [
        LOCALE_TRANSLATION_USE_SOURCE_REMOTE_AND_LOCAL => $this->t('Drupal translation server and local files'),
        LOCALE_TRANSLATION_USE_SOURCE_LOCAL => $this->t('Local files only'),
      ],
      '#description' => $this->t('The source of translation files for automatic interface translation.') . ' ' . $description,
    ];

    if ($config->get('translation.overwrite_not_customized') == FALSE) {
      $default = LOCALE_TRANSLATION_OVERWRITE_NONE;
    }
    elseif ($config->get('translation.overwrite_customized') == TRUE) {
      $default = LOCALE_TRANSLATION_OVERWRITE_ALL;
    }
    else {
      $default = LOCALE_TRANSLATION_OVERWRITE_NON_CUSTOMIZED;
    }
    $form['overwrite'] = [
      '#type' => 'radios',
      '#title' => $this->t('Import behavior'),
      '#default_value' => $default,
      '#options' => [
        LOCALE_TRANSLATION_OVERWRITE_NONE => $this->t("Don't overwrite existing translations."),
        LOCALE_TRANSLATION_OVERWRITE_NON_CUSTOMIZED => $this->t('Only overwrite imported translations, customized translations are kept.'),
        LOCALE_TRANSLATION_OVERWRITE_ALL => $this->t('Overwrite existing translations.'),
      ],
      '#description' => $this->t('How to treat existing translations when automatically updating the interface translations.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (empty($form['#translation_directory']) && $form_state->getValue('use_source') == LOCALE_TRANSLATION_USE_SOURCE_LOCAL) {
      $form_state->setErrorByName('use_source', $this->t('You have selected local translation source, but no <a href=":url">Interface translation directory</a> was configured.', [':url' => $this->url('system.file_system_settings')]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $config = $this->config('locale.settings');
    $config->set('translation.update_interval_days', $values['update_interval_days'])->save();
    $config->set('translation.use_source', $values['use_source'])->save();

    switch ($values['overwrite']) {
      case LOCALE_TRANSLATION_OVERWRITE_ALL:
        $config
          ->set('translation.overwrite_customized', TRUE)
          ->set('translation.overwrite_not_customized', TRUE)
          ->save();
        break;

      case LOCALE_TRANSLATION_OVERWRITE_NON_CUSTOMIZED:
        $config
          ->set('translation.overwrite_customized', FALSE)
          ->set('translation.overwrite_not_customized', TRUE)
          ->save();
        break;

      case LOCALE_TRANSLATION_OVERWRITE_NONE:
        $config
          ->set('translation.overwrite_customized', FALSE)
          ->set('translation.overwrite_not_customized', FALSE)
          ->save();
        break;
    }

    // Invalidate the cached translation status when the configuration setting
    // of 'use_source' changes.
    if ($form['use_source']['#default_value'] != $form_state->getValue('use_source')) {
      locale_translation_clear_status();
    }

    parent::submitForm($form, $form_state);
  }

}
