<?php

namespace Drupal\locale;

/**
 * An interface for a service providing plural formulas.
 *
 * The formula comes from the gettext GNU specification.
 * At the top of each translation po file the number of plurals and the formula
 * is defined.
 * For Polish nplurals=3;
 * phpcs:ignore Drupal.Files.LineLength
 * plural=((n==1)?(0):(((((n%10)>=2)&&((n%10)<=4))&&(((n%100)<10)||((n%100)>=20)))?(1):2));\n
 *
 * @see https://docs.translatehouse.org/projects/localization-guide/en/latest/l10n/pluralforms.html
 */
interface PluralFormulaInterface {

  /**
   * @param string $langcode
   *   The language code to get the formula for.
   * @param int $plural_count
   *   The number of plural forms.
   * @param array $formula
   *   An array of formulae.
   *
   * @return self
   *   The PluralFormula object.
   */
  public function setPluralFormula($langcode, $plural_count, array $formula);

  /**
   * Returns the number of plurals supported by a given language.
   *
   * @param null|string $langcode
   *   (optional) The language code. If not provided, the current language
   *   will be used.
   *
   * @return int
   *   Number of plural variants supported by the given language.
   */
  public function getNumberOfPlurals($langcode = NULL);

  /**
   * Gets the plural formula for a langcode.
   *
   * @param string $langcode
   *   The language code to get the formula for.
   *
   * @return array
   *   An array of formulae.
   */
  public function getFormula($langcode);

  /**
   * Resets the static formulae cache.
   *
   * @return self
   *   The PluralFormula object.
   */
  public function reset();

}
