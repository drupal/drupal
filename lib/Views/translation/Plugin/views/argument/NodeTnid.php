<?php

/**
 * @file
 * Definition of Views\translation\Plugin\views\argument\NodeTnid.
 */

namespace Views\translation\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler to accept a node translation id.
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "node_tnid",
 *   module = "translation"
 * )
 */
class NodeTnid extends Numeric {

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  function title_query() {
    $titles = array();

    $query = db_select('node', 'n');
    $query->addField('n', 'title');
    $query->condition('n.tnid', $this->value);
    $result = $query->execute();
    foreach ($result as $term) {
      $titles[] = check_plain($term->title);
    }
    return $titles;
  }

}
