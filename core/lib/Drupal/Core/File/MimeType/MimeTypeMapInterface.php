<?php

declare(strict_types=1);

namespace Drupal\Core\File\MimeType;

/**
 * Provides an interface for MIME type to file extension mapping.
 */
interface MimeTypeMapInterface {

  /**
   * Adds a mapping between a MIME type and an extension.
   *
   * @param string $mimetype
   *   The MIME type the passed extension should map.
   * @param string $extension
   *   The extension(s) that should map to the passed MIME type.
   *
   * @return $this
   */
  public function addMapping(string $mimetype, string $extension): self;

  /**
   * Removes the mapping between a MIME type and an extension.
   *
   * @param string $mimetype
   *   The MIME type to be removed from the mapping.
   * @param string $extension
   *   The extension to be removed from the mapping.
   *
   * @return bool
   *   TRUE if the extension was present, FALSE otherwise.
   */
  public function removeMapping(string $mimetype, string $extension): bool;

  /**
   * Removes a MIME type and all its mapped extensions from the mapping.
   *
   * @param string $mimetype
   *   The MIME type to be removed from the mapping.
   *
   * @return bool
   *   TRUE if the MIME type was present, FALSE otherwise.
   */
  public function removeMimeType(string $mimetype): bool;

  /**
   * Returns known MIME types.
   *
   * @return string[]
   *   An array of MIME types.
   */
  public function listMimeTypes(): array;

  /**
   * Returns known file extensions.
   *
   * @return string[]
   *   An array of file extensions.
   */
  public function listExtensions(): array;

  /**
   * Determines if a MIME type exists.
   *
   * @param string $mimetype
   *   The mime type.
   *
   * @return bool
   *   TRUE if the MIME type exists, FALSE otherwise.
   */
  public function hasMimeType(string $mimetype): bool;

  /**
   * Determines if a file extension exists.
   *
   * @param string $extension
   *   The file extension.
   *
   * @return bool
   *   TRUE if the file extension exists, FALSE otherwise.
   */
  public function hasExtension(string $extension): bool;

  /**
   * Returns the appropriate MIME type for a given file extension.
   *
   * @param string $extension
   *   A file extension, without leading dot.
   *
   * @return string|null
   *   A matching MIME type, or NULL if no MIME type matches the extension.
   */
  public function getMimeTypeForExtension(string $extension): ?string;

  /**
   * Returns the appropriate extensions for a given MIME type.
   *
   * @param string $mimetype
   *   A MIME type.
   *
   * @return string[]
   *   An array of file extensions matching the MIME type, without leading dot.
   */
  public function getExtensionsForMimeType(string $mimetype): array;

}
