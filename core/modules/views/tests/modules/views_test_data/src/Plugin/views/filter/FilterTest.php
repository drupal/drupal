<?php

/**
 * @file
 * Definition of Drupal\views_test_data\Plugin\views\filter\FilterTest.
 */

namespace Drupal\views_test_data\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * @ViewsFilter("test_filter")
 */
class FilterTest extends FilterPluginBase {

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::defineOptions().
   *
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['test_enable'] = array('default' => TRUE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::buildOptionsForm().
   *
   * @return array
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['test_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Controls whether the filter plugin should be active'),
      '#default_value' => $this->options['test_enable'],
    );
  }

  /**
   * Overrides Drupal\views\Plugin\views\filter\FilterPluginBase::query().
   */
  public function query() {
    // Call the parent if this option is enabled.
    if ($this->options['test_enable']) {
      parent::query();
    }
  }

}
