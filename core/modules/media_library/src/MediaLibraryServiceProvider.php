<?php

declare(strict_types=1);

namespace Drupal\media_library;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Service provider for media library services.
 */
class MediaLibraryServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->registerForAutoconfiguration(MediaLibraryOpenerInterface::class)
      ->addTag('media_library.opener');
  }

}
