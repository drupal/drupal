<?php

namespace Drupal\locale\Hook;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\locale\LocaleJs;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Hook theme alter implementations for locale.
 */
class LocaleThemeAlterHooks {

  public function __construct(
    /**
     * @var \Closure(): \Drupal\locale\LocaleJs
     */
    #[AutowireServiceClosure(LocaleJs::class)]
    protected readonly \Closure $localeJs,
  ) {}

  /**
   * Implements hook_js_alter().
   */
  #[Hook('js_alter')]
  public function jsAlter(array &$javascript, AttachedAssetsInterface $assets, LanguageInterface $language): void {
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
    $placeholderFile = 'core/modules/locale/locale.translation.js';
    if (isset($javascript[$placeholderFile])) {
      if ($translationFile = ($this->localeJs)()->jsTranslate($files, $language)) {
        $jsTranslationAsset =& $javascript[$placeholderFile];
        $jsTranslationAsset['data'] = $translationFile;
      }
      else {
        // If no translation file exists, then remove the placeholder JS asset.
        unset($javascript[$placeholderFile]);
      }
    }
  }

  /**
   * Implements hook_library_info_alter().
   *
   * Provides language support.
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $module): void {
    // When the locale module is enabled, we update the core/drupal library to
    // have a dependency on the locale/translations library, which provides
    // window.drupalTranslations, containing the translations for all strings in
    // JavaScript assets in the current language.
    // @see locale_js_alter()
    if ($module === 'core' && isset($libraries['drupal'])) {
      $libraries['drupal']['dependencies'][] = 'locale/translations';
    }
  }

}
