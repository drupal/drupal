<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\sort\Random.
 */

namespace Drupal\views\Plugin\views\sort;

use Drupal\Core\Annotation\Plugin;

/**
 * Handle a random sort.
 *
 * @Plugin(
 *   id = "random"
 * )
 */
class Random extends SortPluginBase {
  function query() {
    $this->query->add_orderby('rand');
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['order']['#access'] = FALSE;
  }
}
