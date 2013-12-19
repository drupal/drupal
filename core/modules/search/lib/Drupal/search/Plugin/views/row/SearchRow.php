<?php

/**
 * @file
 * Definition of Drupal\search\Plugin\views\row\SearchRow.
 */

namespace Drupal\search\Plugin\views\row;

use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * Plugin which performs a node_view on the resulting object.
 *
 * @ViewsRow(
 *   id = "search_view",
 *   title = @Translation("Search"),
 *   help = @Translation("Provides a row plugin to display search results.")
 * )
 */
class SearchRow extends RowPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['score'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['score'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display score'),
      '#default_value' => $this->options['score'],
    );
  }

  /**
   * Override the behavior of the render() function.
   */
  public function render($row) {
    return array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $row,
    );
  }

}
