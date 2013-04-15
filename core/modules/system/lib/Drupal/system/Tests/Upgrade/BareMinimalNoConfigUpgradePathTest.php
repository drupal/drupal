<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Upgrade\BareMinimalNoConfigUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Tests the database upgrade path without creating config directories.
 */
class BareMinimalNoConfigUpgradePathTest extends BareMinimalUpgradePathTest {

  public static function getInfo() {
    return array(
      'name'  => 'Basic minimal profile upgrade, no config',
      'description'  => 'Basic upgrade path tests for a minimal profile install with a bare database and config directory not pre-created.',
      'group' => 'Upgrade path',
    );
  }

  /**
   * Overrides \Drupal\system\Tests\Upgrade\UpgradePathTestBase::setUp().
   */
  public function setUp() {
    parent::setUp();

    // Override $conf_path and $config_directories in settings.php.
    $settings['conf_path'] = (object) array(
      'value' => $this->public_files_directory,
      'required' => TRUE,
    );
    $settings['config_directories'] = (object) array(
      'value' => array(),
      'required' => TRUE,
    );
    $this->writeSettings($settings);
  }

  /**
   * Overrides \Drupal\system\Tests\Upgrade\UpgradePathTestBase::refreshVariables().
   */
  protected function refreshVariables() {
    // Refresh the variables only if the site was already upgraded.
    if ($this->upgradedSite) {
      // update.php puts the new, randomized config directries in this file.
      include $this->public_files_directory . '/settings.php';
      $GLOBALS['config_directories'] = array();
      foreach ($config_directories as $type => $data) {
        // update.php runs as the child site, so writes the paths relative to
        // that "$conf_path/files", but here, we're running as the parent site,
        // so need to make the paths relative to our "conf_path()/files".
        //
        // Example:
        // - Parent site conf_path(): 'sites/default'
        // - Child site $conf_path: 'sites/default/files/simpletest/123456'
        // - Child site $data['path']: 'config_xyz'
        // - Desired result: 'simpletest/123456/files/config_xyz'
        //
        // @see config_get_config_directory()
        $GLOBALS['config_directories'][$type]['path'] = substr($conf_path, strlen(conf_path() . '/files/')) . '/files/' . $data['path'];
      }
      parent::refreshVariables();
    }
  }

}
