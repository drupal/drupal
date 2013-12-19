<?php

/**
 * @file
 * Definition of Drupal\views_test_data\Plugin\views\area\TestExample
 */

namespace Drupal\views_test_data\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Test area plugin.
 *
 * @see \Drupal\views\Tests\Handler\AreaTest
 *
 * @PluginID("test_example")
 */
class TestExample extends AreaPluginBase {

  /**
   * Overrides Drupal\views\Plugin\views\area\AreaPluginBase::option_definition().
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['string'] = array('default' => '');

    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\area\AreaPluginBase::buildOptionsForm()
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $this->globalTokenForm($form, $form_state);
  }

  /**
   * Implements \Drupal\views\Plugin\views\area\AreaPluginBase::render().
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      return array(
        '#markup' => $this->globalTokenReplace($this->options['string']),
      );
    }
    return array();
  }

}
