<?php

/**
 * @file
 * Definition of Views\taxonomy\Plugin\views\argument\VocabularyVid.
 */

namespace Views\taxonomy\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Numeric;

/**
 * Argument handler to accept a vocabulary id.
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "vocabulary_vid",
 *   module = "taxonomy"
 * )
 */
class VocabularyVid extends Numeric {

  /**
   * Override the behavior of title(). Get the name of the vocabulary.
   */
  function title() {
    $title = db_query("SELECT v.name FROM {taxonomy_vocabulary} v WHERE v.vid = :vid", array(':vid' => $this->argument))->fetchField();

    if (empty($title)) {
      return t('No vocabulary');
    }

    return check_plain($title);
  }

}
