<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\LanguageInterface;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 Language plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class Language extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $predefined_languages = $this->configuration['language_list'] === 'all' ?
      LanguageManager::getStandardLanguageList() :
      LanguageManager::getUnitedNationsLanguageList();

    // Generate the language_list setting as expected by the CKEditor Language
    // plugin, but key the values by the full language name so that we can sort
    // them later on.
    $language_list = [];
    foreach ($predefined_languages as $langcode => $language) {
      $english_name = $language[0];
      $direction = empty($language[2]) ? NULL : $language[2];
      $language_list[$english_name] = [
        'title' => $english_name,
        'languageCode' => $langcode,
      ];
      if ($direction === LanguageInterface::DIRECTION_RTL) {
        $language_list[$english_name]['textDirection'] = 'rtl';
      }
    }

    // Sort on full language name.
    ksort($language_list);
    $dynamic_plugin_config = $static_plugin_config;
    $dynamic_plugin_config['language']['textPartLanguage'] = array_values($language_list);
    return $dynamic_plugin_config;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\editor\Form\EditorImageDialog
   * @see editor_image_upload_settings_form()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $predefined_languages = LanguageManager::getStandardLanguageList();
    $form['language_list'] = [
      '#title' => $this->t('Language list'),
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => [
        'un' => $this->t("United Nations' official languages"),
        'all' => $this->t('All @count languages', ['@count' => count($predefined_languages)]),
      ],
      '#default_value' => $this->configuration['language_list'],
      '#description' => $this->t('The list of languages to show in the language dropdown. The basic list will only show the <a href=":url">six official languages of the UN</a>. The extended list will show all @count languages that are available in Drupal.', [
        ':url' => 'https://www.un.org/en/sections/about-un/official-languages',
        '@count' => count($predefined_languages),
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['language_list'] = $form_state->getValue('language_list');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['language_list' => 'un'];
  }

}
