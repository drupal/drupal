<?php

namespace Drupal\media_library;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Defines a class to get media library openers from the container.
 *
 * This is intended to be a very thin interface-verifying wrapper around
 * services which implement \Drupal\media_library\MediaLibraryOpenerInterface.
 * It is not an API and should not be extended or used by code that does not
 * interact with the Media Library module.
 */
class OpenerResolver implements OpenerResolverInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function get(MediaLibraryState $state) {
    $service_id = $state->getOpenerId();

    $service = $this->container->get($service_id);
    if ($service instanceof MediaLibraryOpenerInterface) {
      return $service;
    }
    throw new \RuntimeException("$service_id must be an instance of " . MediaLibraryOpenerInterface::class);
  }

}
