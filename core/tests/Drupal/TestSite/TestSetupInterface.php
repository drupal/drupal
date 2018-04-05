<?php

namespace Drupal\TestSite;

/**
 * Allows setting up an environment as part of a test site install.
 *
 * @see \Drupal\TestSite\Commands\TestSiteInstallCommand
 */
interface TestSetupInterface {

  /**
   * Run the code to setup the test environment.
   *
   * You have access to any API provided by any installed module. For example,
   * to install modules use:
   * @code
   * \Drupal::service('module_installer')->install(['my_module'])
   * @endcode
   *
   * Check out TestSiteInstallTestScript for an example.
   *
   * @see \Drupal\TestSite\TestSiteInstallTestScript
   */
  public function setup();

}
