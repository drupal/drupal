<?php

namespace Drupal\locale;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\State\StateInterface;

/**
 * Provides the locale javascript related methods.
 *
 * @internal
 */
class LocaleJs {

  public function __construct(
    protected readonly StateInterface $state,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Returns a list of translation files given a list of JavaScript files.
   *
   * This function checks all JavaScript files passed and invokes parsing if
   * they have not yet been parsed for Drupal.t() and Drupal.formatPlural()
   * calls. Also refreshes the JavaScript translation files if necessary, and
   * returns the filepath to the translation file (if any).
   *
   * @param array $files
   *   An array of local file paths.
   * @param \Drupal\Core\Language\LanguageInterface $languageInterface
   *   The interface language the files should be translated into.
   *
   * @return string|null
   *   The filepath to the translation file or NULL if no translation is
   *   applicable.
   */
  public function jsTranslate(array $files, LanguageInterface $languageInterface): string|null {
    $dir = 'assets://' . $this->configFactory->get('locale.settings')->get('javascript.directory');
    $parsed = $this->state->get('system.javascript_parsed', []);
    $newFiles = FALSE;

    foreach ($files as $filepath) {
      if (!in_array($filepath, $parsed)) {
        // Don't parse our own translations files.
        if (!str_starts_with($filepath, $dir)) {
          _locale_parse_js_file($filepath);
          $parsed[] = $filepath;
          $newFiles = TRUE;
        }
      }
    }

    // If there are any new source files we parsed, invalidate existing
    // JavaScript translation files for all languages, adding the refresh
    // flags into the existing array.
    if ($newFiles) {
      $parsed += _locale_invalidate_js();
    }

    // If necessary, rebuild the translation file for the current language.
    if (!empty($parsed['refresh:' . $languageInterface->getId()])) {
      // Don't clear the refresh flag on failure, so that another try will
      // be performed later.
      if (_locale_rebuild_js()) {
        unset($parsed['refresh:' . $languageInterface->getId()]);
      }
      // Store any changes after refresh was attempted.
      $this->state->set('system.javascript_parsed', $parsed);
    }
    // If no refresh was attempted, but we have new source files, we need
    // to store them too. This occurs if current page is in English.
    elseif ($newFiles) {
      $this->state->set('system.javascript_parsed', $parsed);
    }

    // Add the translation JavaScript file to the page.
    $localeJavascripts = $this->state->get('locale.translation.javascript', []);
    $translationFile = NULL;
    if (!empty($files) && !empty($localeJavascripts[$languageInterface->getId()])) {
      // Add the translation JavaScript file to the page.
      $translationFile = $dir . '/' . $languageInterface->getId() . '_' . $localeJavascripts[$languageInterface->getId()] . '.js';
    }
    return $translationFile;
  }

}
