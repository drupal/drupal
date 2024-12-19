<?php

namespace Drupal\views\Plugin\views\sort;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsSort;

/**
 * Handle a random sort.
 */
#[ViewsSort("random")]
class Random extends SortPluginBase implements CacheableDependencyInterface {

  use UncacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->query->addOrderBy('rand');
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['order']['#access'] = FALSE;
  }

}
