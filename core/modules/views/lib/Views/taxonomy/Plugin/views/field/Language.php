<?php

/**
 * @file
 * Definition of Views\taxonomy\Plugin\views\field\Language.
 */

namespace Views\taxonomy\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to show the language of a taxonomy term.
 *
 * @Plugin(
 *   id = "taxonomy_term_language"
 * )
 */
class Language extends Taxonomy {

  /**
   * Overrides Views\taxonomy\Plugin\views\field\Taxonomy::render().
   */
  public function render($values) {
    $value = $this->get_value($values);
    $language = language_load($value);
    $value = $language ? $language->name : '';

    return $this->render_link($this->sanitizeValue($value), $values);
  }

}
