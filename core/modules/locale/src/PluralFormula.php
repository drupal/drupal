<?php

namespace Drupal\locale;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Manages the storage of plural formula per language in state.
 *
 * @see \Drupal\locale\PoDatabaseWriter::setHeader()
 */
class PluralFormula implements PluralFormulaInterface {

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The plural formula and count keyed by langcode.
   *
   * For example the structure looks like this:
   * @code
   * [
   *   'de' => [
   *     'plurals' => 2,
   *     'formula' => [
   *       // @todo
   *     ]
   *   ],
   * ]
   * @endcode
   * @var array
   */
  protected $formulae;

  /**
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(LanguageManagerInterface $language_manager, StateInterface $state) {
    $this->languageManager = $language_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluralFormula($langcode, $plural_count, array $formula) {
    // Ensure that the formulae are loaded.
    $this->loadFormulae();

    $this->formulae[$langcode] = [
      'plurals' => $plural_count,
      'formula' => $formula,
    ];
    $this->state->set('locale.translation.formulae', $this->formulae);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfPlurals($langcode = NULL) {
    // Ensure that the formulae are loaded.
    $this->loadFormulae();

    // Set the langcode to use.
    $langcode = $langcode ?: $this->languageManager->getCurrentLanguage()->getId();

    // We assume 2 plurals if there is no explicit information yet.
    if (!isset($this->formulae[$langcode]['plurals'])) {
      return 2;
    }
    return $this->formulae[$langcode]['plurals'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormula($langcode) {
    $this->loadFormulae();
    return $this->formulae[$langcode]['formula'] ?? FALSE;
  }

  /**
   * Loads the formulae and stores them on the PluralFormula object if not set.
   *
   * @return array
   */
  protected function loadFormulae() {
    if (!isset($this->formulae)) {
      $this->formulae = $this->state->get('locale.translation.formulae', []);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->formulae = NULL;
    return $this;
  }

}
