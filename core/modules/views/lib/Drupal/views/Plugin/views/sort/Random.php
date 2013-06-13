<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\sort\Random.
 */

namespace Drupal\views\Plugin\views\sort;

use Drupal\Component\Annotation\PluginID;

/**
 * Handle a random sort.
 *
 * @PluginID("random")
 */
class Random extends SortPluginBase {

  public function query() {
    $this->query->addOrderBy('rand');
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['order']['#access'] = FALSE;
  }

}
