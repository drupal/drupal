<?php

namespace Drupal\locale;

use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Overrides the language_manager service to point to language's module one.
 */
class LocaleServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if ($container->hasParameter('language.translate_english')) {
      $config_storage = BootstrapConfigStorageFactory::get();
      $config = $config_storage->read('locale.settings');
      if ($config) {
        $container->setParameter('language.translate_english', $config['translate_english'] ?? TRUE);
      }
    }
  }

}
