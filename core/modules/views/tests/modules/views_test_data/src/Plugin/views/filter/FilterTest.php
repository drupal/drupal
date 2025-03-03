<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Provides a test filter plugin for Views.
 */
#[ViewsFilter("test_filter")]
class FilterTest extends FilterPluginBase {

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::defineOptions().
   *
   * @return array
   *   An array of options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['test_enable'] = ['default' => TRUE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['test_enable'] = [
      '#type' => 'checkbox',
      '#title' => 'Controls whether the filter plugin should be active',
      '#default_value' => $this->options['test_enable'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Call the parent if this option is enabled.
    if ($this->options['test_enable']) {
      parent::query();
    }
  }

}
