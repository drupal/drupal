<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\KernelTests\AssertConfigTrait;

/**
 * Provides a class for install profiles to check their installed config.
 */
abstract class ConfigAfterInstallerTestBase extends InstallerTestBase {

  use AssertConfigTrait;

  /**
   * Ensures that all the installed config looks like the exported one.
   *
   * @param array $skipped_config
   *   An array of skipped config.
   */
  protected function assertInstalledConfig(array $skipped_config) {
    $this->addToAssertionCount(1);
    /** @var \Drupal\Core\Config\StorageInterface $active_config_storage */
    $active_config_storage = $this->container->get('config.storage');
    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = $this->container->get('config.manager');

    $default_install_path = 'core/profiles/' . $this->profile . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
    $profile_config_storage = new FileStorage($default_install_path, StorageInterface::DEFAULT_COLLECTION);

    foreach ($profile_config_storage->listAll() as $config_name) {
      $result = $config_manager->diff($profile_config_storage, $active_config_storage, $config_name);
      try {
        $this->assertConfigDiff($result, $config_name, $skipped_config);
      }
      catch (\Exception $e) {
        $this->fail($e->getMessage());
      }
    }
  }

}
