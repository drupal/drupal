<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\field\Language.
 */

namespace Drupal\taxonomy\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to show the language of a taxonomy term.
 *
 * @PluginID("taxonomy_term_language")
 */
class Language extends Taxonomy {

  /**
   * Overrides Drupal\taxonomy\Plugin\views\field\Taxonomy::render().
   */
  public function render($values) {
    $value = $this->getValue($values);
    $language = language_load($value);
    $value = $language ? $language->name : '';

    return $this->render_link($this->sanitizeValue($value), $values);
  }

}
