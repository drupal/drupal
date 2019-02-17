<?php

namespace Drupal\taxonomy\Plugin\views\argument;

use Drupal\taxonomy\Entity\Term;
use Drupal\views\Plugin\views\argument\ManyToOne;

/**
 * Allow taxonomy term ID(s) as argument.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("taxonomy_index_tid")
 */
class IndexTid extends ManyToOne {

  public function titleQuery() {
    $titles = [];
    $terms = Term::loadMultiple($this->value);
    foreach ($terms as $term) {
      $titles[] = \Drupal::service('entity.repository')->getTranslationFromContext($term)->label();
    }
    return $titles;
  }

}
