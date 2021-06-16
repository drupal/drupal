<?php

namespace Drupal\media_test_oembed;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Replaces oEmbed-related media services with testing versions.
 */
class MediaTestOembedServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    parent::alter($container);

    $container->getDefinition('media.oembed.provider_repository')
      ->setClass(ProviderRepository::class);

    $container->getDefinition('media.oembed.url_resolver')
      ->setClass(UrlResolver::class);
  }

}
