<?php

namespace Drupal\locale\Hook;

use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\language\ConfigurableLanguageInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for locale.
 */
class LocaleHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.locale':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Interface Translation module allows you to translate interface text (<em>strings</em>) into different languages, and to switch between them for the display of interface text. It uses the functionality provided by the <a href=":language">Language module</a>. For more information, see the <a href=":doc-url">online documentation for the Interface Translation module</a>.', [
          ':doc-url' => 'https://www.drupal.org/documentation/modules/locale/',
          ':language' => Url::fromRoute('help.page', [
            'name' => 'language',
          ])->toString(),
        ]) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Importing translation files') . '</dt>';
        $output .= '<dd>' . t('Translation files with translated interface text are imported automatically when languages are added on the <a href=":languages">Languages</a> page, or when modules or themes are installed. On the <a href=":locale-settings">Interface translation settings</a> page, the <em>Translation source</em> can be restricted to local files only, or to include the <a href=":server">Drupal translation server</a>. Although modules and themes may not be fully translated in all languages, new translations become available frequently. You can specify whether and how often to check for translation file updates and whether to overwrite existing translations on the <a href=":locale-settings">Interface translation settings</a> page. You can also manually import a translation file on the <a href=":import">Interface translation import</a> page.', [
          ':import' => Url::fromRoute('locale.translate_import')->toString(),
          ':locale-settings' => Url::fromRoute('locale.settings')->toString(),
          ':languages' => Url::fromRoute('entity.configurable_language.collection')->toString(),
          ':server' => 'https://localize.drupal.org',
        ]) . '</dd>';
        $output .= '<dt>' . t('Checking the translation status') . '</dt>';
        $output .= '<dd>' . t('You can check how much of the interface on your site is translated into which language on the <a href=":languages">Languages</a> page. On the <a href=":translation-updates">Available translation updates</a> page, you can check whether interface translation updates are available on the <a href=":server">Drupal translation server</a>.', [
          ':languages' => Url::fromRoute('entity.configurable_language.collection')->toString(),
          ':translation-updates' => Url::fromRoute('locale.translate_status')->toString(),
          ':server' => 'https://localize.drupal.org',
        ]) . '<dd>';
        $output .= '<dt>' . t('Translating individual strings') . '</dt>';
        $output .= '<dd>' . t('You can translate individual strings directly on the <a href=":translate">User interface translation</a> page, or download the currently-used translation file for a specific language on the <a href=":export">Interface translation export</a> page. Once you have edited the translation file, you can then import it again on the <a href=":import">Interface translation import</a> page.', [
          ':translate' => Url::fromRoute('locale.translate_page')->toString(),
          ':export' => Url::fromRoute('locale.translate_export')->toString(),
          ':import' => Url::fromRoute('locale.translate_import')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . t('Overriding default English strings') . '</dt>';
        $output .= '<dd>' . t('If translation is enabled for English, you can <em>override</em> the default English interface text strings in your site with other English text strings on the <a href=":translate">User interface translation</a> page. Translation is off by default for English, but you can turn it on by visiting the <em>Edit language</em> page for <em>English</em> from the <a href=":languages">Languages</a> page.', [
          ':translate' => Url::fromRoute('locale.translate_page')->toString(),
          ':languages' => Url::fromRoute('entity.configurable_language.collection')->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'entity.configurable_language.collection':
        return '<p>' . t('Interface translations are automatically imported when a language is added, or when new modules or themes are installed. The report <a href=":update">Available translation updates</a> shows the status. Interface text can be customized in the <a href=":translate">user interface translation</a> page.', [
          ':update' => Url::fromRoute('locale.translate_status')->toString(),
          ':translate' => Url::fromRoute('locale.translate_page')->toString(),
        ]) . '</p>';

      case 'locale.translate_page':
        $output = '<p>' . t('This page allows a translator to search for specific translated and untranslated strings, and is used when creating or editing translations. (Note: Because translation tasks involve many strings, it may be more convenient to <a title="User interface translation export" href=":export">export</a> strings for offline editing in a desktop Gettext translation editor.) Searches may be limited to strings in a specific language.', [':export' => Url::fromRoute('locale.translate_export')->toString()]) . '</p>';
        return $output;

      case 'locale.translate_import':
        $output = '<p>' . t('Translation files are automatically downloaded and imported when <a title="Languages" href=":language">languages</a> are added, or when modules or themes are installed.', [
          ':language' => Url::fromRoute('entity.configurable_language.collection')->toString(),
        ]) . '</p>';
        $output .= '<p>' . t('This page allows translators to manually import translated strings contained in a Gettext Portable Object (.po) file. Manual import may be used for customized translations or for the translation of custom modules and themes. To customize translations you can download a translation file from the <a href=":url">Drupal translation server</a> or <a title="User interface translation export" href=":export">export</a> translations from the site, customize the translations using a Gettext translation editor, and import the result using this page.', [
          ':url' => 'https://localize.drupal.org',
          ':export' => Url::fromRoute('locale.translate_export')->toString(),
        ]) . '</p>';
        $output .= '<p>' . t('Note that importing large .po files may take several minutes.') . '</p>';
        return $output;

      case 'locale.translate_export':
        return '<p>' . t('This page exports the translated strings used by your site. An export file may be in Gettext Portable Object (<em>.po</em>) form, which includes both the original string and the translation (used to share translations with others), or in Gettext Portable Object Template (<em>.pot</em>) form, which includes the original strings only (used to create new translations with a Gettext translation editor).') . '</p>';
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'locale_translation_last_check' => [
        'variables' => [
          'last' => NULL,
        ],
        'file' => 'locale.pages.inc',
      ],
      'locale_translation_update_info' => [
        'variables' => [
          'updates' => [],
          'not_found' => [],
        ],
        'file' => 'locale.pages.inc',
      ],
    ];
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for 'configurable_language'.
   */
  #[Hook('configurable_language_insert')]
  public function configurableLanguageInsert(ConfigurableLanguageInterface $language) {
    // @todo move these two cache clears out. See
    //   https://www.drupal.org/node/1293252.
    // Changing the language settings impacts the interface: clear render cache.
    \Drupal::cache('render')->deleteAll();
    // Force JavaScript translation file re-creation for the new language.
    _locale_invalidate_js($language->id());
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for 'configurable_language'.
   */
  #[Hook('configurable_language_update')]
  public function configurableLanguageUpdate(ConfigurableLanguageInterface $language) {
    // @todo move these two cache clears out. See
    //   https://www.drupal.org/node/1293252.
    // Changing the language settings impacts the interface: clear render cache.
    \Drupal::cache('render')->deleteAll();
    // Force JavaScript translation file re-creation for the modified language.
    _locale_invalidate_js($language->id());
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for 'configurable_language'.
   */
  #[Hook('configurable_language_delete')]
  public function configurableLanguageDelete(ConfigurableLanguageInterface $language) {
    // Remove translations.
    \Drupal::service('locale.storage')->deleteTranslations(['language' => $language->id()]);
    // Remove interface translation files.
    \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.bulk');
    locale_translate_delete_translation_files([], [$language->id()]);
    // Remove translated configuration objects.
    \Drupal::service('locale.config_manager')->deleteLanguageTranslations($language->id());
    // Changing the language settings impacts the interface:
    _locale_invalidate_js($language->id());
    \Drupal::cache('render')->deleteAll();
    // Clear locale translation caches.
    locale_translation_status_delete_languages([$language->id()]);
    \Drupal::cache()->delete('locale:' . $language->id());
  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public function modulesInstalled($modules) {
    $components['module'] = $modules;
    locale_system_update($components);
  }

  /**
   * Implements hook_module_preuninstall().
   */
  #[Hook('module_preuninstall')]
  public function modulePreuninstall($module) {
    $components['module'] = [$module];
    locale_system_remove($components);
  }

  /**
   * Implements hook_themes_installed().
   */
  #[Hook('themes_installed')]
  public function themesInstalled($themes) {
    $components['theme'] = $themes;
    locale_system_update($components);
  }

  /**
   * Implements hook_themes_uninstalled().
   */
  #[Hook('themes_uninstalled')]
  public function themesUninstalled($themes) {
    $components['theme'] = $themes;
    locale_system_remove($components);
  }

  /**
   * Implements hook_cron().
   *
   * @see \Drupal\locale\Plugin\QueueWorker\LocaleTranslation
   */
  #[Hook('cron')]
  public function cron(): void {
    // Update translations only when an update frequency was set by the admin
    // and a translatable language was set.
    // Update tasks are added to the queue here but processed by Drupal's cron.
    if (\Drupal::config('locale.settings')->get('translation.update_interval_days') && locale_translatable_language_list()) {
      \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.translation');
      locale_cron_fill_queue();
    }
  }

  /**
   * Implements hook_cache_flush().
   */
  #[Hook('cache_flush')]
  public function cacheFlush() {
    \Drupal::state()->delete('system.javascript_parsed');
  }

  /**
   * Implements hook_js_alter().
   */
  #[Hook('js_alter')]
  public function jsAlter(&$javascript, AttachedAssetsInterface $assets, LanguageInterface $language): void {
    $files = [];
    foreach ($javascript as $item) {
      if (isset($item['type']) && $item['type'] == 'file') {
        // Ignore the JS translation placeholder file.
        if ($item['data'] === 'core/modules/locale/locale.translation.js') {
          continue;
        }
        $files[] = $item['data'];
      }
    }
    // Replace the placeholder file with the actual JS translation file.
    $placeholder_file = 'core/modules/locale/locale.translation.js';
    if (isset($javascript[$placeholder_file])) {
      if ($translation_file = locale_js_translate($files, $language)) {
        $js_translation_asset =& $javascript[$placeholder_file];
        $js_translation_asset['data'] = $translation_file;
        // @todo Remove this when https://www.drupal.org/node/1945262 lands.
        // Decrease the weight so that the translation file is loaded first.
        $js_translation_asset['weight'] = $javascript['core/misc/drupal.js']['weight'] - 0.001;
      }
      else {
        // If no translation file exists, then remove the placeholder JS asset.
        unset($javascript[$placeholder_file]);
      }
    }
  }

  /**
   * Implements hook_library_info_alter().
   *
   * Provides language support.
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, $module): void {
    // When the locale module is enabled, we update the core/drupal library to
    // have a dependency on the locale/translations library, which provides
    // window.drupalTranslations, containing the translations for all strings in
    // JavaScript assets in the current language.
    // @see locale_js_alter()
    if ($module === 'core' && isset($libraries['drupal'])) {
      $libraries['drupal']['dependencies'][] = 'locale/translations';
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for language_admin_overview_form().
   */
  #[Hook('form_language_admin_overview_form_alter')]
  public function formLanguageAdminOverviewFormAlter(&$form, FormStateInterface $form_state) : void {
    $languages = $form['languages']['#languages'];
    $total_strings = \Drupal::service('locale.storage')->countStrings();
    $stats = array_fill_keys(array_keys($languages), []);
    // If we have source strings, count translations and calculate progress.
    if (!empty($total_strings)) {
      $translations = \Drupal::service('locale.storage')->countTranslations();
      foreach ($translations as $langcode => $translated) {
        $stats[$langcode]['translated'] = $translated;
        if ($translated > 0) {
          $stats[$langcode]['ratio'] = round($translated / $total_strings * 100, 2);
        }
      }
    }
    array_splice($form['languages']['#header'], -1, 0, ['translation-interface' => t('Interface translation')]);
    foreach ($languages as $langcode => $language) {
      $stats[$langcode] += ['translated' => 0, 'ratio' => 0];
      if (!$language->isLocked() && locale_is_translatable($langcode)) {
        $form['languages'][$langcode]['locale_statistics'] = Link::fromTextAndUrl(t('@translated/@total (@ratio%)', [
          '@translated' => $stats[$langcode]['translated'],
          '@total' => $total_strings,
          '@ratio' => $stats[$langcode]['ratio'],
        ]), Url::fromRoute('locale.translate_page', [], ['query' => ['langcode' => $langcode]]))->toRenderable();
      }
      else {
        $form['languages'][$langcode]['locale_statistics'] = ['#markup' => t('not applicable')];
      }
      // #type = link doesn't work with #weight on table.
      // reset and set it back after locale_statistics to get it at the right end.
      $operations = $form['languages'][$langcode]['operations'];
      unset($form['languages'][$langcode]['operations']);
      $form['languages'][$langcode]['operations'] = $operations;
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for language_admin_add_form().
   */
  #[Hook('form_language_admin_add_form_alter')]
  public function formLanguageAdminAddFormAlter(&$form, FormStateInterface $form_state) : void {
    $form['predefined_submit']['#submit'][] = 'locale_form_language_admin_add_form_alter_submit';
    $form['custom_language']['submit']['#submit'][] = 'locale_form_language_admin_add_form_alter_submit';
  }

  /**
   * Implements hook_form_FORM_ID_alter() for language_admin_edit_form().
   */
  #[Hook('form_language_admin_edit_form_alter')]
  public function formLanguageAdminEditFormAlter(&$form, FormStateInterface $form_state) : void {
    if ($form['langcode']['#type'] == 'value' && $form['langcode']['#value'] == 'en') {
      $form['locale_translate_english'] = [
        '#title' => t('Enable interface translation to English'),
        '#type' => 'checkbox',
        '#default_value' => \Drupal::configFactory()->getEditable('locale.settings')->get('translate_english'),
      ];
      $form['actions']['submit']['#submit'][] = 'locale_form_language_admin_edit_form_alter_submit';
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for system_file_system_settings().
   *
   * Add interface translation directory setting to directories configuration.
   */
  #[Hook('form_system_file_system_settings_alter')]
  public function formSystemFileSystemSettingsAlter(&$form, FormStateInterface $form_state) : void {
    $form['translation_path'] = [
      '#type' => 'textfield',
      '#title' => t('Interface translations directory'),
      '#default_value' => \Drupal::configFactory()->getEditable('locale.settings')->get('translation.path'),
      '#maxlength' => 255,
      '#description' => t('A local file system path where interface translation files will be stored.'),
      '#required' => TRUE,
      '#after_build' => [
        'system_check_directory',
      ],
      '#weight' => 10,
    ];
    if ($form['file_default_scheme']) {
      $form['file_default_scheme']['#weight'] = 20;
    }
    $form['#submit'][] = 'locale_system_file_system_settings_submit';
  }

}
