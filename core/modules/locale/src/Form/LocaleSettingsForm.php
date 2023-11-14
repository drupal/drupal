<?php

namespace Drupal\locale\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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
      '#config_target' => 'locale.settings:translation.update_interval_days',
      '#options' => [
        '0' => $this->t('Never (manually)'),
        '7' => $this->t('Weekly'),
        '30' => $this->t('Monthly'),
      ],
      '#description' => $this->t('Select how frequently you want to check for new interface translations for your currently installed modules and themes. <a href=":url">Check updates now</a>.', [':url' => Url::fromRoute('locale.check_translation')->toString()]),
    ];

    if ($directory = $config->get('translation.path')) {
      $description = $this->t('Translation files are stored locally in the  %path directory. You can change this directory on the <a href=":url">File system</a> configuration page.', ['%path' => $directory, ':url' => Url::fromRoute('system.file_system_settings')->toString()]);
    }
    else {
      $description = $this->t('Translation files will not be stored locally. Change the Interface translation directory on the <a href=":url">File system configuration</a> page.', [':url' => Url::fromRoute('system.file_system_settings')->toString()]);
    }
    $form['#translation_directory'] = $directory;
    $form['use_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Translation source'),
      '#config_target' => 'locale.settings:translation.use_source',
      '#options' => [
        LOCALE_TRANSLATION_USE_SOURCE_REMOTE_AND_LOCAL => $this->t('Drupal translation server and local files'),
        LOCALE_TRANSLATION_USE_SOURCE_LOCAL => $this->t('Local files only'),
      ],
      '#description' => $this->t('The source of translation files for automatic interface translation.') . ' ' . $description,
    ];

    $form['overwrite'] = [
      '#type' => 'radios',
      '#title' => $this->t('Import behavior'),
      '#options' => [
        LOCALE_TRANSLATION_OVERWRITE_NONE => $this->t("Don't overwrite existing translations."),
        LOCALE_TRANSLATION_OVERWRITE_NON_CUSTOMIZED => $this->t('Only overwrite imported translations, customized translations are kept.'),
        LOCALE_TRANSLATION_OVERWRITE_ALL => $this->t('Overwrite existing translations.'),
      ],
      '#description' => $this->t('How to treat existing translations when automatically updating the interface translations.'),
      '#config_target' => new ConfigTarget(
        'locale.settings',
        [
          'translation.overwrite_customized',
          'translation.overwrite_not_customized',
        ],
        fromConfig: fn (bool $overwrite_customized, bool $overwrite_not_customized): string => match(TRUE) {
          $overwrite_not_customized == FALSE => LOCALE_TRANSLATION_OVERWRITE_NONE,
          $overwrite_customized == TRUE => LOCALE_TRANSLATION_OVERWRITE_ALL,
          default => LOCALE_TRANSLATION_OVERWRITE_NON_CUSTOMIZED,
        },
        toConfig: fn (string $radio_option): array => match($radio_option) {
          LOCALE_TRANSLATION_OVERWRITE_ALL => [
            'translation.overwrite_customized' => TRUE,
            'translation.overwrite_not_customized' => TRUE,
          ],
          LOCALE_TRANSLATION_OVERWRITE_NON_CUSTOMIZED => [
            'translation.overwrite_customized' => FALSE,
            'translation.overwrite_not_customized' => TRUE,
          ],
          LOCALE_TRANSLATION_OVERWRITE_NONE => [
            'translation.overwrite_customized' => FALSE,
            'translation.overwrite_not_customized' => FALSE,
          ],
        }
      ),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (empty($form['#translation_directory']) && $form_state->getValue('use_source') == LOCALE_TRANSLATION_USE_SOURCE_LOCAL) {
      $form_state->setErrorByName('use_source', $this->t('You have selected local translation source, but no <a href=":url">Interface translation directory</a> was configured.', [':url' => Url::fromRoute('system.file_system_settings')->toString()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Invalidate the cached translation status when the configuration setting
    // of 'use_source' changes.
    if ($form['use_source']['#default_value'] != $form_state->getValue('use_source')) {
      locale_translation_clear_status();
    }

    parent::submitForm($form, $form_state);
  }

}
