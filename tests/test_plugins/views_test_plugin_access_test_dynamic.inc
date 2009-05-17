<?php

/**
 * @file
 * Definition of views_test_plugin_access_test_dynamic.
 */

/**
 * Tests a dynamic access plugin.
 */
class views_test_plugin_access_test_dynamic extends views_plugin_access {
  function option_definition() {
    $options = parent::option_definition();
    $options['access'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  function access($account) {
    return !empty($this->options['access']) && isset($this->view->args[0]) && $this->view->args[0] == variable_get('test_dynamic_access_argument1', NULL) && isset($this->view->args[1]) && $this->view->args[1] == variable_get('test_dynamic_access_argument2', NULL);
  }

  function get_access_callback() {
    return array('views_test_test_dynamic_access_callback', array(!empty($options['access']), 1, 2));
  }
}
