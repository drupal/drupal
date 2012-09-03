<?php

/**
 * @file
 * Definition of Drupal\views_test_data\Plugin\views\access\DynamicTest.
 */

namespace Drupal\views_test_data\Plugin\views\access;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\access\AccessPluginBase;

/**
 * Tests a dynamic access plugin.
 *
 * @Plugin(
 *   id = "test_dynamic",
 *   title = @Translation("Dynamic test access plugin."),
 *   help = @Translation("Provides a dynamic test access plugin.")
 * )
 */
class DynamicTest extends AccessPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['access'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  public function access($account) {
    return !empty($this->options['access']) && isset($this->view->args[0]) && $this->view->args[0] == variable_get('test_dynamic_access_argument1', NULL) && isset($this->view->args[1]) && $this->view->args[1] == variable_get('test_dynamic_access_argument2', NULL);
  }

  function get_access_callback() {
    return array('views_test_data_test_dynamic_access_callback', array(!empty($options['access']), 1, 2));
  }

}
