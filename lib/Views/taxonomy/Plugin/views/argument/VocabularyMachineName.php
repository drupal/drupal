<?php

/**
 * @file
 * Definition of Views\taxonomy\Plugin\views\argument\VocabularyMachineName.
 */

namespace Views\taxonomy\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\String;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler to accept a vocabulary machine name.
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "vocabulary_machine_name",
 *   module = "taxonomy"
 * )
 */
class VocabularyMachineName extends String {

  /**
   * Override the behavior of title(). Get the name of the vocabulary..
   */
  function title() {
    $query = db_select('taxonomy_vocabulary', 'v');
    $query->addField('v', 'name');
    $query->condition('v.machine_name', $this->argument);
    $title = $query->execute()->fetchField();

    if (empty($title)) {
      return t('No vocabulary');
    }

    return check_plain($title);
  }

}
