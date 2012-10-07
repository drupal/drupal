<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\argument\VocabularyVid.
 */

namespace Drupal\taxonomy\Plugin\views\argument;

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
    $query = db_select('taxonomy_vocabulary', 'v');
    $query->addField('v', 'name');
    $query->condition('v.vid', $this->argument);
    $title = $query->execute()->fetchField();
    if (empty($title)) {
      return t('No vocabulary');
    }

    return check_plain($title);
  }

}
