<?php

namespace Drupal\media_library;

/**
 * Defines a class to resolve media library openers.
 *
 * This is intended to be a very thin interface-verifying wrapper around
 * services which implement \Drupal\media_library\MediaLibraryOpenerInterface.
 * It is not an API and should not be extended or used by code that does not
 * interact with the Media Library module.
 *
 * @internal
 *   This service is an internal part of the modal media library dialog and
 *   does not provide any extension points or public API.
 */
class OpenerResolver implements OpenerResolverInterface {

  /**
   * @var \Drupal\media_library\MediaLibraryOpenerInterface[]
   */
  protected array $openers = [];

  /**
   * Registers an opener.
   *
   * @param \Drupal\media_library\MediaLibraryOpenerInterface $opener
   *   The opener.
   * @param string $id
   *   The service ID.
   */
  public function addOpener(MediaLibraryOpenerInterface $opener, string $id): void {
    $this->openers[$id] = $opener;
  }

  /**
   * {@inheritdoc}
   */
  public function get(MediaLibraryState $state) {
    $service_id = $state->getOpenerId();

    $service = $this->openers[$service_id] ?? NULL;
    if ($service instanceof MediaLibraryOpenerInterface) {
      return $service;
    }
    throw new \RuntimeException("$service_id must be an instance of " . MediaLibraryOpenerInterface::class);
  }

}
