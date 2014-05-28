<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\sort\Random.
 */

namespace Drupal\views\Plugin\views\sort;

/**
 * Handle a random sort.
 *
 * @ViewsSort("random")
 */
class Random extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  public function query() {
    $this->query->addOrderBy('rand');
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['order']['#access'] = FALSE;
  }

}
