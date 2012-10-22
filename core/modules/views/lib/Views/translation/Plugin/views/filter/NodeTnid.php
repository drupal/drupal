<?php

/**
 * @file
 * Definition of Views\translation\Plugin\views\filter\NodeTnid.
 */

namespace Views\translation\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Filter by whether the node is the original translation.
 *
 * @ingroup views_filter_handlers
 *
 * @Plugin(
 *   id = "node_tnid",
 *   module = "translation"
 * )
 */
class NodeTnid extends FilterPluginBase {

  public function adminSummary() { }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['operator']['default'] = 1;

    return $options;
  }

  /**
   * Provide simple boolean operator
   */
  function operator_form(&$form, &$form_state) {
    $form['operator'] = array(
      '#type' => 'radios',
      '#title' => t('Include untranslated content'),
      '#default_value' => $this->operator,
      '#options' => array(
        1 => t('Yes'),
        0 => t('No'),
      ),
    );
  }

  public function canExpose() { return FALSE; }

  public function query() {
    $table = $this->ensureMyTable();
    // Select for source translations (tnid = nid). Conditionally, also accept either untranslated nodes (tnid = 0).
    $this->query->add_where_expression($this->options['group'], "$table.tnid = $table.nid" . ($this->operator ? " OR $table.tnid = 0" : ''));
  }

}
