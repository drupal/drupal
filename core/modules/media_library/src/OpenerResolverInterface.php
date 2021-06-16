<?php

namespace Drupal\media_library;

/**
 * Defines an interface to get a media library opener from the container.
 *
 * This is intended to be a very thin interface-verifying wrapper around
 * services which implement \Drupal\media_library\MediaLibraryOpenerInterface.
 * It is not an API and should not be extended or used by code that does not
 * interact with the Media Library module.
 *
 * @internal
 *   This interface is an internal part of the modal media library dialog and
 *   is only implemented by \Drupal\media_library\OpenerResolver. It is not a
 *   public API.
 */
interface OpenerResolverInterface {

  /**
   * Gets a media library opener service from the container.
   *
   * @param \Drupal\media_library\MediaLibraryState $state
   *   A value object representing the state of the media library.
   *
   * @return \Drupal\media_library\MediaLibraryOpenerInterface
   *   The media library opener service.
   *
   * @throws \RuntimeException
   *   If the requested opener service does not implement
   *   \Drupal\media_library\MediaLibraryOpenerInterface.
   */
  public function get(MediaLibraryState $state);

}
