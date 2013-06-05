<?php

/**
 * @file
 * Definition of Drupal\file\Plugin\views\argument\Fid.
 */

namespace Drupal\file\Plugin\views\argument;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\argument\Numeric;

/**
 * Argument handler to accept multiple file ids.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("file_fid")
 */
class Fid extends Numeric {

  /**
   * Override the behavior of titleQuery(). Get the filenames.
   */
  public function titleQuery() {
    $titles = db_select('file_managed', 'f')
      ->fields('f', array('filename'))
      ->condition('fid', $this->value)
      ->execute()
      ->fetchCol();
    foreach ($titles as &$title) {
      $title = check_plain($title);
    }
    return $titles;
  }

}
