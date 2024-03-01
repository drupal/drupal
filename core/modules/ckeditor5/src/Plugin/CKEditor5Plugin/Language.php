<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CKEditor 5 Language plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class Language extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, ContainerFactoryPluginInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * Language constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The route provider.
   */
  public function __construct(array $configuration, string $plugin_id, CKEditor5PluginDefinition $plugin_definition, protected LanguageManagerInterface $languageManager, protected RouteProviderInterface $routeProvider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('router.route_provider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $languages = NULL;
    switch ($this->configuration['language_list']) {
      case 'site_configured':
        $configured_languages = $this->languageManager->getLanguages();
        $languages = [];
        foreach ($configured_languages as $language) {
          $languages[$language->getId()] = [
            $language->getName(),
            '',
            $language->getDirection(),
          ];
        }
        break;

      case 'all':
        $languages = LanguageManager::getStandardLanguageList();
        break;

      case 'un':
        $languages = LanguageManager::getUnitedNationsLanguageList();
    }

    // Generate the language_list setting as expected by the CKEditor Language
    // plugin, but key the values by the full language name so that we can sort
    // them later on.
    $language_list = [];
    foreach ($languages as $langcode => $language) {
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
   * @see editor_image_upload_settings_form()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configured = count($this->languageManager->getLanguages());
    $predefined = count(LanguageManager::getStandardLanguageList());
    $united_nations = count(LanguageManager::getUnitedNationsLanguageList());

    $language_list_description_args = [
      ':united-nations-official' => 'https://www.un.org/en/sections/about-un/official-languages',
      '@count_predefined' => $predefined,
      '@count_united_nations' => $united_nations,
      '@count_configured' => $configured,
    ];
    // If Language is enabled, link to the configuration route.
    if ($this->routeProvider->getRoutesByNames(['entity.configurable_language.collection'])) {
      $language_list_description = $this->t('The list of languages in the CKEditor "Language" dropdown can present the <a href=":united-nations-official">@count_united_nations official languages of the UN</a>, all @count_predefined languages predefined in Drupal, or the <a href=":admin-configure-languages">@count_configured languages configured for this site</a>.', $language_list_description_args + [':admin-configure-languages' => Url::fromRoute('entity.configurable_language.collection')->toString()]);
    }
    else {
      $language_list_description = $this->t('The list of languages in the CKEditor "Language" dropdown can present the <a href=":united-nations-official">@count_united_nations official languages of the UN</a>, all @count_predefined languages predefined in Drupal, or the languages configured for this site.', $language_list_description_args);
    }

    $form['language_list'] = [
      '#title' => $this->t('Language list'),
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => [
        'un' => $this->t("United Nations' official languages (@count)", ['@count' => $united_nations]),
        'all' => $this->t('Drupal predefined languages (@count)', ['@count' => $predefined]),
        'site_configured' => $this->t("Site-configured languages (@count)", ['@count' => $configured]),
      ],
      '#default_value' => $this->configuration['language_list'],
      '#description' => $language_list_description,
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
