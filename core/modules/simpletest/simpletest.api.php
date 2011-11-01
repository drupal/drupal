<?php

/**
 * @file
 * Hooks provided by the SimpleTest module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the list of tests.
 *
 * @param $groups
 *   A two dimension array, the first key is the test group (as defined in
 *   getInfo) the second is the name of the class and the value is the return
 *   value of the getInfo method.
 */
function hook_simpletest_alter(&$groups) {
  // An alternative session handler module would not want to run the original
  // Session https handling test because it checks the sessions table in the
  // database.
  unset($groups['Session']['testHttpsSession']);
}

/**
 * A test group has started.
 *
 * This hook is called just once at the beginning of a test group.
 */
function hook_test_group_started() {
}

/**
 * A test group has finished.
 *
 * This hook is called just once at the end of a test group.
 */
function hook_test_group_finished() {
}

/**
 * An individual test has finished.
 *
 * This hook is called when an individual test has finished.
 *
 * @param
 *   $results The results of the test as gathered by DrupalWebTestCase.
 *
 * @see DrupalWebTestCase->results
 */
function hook_test_finished($results) {
}


/**
 * @} End of "addtogroup hooks".
 */
