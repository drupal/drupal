<?php

namespace Drupal\Core\Installer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Override configuration during the installer.
 */
class ConfigOverride implements ServiceProviderInterface, ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Register this class so that it can override configuration.
    $container
      ->register('core.install_config_override', static::class)
      ->addTag('config.factory.override');
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (drupal_installation_attempted() && function_exists('drupal_install_profile_distribution_name')) {
      // Early in the installer the site name is unknown. In this case we need
      // to fallback to the distribution's name.
      $overrides['system.site'] = [
        'name' => drupal_install_profile_distribution_name(),
      ];
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'core.install_config_override';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

}
