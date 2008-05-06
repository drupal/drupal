<?php
// $Id$

/**
 * Implements getTestInstances to allow access to the test objects from outside.
 */
class DrupalTestSuite extends TestSuite {
  var $_cleanupModules = array();

  function DrupalTestSuite($label) {
    $this->TestSuite($label);
  }

  /**
   * @return
   *   An array of instantiated tests that this GroupTests holds.
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
   * Constructor for the DrupalTests class.
   *
   * @param array $class_list
   *   List containing the classes of tests to be processed,
   *   defaults to process all tests.
   */
  function DrupalTests($class_list = NULL) {
    static $classes;
    $this->DrupalTestSuite('Drupal Unit Tests');

    // Tricky part to avoid double inclusion.
    if (!$classes) {

      $files = $this->getFiles();

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
      if ($this->classIsTest($class)) {
        $this->_addClassToGroups($groups, $class);
      }
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
   * Adds a class to a groups array specified by the getInfo() of the group.
   *
   * @param array $groups
   *   Group of categorized tests.
   * @param string $class
   *   Name of the class.
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
   * @param SimpleReporter $reporter
   *   Current test reporter.
   * @access public
   */
  function run(&$reporter) {
    @set_time_limit(0);
    ignore_user_abort(TRUE);

    $this->cleanupBeforeRun();
    $result = parent::run($reporter);
    return $result;
  }

  /**
   * Gets the files which contains the tests.
   *
   * @return
   *   A list of files that contains the tests.
   */
  function getFiles() {
    $files = array();
    foreach (array_keys(module_rebuild_cache()) as $module) {
      $module_path = drupal_get_path('module', $module);
      $test = $module_path . "/$module.test";
      if (file_exists($test)) {
        $files[] = $test;
      }
    }
    foreach (file_scan_directory('includes', '\.test$') as $file) {
      $files[] = $file->filename;
    }
    return $files;
  }

  /**
   * Determines whether the class is a test.
   *
   * @return
   *   TRUE / FALSE depending on whether the class is a test.
   */
  function classIsTest($class) {
    return is_subclass_of($class, 'DrupalWebTestCase');
  }

  /**
   * Called before the tests are run.
   */
  function cleanupBeforeRun() {
    cache_clear_all();
    // Disable devel output, check simpletest settings page.
    if (!variable_get('simpletest_devel', FALSE)) {
      $GLOBALS['devel_shutdown'] = FALSE;
    }
  }
}
