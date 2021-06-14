<?php

namespace Drupal\views_test_data\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Test area plugin.
 *
 * @see \Drupal\views\Tests\Handler\AreaTest
 *
 * @ViewsArea("test_example")
 */
class TestExample extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $this->options['custom_access'];
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['string'] = ['default' => ''];
    $options['custom_access'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $this->globalTokenForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      return [
        '#markup' => $this->globalTokenReplace($this->options['string']),
      ];
    }
    return [];
  }

}
