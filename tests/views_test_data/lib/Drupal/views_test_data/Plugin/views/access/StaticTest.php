<?php

/**
 * @file
 * Definition of Drupal\views_test_data\Plugin\views\access\StaticTest.
 */

namespace Drupal\views_test_data\Plugin\views\access;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\access\AccessPluginBase;

/**
 * Tests a static access plugin.
 *
 * @Plugin(
 *   id = "test_static",
 *   title = @Translation("Static test access plugin"),
 *   help = @Translation("Provides a static test access plugin.")
 * )
 */
class StaticTest extends AccessPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['access'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  public function access($account) {
    return !empty($this->options['access']);
  }

  function get_access_callback() {
    return array('views_test_data_test_static_access_callback', array(!empty($options['access'])));
  }

}
