<?php

namespace Drupal\Setup;

/**
 * Allows you to setup an environment used for javascript tests.
 */
interface TestSetupInterface {

  /**
   * Run code to setup the test.
   * 
   * You have access to any API provided by any installed module. To install
   * modules use
   * @code
   * \Drupal::service('module_installer')->install(['my_module'])
   * @endcode
   *
   * Check out 'core/tests/Drupal/Setup/ExampleTestSetup.php' for an example.
   */
  public function setup();

}
