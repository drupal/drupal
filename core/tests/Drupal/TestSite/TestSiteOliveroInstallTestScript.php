<?php

namespace Drupal\TestSite;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeInstallerInterface;

/**
 * Setup file used by TestSiteInstallTestScript.
 *
 * @see \Drupal\Tests\Scripts\TestSiteApplicationTest
 */
class TestSiteOliveroInstallTestScript implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function setup() {
    // Install required module for the Olivero front page.
    $module_installer = \Drupal::service('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->install(['views', 'olivero_test']);

    // Install Olivero and set it as the default theme.
    $theme_installer = \Drupal::service('theme_installer');
    assert($theme_installer instanceof ThemeInstallerInterface);
    $theme_installer->install(['olivero'], TRUE);
    $system_theme_config = \Drupal::configFactory()->getEditable('system.theme');
    $system_theme_config->set('default', 'olivero')->save();
  }

}
