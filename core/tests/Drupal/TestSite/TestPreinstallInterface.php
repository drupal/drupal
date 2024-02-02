<?php

declare(strict_types=1);

namespace Drupal\TestSite;

/**
 * Allows running code prior to a test site install.
 *
 * @see \Drupal\TestSite\Commands\TestSiteInstallCommand
 */
interface TestPreinstallInterface {

  /**
   * Runs code prior to a test site install.
   *
   * This method is run after FunctionalTestSetupTrait::prepareEnvironment()
   * but before Drupal is installed. As such, there is limited setup of the
   * environment and no Drupal API is available.
   *
   * @param string $db_prefix
   *   The database prefix.
   * @param string $site_directory
   *   The site directory.
   *
   * @see \Drupal\TestSite\TestSiteInstallTestScript
   */
  public function preinstall($db_prefix, $site_directory);

}
