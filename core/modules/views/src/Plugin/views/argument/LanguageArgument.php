<?php

namespace Drupal\views\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;

/**
 * Defines an argument handler to accept a language.
 *
 * @ingroup views_argument_handlers
  */
#[ViewsArgument(
  id: 'language',
)]
class LanguageArgument extends ArgumentPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summaryName($data) {
    // Gets the user-friendly version of the language name.
    return $this->language($data->{$this->name_alias});
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    // Gets the user friendly version of the language name for display as a
    // title placeholder.
    return $this->language($this->argument);
  }

  /**
   * Returns the language name for a given langcode.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   The translated name for the language, or "Unknown language" if the
   *   language was not found.
   */
  public function language($langcode) {
    $languages = $this->listLanguages();
    return $languages[$langcode] ?? $this->t('Unknown language');
  }

}
