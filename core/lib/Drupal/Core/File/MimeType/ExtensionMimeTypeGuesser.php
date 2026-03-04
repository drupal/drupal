<?php

namespace Drupal\Core\File\MimeType;

use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Makes possible to guess the MIME type of a file using its extension.
 */
class ExtensionMimeTypeGuesser implements MimeTypeGuesserInterface {

  /**
   * Constructs a new ExtensionMimeTypeGuesser.
   *
   * @param \Drupal\Core\File\MimeType\MimeTypeMapInterface $map
   *   The MIME type map.
   */
  public function __construct(
    protected MimeTypeMapInterface $map,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function guessMimeType($path): ?string {
    $file_parts = explode('.', basename($path));

    // Remove the first part: a full filename should not match an extension,
    // then iterate over the file parts, trying to find a match.
    // For 'my.awesome.image.jpeg', we try: 'awesome.image.jpeg', then
    // 'image.jpeg', then 'jpeg'.
    // We explicitly check for NULL because that indicates that the array is
    // empty.
    while (array_shift($file_parts) !== NULL) {
      $extension = strtolower(implode('.', $file_parts));
      if ($mimeType = $this->map->getMimeTypeForExtension($extension)) {
        return $mimeType;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isGuesserSupported(): bool {
    return TRUE;
  }

}
