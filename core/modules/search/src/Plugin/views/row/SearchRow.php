<?php

/**
 * @file
 * Definition of Drupal\search\Plugin\views\row\SearchRow.
 */

namespace Drupal\search\Plugin\views\row;

use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * Row handler plugin for displaying search results.
 *
 * @ViewsRow(
 *   id = "search_view",
 *   title = @Translation("Search results"),
 *   help = @Translation("Provides a row plugin to display search results.")
 * )
 */
class SearchRow extends RowPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['score'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['score'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display score'),
      '#default_value' => $this->options['score'],
    );
  }

  /**
   * {@inheritdoc}
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
