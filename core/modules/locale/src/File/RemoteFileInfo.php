<?php

namespace Drupal\locale\File;

/**
 * Provides the locale remote file information.
 *
 *   Value object of file data with the following properties:
 *   - lastModified: Last modified timestamp of the translation file.
 *   - (optional) location: The location of the translation file. It is only
 *     set when a redirect (301) has occurred.
 *   - status: RemoteFileStatus enum
 *     - ::Success if the request was successful
 *     - ::Missing if a 404 occurred
 *     - ::Error if an exception other than 404 is encountered
 */
class RemoteFileInfo {

  /**
   * The status of the file check.
   *
   * @var \Drupal\locale\File\RemoteFileStatus|null
   */
  public ?RemoteFileStatus $status;

  /**
   * The file uri if a redirect occurred.
   *
   * @var string|null
   */
  public ?string $location = NULL;

  /**
   * When the translation was last modified.
   *
   * @var int|null
   */
  public ?int $lastModified = NULL;

}
