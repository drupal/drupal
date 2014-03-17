<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\argument\IndexTid.
 */

namespace Drupal\taxonomy\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ManyToOne;
use Drupal\Component\Utility\String;

/**
 * Allow taxonomy term ID(s) as argument.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("taxonomy_index_tid")
 */
class IndexTid extends ManyToOne {

  public function titleQuery() {
    $titles = array();
    $result = db_select('taxonomy_term_data', 'td')
      ->fields('td', array('name'))
      ->condition('td.tid', $this->value)
      ->execute();
    foreach ($result as $term_record) {
      $titles[] = String::checkPlain($term_record->name);
    }
    return $titles;
  }

}
