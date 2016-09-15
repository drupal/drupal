<?php

namespace Drupal\config\Tests;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Serialization\Yaml;
use Drupal\simpletest\InstallerTestBase;

/**
 * Tests install profile config overrides can not add unmet dependencies.
 *
 * @group Config
 */
class ConfigInstallProfileUnmetDependenciesTest extends InstallerTestBase {

  /**
   * The installation profile to install.
   *
   * @var string
   */
  protected $profile = 'testing_config_overrides';

  /**
   * Set to TRUE if the expected exception is thrown.
   *
   * @var bool
   */
  protected $expectedException = FALSE;

  protected function setUp() {
    // Copy the testing_config_overrides install profile so we can change the
    // configuration to include a dependency that can not be met. File API
    // functions are not available yet.
    $dest = $this->siteDirectory . '/profiles/testing_config_overrides';
    mkdir($dest, 0777, TRUE);
    $source = DRUPAL_ROOT . '/core/profiles/testing_config_overrides';
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $item) {
      if ($item->isDir()) {
        mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
      }
      else {
        copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
      }
    }

    // Add a dependency that can not be met because User is installed before
    // Action.
    $config_file = $dest . DIRECTORY_SEPARATOR . InstallStorage::CONFIG_INSTALL_DIRECTORY . DIRECTORY_SEPARATOR . 'system.action.user_block_user_action.yml';
    $action = Yaml::decode(file_get_contents($config_file));
    $action['dependencies']['module'][] = 'action';
    file_put_contents($config_file, Yaml::encode($action));

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   *
   * Override the error method so we can test for the expected exception.
   */
  protected function error($message = '', $group = 'Other', array $caller = NULL) {
    if ($group == 'User notice') {
      // Since 'User notice' is set by trigger_error() which is used for debug
      // set the message to a status of 'debug'.
      return $this->assert('debug', $message, 'Debug', $caller);
    }
    if ($group == 'Drupal\Core\Config\UnmetDependenciesException') {
      $this->expectedException = TRUE;
      return FALSE;
    }
    return $this->assert('exception', $message, $group, $caller);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite() {
    // This step is not reached due to the exception.
  }


  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled() {
    if ($this->expectedException) {
      $this->pass('Expected Drupal\Core\Config\UnmetDependenciesException exception thrown');
    }
    else {
      $this->fail('Expected Drupal\Core\Config\UnmetDependenciesException exception thrown');
    }
    $this->assertErrorLogged('Configuration objects provided by <em class="placeholder">user</em> have unmet dependencies: <em class="placeholder">system.action.user_block_user_action (action)</em>');
  }

}
