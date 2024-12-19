<?php

namespace Drupal\taxonomy\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\TaxonomyIndexDepthQueryTrait;
use Drupal\views\Attribute\ViewsFilter;

/**
 * Filter handler for taxonomy terms with depth.
 *
 * This handler is actually part of the node table and has some restrictions,
 * because it uses a subquery to find nodes with.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("taxonomy_index_tid_depth")]
class TaxonomyIndexTidDepth extends TaxonomyIndexTid {
  use TaxonomyIndexDepthQueryTrait;

  /**
   * {@inheritdoc}
   */
  public function operatorOptions($which = 'title') {
    return [
      'or' => $this->t('Is one of'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['depth'] = ['default' => 0];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildExtraOptionsForm($form, $form_state);

    $form['depth'] = [
      '#type' => 'weight',
      '#title' => $this->t('Depth'),
      '#default_value' => $this->options['depth'],
      '#description' => $this->t('The depth will match nodes tagged with terms in the hierarchy. For example, if you have the term "fruit" and a child term "apple", with a depth of 1 (or higher) then filtering for the term "fruit" will get nodes that are tagged with "apple" as well as "fruit". If negative, the reverse is true; searching for "apple" will also pick up nodes tagged with "fruit" if depth is -1 (or lower).'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // If no filter values are present, then do nothing.
    if (count($this->value) == 0) {
      return;
    }
    elseif (count($this->value) == 1) {
      // Sometimes $this->value is an array with a single element so convert it.
      if (is_array($this->value)) {
        $this->value = current($this->value);
      }
    }

    // The normal use of ensureMyTable() here breaks Views.
    // So instead we trick the filter into using the alias of the base table.
    // See https://www.drupal.org/node/271833.
    // If a relationship is set, we must use the alias it provides.
    if (!empty($this->relationship)) {
      $this->tableAlias = $this->relationship;
    }
    // If no relationship, then use the alias of the base table.
    else {
      $this->tableAlias = $this->query->ensureTable($this->view->storage->get('base_table'));
    }

    $this->addSubQueryJoin($this->value);
  }

}
