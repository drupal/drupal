<?php

namespace Drupal\locale;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Manages which languages are translatable and checks a specific language.
 */
class LocaleLanguages {

  public function __construct(
    protected readonly LanguageManagerInterface $languageManager,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Returns list of translatable languages.
   *
   * @return array
   *   Array of installed languages keyed by language name. English is omitted
   *   unless it is marked as translatable.
   */
  public function getTranslatableLanguages(): array {
    $languages = $this->languageManager->getLanguages();
    if (!$this->isTranslatable('en')) {
      unset($languages['en']);
    }
    return $languages;
  }

  /**
   * Checks whether $langcode is a language supported as a locale target.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   Whether $langcode can be translated to in locale.
   */
  public function isTranslatable(string $langcode): bool {
    return $langcode != 'en' || $this->configFactory->get('locale.settings')->get('translate_english');
  }

}
