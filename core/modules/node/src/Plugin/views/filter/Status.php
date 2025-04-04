<?php

namespace Drupal\node\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters out unpublished content if the current user cannot view it.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("node_status")]
class Status extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {}

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if ($this->moduleHandler->hasImplementations('node_grants')) {
      return;
    }
    $table = $this->ensureMyTable();
    $snippet = "$table.status = 1 OR ($table.uid = ***CURRENT_USER*** AND ***CURRENT_USER*** <> 0 AND ***VIEW_OWN_UNPUBLISHED_NODES*** = 1) OR ***BYPASS_NODE_ACCESS*** = 1";
    if ($this->moduleHandler->moduleExists('content_moderation')) {
      $snippet .= ' OR ***VIEW_ANY_UNPUBLISHED_NODES*** = 1';
    }
    $this->query->addWhereExpression($this->options['group'], $snippet);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    $contexts[] = 'user';

    return $contexts;
  }

}
