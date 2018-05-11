<?php

namespace Drupal\ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginCssInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\LanguageInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "language" plugin.
 *
 * @CKEditorPlugin(
 *   id = "language",
 *   label = @Translation("Language")
 * )
 */
class Language extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginCssInterface {

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    // This plugin is already part of Drupal core's CKEditor build.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return ['ckeditor/drupal.ckeditor.plugins.language'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $language_list = [];
    $config = ['language_list' => 'un'];
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['language'])) {
      $config = $settings['plugins']['language'];
    }

    $predefined_languages = ($config['language_list'] === 'all') ?
      LanguageManager::getStandardLanguageList() :
      LanguageManager::getUnitedNationsLanguageList();

    // Generate the language_list setting as expected by the CKEditor Language
    // plugin, but key the values by the full language name so that we can sort
    // them later on.
    foreach ($predefined_languages as $langcode => $language) {
      $english_name = $language[0];
      $direction = empty($language[2]) ? NULL : $language[2];
      if ($direction === LanguageInterface::DIRECTION_RTL) {
        $language_list[$english_name] = $langcode . ':' . $english_name . ':rtl';
      }
      else {
        $language_list[$english_name] = $langcode . ':' . $english_name;
      }
    }

    // Sort on full language name.
    ksort($language_list);
    $config = ['language_list' => array_values($language_list)];
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'Language' => [
        'label' => $this->t('Language'),
        'image_alternative' => [
          '#type' => 'inline_template',
          '#template' => '<a href="#" class="cke-icon-only" role="button" title="' . $this->t('Language') . '" aria-label="' . $this->t('Language') . '"><span class="cke_button_icon cke_button__language_icon">' . $this->t('Language') . '</span></a>',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    // Defaults.
    $config = ['language_list' => 'un'];
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['language'])) {
      $config = $settings['plugins']['language'];
    }

    $predefined_languages = LanguageManager::getStandardLanguageList();
    $form['language_list'] = [
      '#title' => $this->t('Language list'),
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => [
        'un' => $this->t("United Nations' official languages"),
        'all' => $this->t('All @count languages', ['@count' => count($predefined_languages)]),
      ],
      '#default_value' => $config['language_list'],
      '#description' => $this->t('The list of languages to show in the language dropdown. The basic list will only show the <a href=":url">six official languages of the UN</a>. The extended list will show all @count languages that are available in Drupal.', [
        ':url' => 'https://www.un.org/en/sections/about-un/official-languages',
        '@count' => count($predefined_languages),
      ]),
      '#attached' => ['library' => ['ckeditor/drupal.ckeditor.language.admin']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor) {
    return [
        drupal_get_path('module', 'ckeditor') . '/css/plugins/language/ckeditor.language.css',
    ];
  }

}
