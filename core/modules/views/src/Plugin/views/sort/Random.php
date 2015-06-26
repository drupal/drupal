<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\sort\Random.
 */

namespace Drupal\views\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\CacheablePluginInterface;

/**
 * Handle a random sort.
 *
 * @ViewsSort("random")
 */
class Random extends SortPluginBase implements CacheablePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  public function query() {
    $this->query->addOrderBy('rand');
    // @todo Replace this once https://www.drupal.org/node/2464427 is in.
    $this->view->element['#cache']['max-age'] = 0;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['order']['#access'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

}
