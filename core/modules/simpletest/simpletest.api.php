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
 * This hook will not be invoked by the phpunit tool.
 *
 * @param $groups
 *   A two dimensional array, the first key is the test group, the second is the
 *   name of the test class, and the value is in associative array containing
 *   'name', 'description', 'group', and 'requires' keys.
 *
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Convert
 *   your test to a PHPUnit-based one and implement test listeners.
 *
 * @see https://www.drupal.org/node/2939892
 */
function hook_simpletest_alter(&$groups) {
  // An alternative session handler module would not want to run the original
  // Session HTTPS handling test because it checks the sessions table in the
  // database.
  unset($groups['Session']['testHttpsSession']);
}

/**
 * A test group has started.
 *
 * This hook is called just once at the beginning of a test group.
 *
 * This hook is only invoked by the Simpletest UI form runner. It will not be
 * invoked by run-tests.sh or the phpunit tool.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Convert your
 *   test to a PHPUnit-based one and implement test listeners.
 *
 * @see https://www.drupal.org/node/2934242
 */
function hook_test_group_started() {
}

/**
 * A test group has finished.
 *
 * This hook is called just once at the end of a test group.
 *
 * This hook is only invoked by the Simpletest UI form runner. It will not be
 * invoked by run-tests.sh or the phpunit tool.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Convert your
 *   test to a PHPUnit-based one and implement test listeners.
 *
 * @see https://www.drupal.org/node/2934242
 */
function hook_test_group_finished() {
}

/**
 * An individual test has finished.
 *
 * This hook is called when an individual test has finished.
 *
 * This hook is only invoked by the Simpletest UI form runner. It will not be
 * invoked by run-tests.sh or the phpunit tool.
 *
 * @param
 *   $results The results of the test as gathered by
 *   \Drupal\simpletest\WebTestBase.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Convert your
 *   test to a PHPUnit-based one and implement test listeners.
 *
 * @see https://www.drupal.org/node/2934242
 * @see _simpletest_batch_operation()
 */
function hook_test_finished($results) {
}

/**
 * @} End of "addtogroup hooks".
 */
