<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\field\Language.
 */

namespace Drupal\taxonomy\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Field handler to show the language of a taxonomy term.
 *
 * @ViewsField("taxonomy_term_language")
 */
class Language extends Taxonomy {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $language = \Drupal::languageManager()->getLanguage($value);
    $value = $language ? $language->name : '';

    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
