<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\ResultRow;

/**
 * Field handler to show a counter of the current row.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("counter")]
class Counter extends FieldPluginBase {

  use UncacheableFieldHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['counter_start'] = ['default' => 1];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['counter_start'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Starting value'),
      '#default_value' => $this->options['counter_start'],
      '#description' => $this->t('Specify the number the counter should start at.'),
      '#size' => 2,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    // Note:  1 is subtracted from the counter start value below because the
    // counter value is incremented by 1 at the end of this function.
    $count = is_numeric($this->options['counter_start']) ? $this->options['counter_start'] - 1 : 0;
    $pager = $this->view->pager;
    // Get the base count of the pager.
    if ($pager->usePager()) {
      $count += ($pager->getItemsPerPage() * $pager->getCurrentPage() + $pager->getOffset());
    }
    // Add the counter for the current site.
    $count += $this->view->row_index + 1;

    return $count;
  }

}
