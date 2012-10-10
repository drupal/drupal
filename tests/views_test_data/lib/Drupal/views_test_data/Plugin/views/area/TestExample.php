<?php

/**
 * @file
 * Definition of Drupal\views_test_data\Plugin\views\area\TestExample
 */

namespace Drupal\views_test_data\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Test area plugin.
 *
 * @see Drupal\views\Tests\Handler\AreaTest
 *
 * @Plugin(
 *   id = "test_example"
 * )
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
   * Overrides Drupal\views\Plugin\views\area\AreaPluginBase::render().
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      return $this->options['string'];
    }
  }

}
