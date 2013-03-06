<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\sort\Random.
 */

namespace Drupal\views\Plugin\views\sort;

use Drupal\Component\Annotation\Plugin;

/**
 * Handle a random sort.
 *
 * @Plugin(
 *   id = "random"
 * )
 */
class Random extends SortPluginBase {

  public function query() {
    $this->query->add_orderby('rand');
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['order']['#access'] = FALSE;
  }

}
