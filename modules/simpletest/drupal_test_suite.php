<?php
// $Id: drupal_test_suite.php,v 1.1 2008/04/20 18:34:43 dries Exp $

/**
 * Implementes getTestInstances to allow access to the test objects from outside
 */
class DrupalTestSuite extends TestSuite {
  var $_cleanupModules   = array();

  function DrupalTestSuite($label) {
    $this->TestSuite($label);
  }

  /**
   * @return array of instantiated tests that this GroupTests holds
   */
  function getTestInstances() {
    for ($i = 0, $count = count($this->_test_cases); $i < $count; $i++) {
      if (is_string($this->_test_cases[$i])) {
        $class = $this->_test_cases[$i];
        $this->_test_cases[$i] = &new $class();
      }
    }
    return $this->_test_cases;
  }
}

class DrupalTests extends DrupalTestSuite {
  /**
   * Constructor
   * @param array   $class_list  list containing the classes of tests to be processed
   *                             default: NULL - run all tests
   */
  function DrupalTests($class_list = NULL) {
    static $classes;
    $this->DrupalTestSuite('Drupal Unit Tests');

    /* Tricky part to avoid double inclusion */
    if (!$classes) {

      $files = array();
      foreach (array_keys(module_rebuild_cache()) as $module) {
        $module_path = drupal_get_path('module', $module);
        $test = $module_path . "/$module.test";
        if (file_exists($test)) {
          $files[] = $test;
        }
      }

      $existing_classes = get_declared_classes();
      foreach ($files as $file) {
        include_once($file);
      }
      $classes = array_diff(get_declared_classes(), $existing_classes);
    }
    if (!is_null($class_list)) {
      $classes = $class_list;
    }
    if (count($classes) == 0) {
      drupal_set_message('No test cases found.', 'error');
      return;
    }
    $groups = array();
    foreach ($classes as $class) {
      if (!is_subclass_of($class, 'DrupalWebTestCase') && !is_subclass_of($class, 'DrupalUnitTestCase')) {
        continue;
      }
      $this->_addClassToGroups($groups, $class);
    }
    foreach ($groups as $group_name => $group) {
      $group_test = &new DrupalTestSuite($group_name);
      foreach ($group as $key => $v) {
        $group_test->addTestCase($group[$key]);
      }
      $this->addTestCase($group_test);
    }
  }

  /**
   * Adds a class to a groups array specified by the getInfo of the group
   * @param array  $groups Group of categorized tests
   * @param string $class  Name of a class
   */
  function _addClassToGroups(&$groups, $class) {
    $test = &new $class();
    if (method_exists($test, 'getInfo')) {
      $info = $test->getInfo();
      $groups[$info['group']][] = $test;
    }
  }

  /**
   * Invokes run() on all of the held test cases, instantiating
   * them if necessary.
   * The Drupal version uses paintHeader instead of paintGroupStart
   * to avoid collapsing of the very top level.
   *
   * @param SimpleReporter $reporter    Current test reporter.
   * @access public
   */
  function run(&$reporter) {
    cache_clear_all();
    @set_time_limit(0);
    ignore_user_abort(TRUE);

    // Disable devel output, check simpletest settings page
    if (!variable_get('simpletest_devel', FALSE)) {
      $GLOBALS['devel_shutdown'] = FALSE;
    }

    $result = parent::run($reporter);

    // Restores modules
    foreach ($this->_cleanupModules as $name => $status) {
      db_query("UPDATE {system} SET status = %d WHERE name = '%s' AND type = 'module'", $status, $name);
    }
    $this->_cleanupModules = array();

    return $result;
  }
}
