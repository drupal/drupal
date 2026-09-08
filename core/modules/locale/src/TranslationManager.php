<?php

declare(strict_types=1);

namespace Drupal\locale;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\StringTranslation\TranslationManager as CoreTranslationManager;

/**
 * Plural index implementation.
 */
class TranslationManager extends CoreTranslationManager {
  use DependencySerializationTrait;

  public function __construct(LanguageDefault $default_language, protected PluralFormulaInterface $pluralFormula) {
    parent::__construct($default_language);
  }

  /**
   * Returns plural form index for a specific number.
   *
   * The index is computed from the formula of this language.
   *
   * @param int $count
   *   Number to return plural for.
   * @param string|null $langcode
   *   (optional) Language code to translate to a language other than what is
   *   used to display the page.
   *
   * @return int
   *   The numeric index of the plural variant to use for this $langcode and
   *   $count combination or -1 if the language was not found or does not have a
   *   plural formula.
   */
  protected function getPluralIndex(int $count, ?string $langcode = NULL): int {
    $langcode = $langcode ?: $this->defaultLangcode;

    // Retrieve the plural formulas for all languages.
    $plural_formulas = $this->pluralFormula->getFormula($langcode);

    // If there is a plural formula for the language, evaluate it for the
    // given $count.
    if (!empty($plural_formulas)) {
      // Plural formulas are stored as an array for 0-199. 100 is the highest
      // modulo used but storing 0-99 is not enough because below 100 we often
      // find exceptions (1, 2, etc).
      $index = $count > 199 ? 100 + ($count % 100) : $count;
      return $plural_formulas[$index] ?? $plural_formulas['default'];
    }
    // In case there is no plural formula for English (no imported translation
    // for English), use a default formula.
    elseif ($langcode == 'en') {
      return (int) ($count != 1);
    }
    // Otherwise, return -1 (unknown).
    else {
      return -1;
    }
  }

}
