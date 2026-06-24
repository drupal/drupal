<?php

namespace Drupal\locale\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\locale\LocaleConfigBatch;
use Drupal\locale\LocaleDefaultOptions;
use Drupal\locale\LocaleFetch;
use Drupal\locale\StreamWrapper\TranslationsStream;
use Drupal\locale\StringStorageInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Form hook implementations for locale.
 */
class LocaleFormHooks {

  use StringTranslationTrait;

  public function __construct(
    protected StringStorageInterface $stringStorage,
    protected readonly ConfigFactoryInterface $configFactory,
    /**
     * @var \Closure(): \Drupal\locale\LocaleFetch
     */
    #[AutowireServiceClosure(LocaleFetch::class)]
    protected \Closure $localeFetchClosure,
    /**
     * @var \Closure(): \Drupal\locale\LocaleConfigBatch
     */
    #[AutowireServiceClosure(LocaleConfigBatch::class)]
    protected \Closure $localeConfigBatchClosure,
  ) {
  }

  /**
   * Implements hook_form_FORM_ID_alter() for language_admin_overview_form().
   */
  #[Hook('form_language_admin_overview_form_alter')]
  public function formLanguageAdminOverviewFormAlter(array &$form, FormStateInterface $form_state) : void {
    $languages = $form['languages']['#languages'];
    $total_strings = $this->stringStorage->countStrings();
    $stats = array_fill_keys(array_keys($languages), []);
    // If we have source strings, count translations and calculate progress.
    if (!empty($total_strings)) {
      $translations = $this->stringStorage->countTranslations();
      foreach ($translations as $langcode => $translated) {
        $stats[$langcode]['translated'] = $translated;
        if ($translated > 0) {
          $stats[$langcode]['ratio'] = round($translated / $total_strings * 100, 2);
        }
      }
    }
    array_splice($form['languages']['#header'], -1, 0, ['translation-interface' => $this->t('Interface translation')]);
    foreach ($languages as $langcode => $language) {
      $stats[$langcode] += ['translated' => 0, 'ratio' => 0];
      if (!$language->isLocked() && locale_is_translatable($langcode)) {
        $form['languages'][$langcode]['locale_statistics'] = Link::fromTextAndUrl($this->t('@translated/@total (@ratio%)', [
          '@translated' => $stats[$langcode]['translated'],
          '@total' => $total_strings,
          '@ratio' => $stats[$langcode]['ratio'],
        ]), Url::fromRoute('locale.translate_page', [], ['query' => ['langcode' => $langcode]]))->toRenderable();
      }
      else {
        $form['languages'][$langcode]['locale_statistics'] = ['#markup' => $this->t('not applicable')];
      }
      // #type = link doesn't work with #weight on table.
      // reset and set it back after locale_statistics to get it at the right
      // end.
      $operations = $form['languages'][$langcode]['operations'];
      unset($form['languages'][$langcode]['operations']);
      $form['languages'][$langcode]['operations'] = $operations;
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for language_admin_add_form().
   */
  #[Hook('form_language_admin_add_form_alter')]
  public function formLanguageAdminAddFormAlter(array &$form, FormStateInterface $form_state) : void {
    $form['predefined_submit']['#submit'][] = self::class . ':formLanguageAdminAddFormAlterSubmit';
    $form['custom_language']['submit']['#submit'][] = self::class . ':formLanguageAdminAddFormAlterSubmit';
  }

  /**
   * Implements hook_form_FORM_ID_alter() for language_admin_edit_form().
   */
  #[Hook('form_language_admin_edit_form_alter')]
  public function formLanguageAdminEditFormAlter(array &$form, FormStateInterface $form_state) : void {
    /** @var \Drupal\language\ConfigurableLanguageInterface $language */
    $language = $form_state->getFormObject()->getEntity();
    if ($language->id() == 'en') {
      $form['locale_translate_english'] = [
        '#title' => $this->t('Enable interface translation to English'),
        '#type' => 'checkbox',
        '#default_value' => $this->configFactory->getEditable('locale.settings')->get('translate_english'),
      ];
      $form['actions']['submit']['#submit'][] = self::class . ':formLanguageAdminEditFormAlterSubmit';
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for system_file_system_settings().
   *
   * Add interface translation directory setting to directories configuration.
   */
  #[Hook('form_system_file_system_settings_alter')]
  public function formSystemFileSystemSettingsAlter(array &$form, FormStateInterface $form_state) : void {
    $form['translation_path'] = [
      '#type' => 'item',
      '#title' => $this->t('Interface translations directory'),
      '#markup' => TranslationsStream::basePath(),
      '#description' => $this->t('A local file system path where interface translation files will be stored. This must be changed in settings.php file as the "locale_translation_path" setting.'),
    ];
    if ($form['file_default_scheme']) {
      $form['file_default_scheme']['#weight'] = 20;
    }
  }

  /**
   * Form submission handler for language_admin_add_form.
   *
   * Set a batch for a newly-added language.
   */
  public function formLanguageAdminAddFormAlterSubmit(array $form, FormStateInterface $form_state): void {
    $options = LocaleDefaultOptions::updateOptions();

    if ($form_state->isValueEmpty('predefined_langcode') || $form_state->getValue('predefined_langcode') == 'custom') {
      $langcode = $form_state->getValue('langcode');
    }
    else {
      $langcode = $form_state->getValue('predefined_langcode');
    }

    if ($this->importEnabled()) {
      // Download and import translations for the newly added language.
      $batch = ($this->localeFetchClosure)()->buildUpdateBatch([], [$langcode], $options);
      batch_set($batch);
    }

    // Create or update all configuration translations for this language. If we
    // are adding English then we need to run this even if import is not
    // enabled, because then we extract English sources from shipped
    // configuration.
    if ($this->importEnabled() || $langcode == 'en') {
      if ($batch = ($this->localeConfigBatchClosure)()->buildBatch($options, [$langcode])) {
        batch_set($batch);
      }
    }
  }

  /**
   * Form submission handler for language_admin_edit_form().
   */
  public function formLanguageAdminEditFormAlterSubmit(array $form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable('locale.settings')->set('translate_english', intval($form_state->getValue('locale_translate_english')))->save();
  }

  /**
   * Check if import is enabled.
   */
  protected function importEnabled(): bool {
    return $this->configFactory->get('locale.settings')->get('translation.import_enabled');
  }

}
