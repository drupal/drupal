<?php

declare(strict_types=1);

namespace Drupal\Core\File;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Helper class for getting the uploaded files for a given form element name.
 */
class UploadedFilesExtractor {

  public function __construct(
    protected readonly RequestStack $requestStack,
  ) {}

  /**
   * Extracts the uploaded files from the request.
   *
   * @param string $name
   *   The form element name.
   *
   * @return \Symfony\Component\HttpFoundation\File\UploadedFile[]
   *   The uploaded files.
   */
  public function extractUploadedFiles(string $name): array {
    $allFiles = $this->requestStack->getCurrentRequest()->files->get('files', []);
    if (empty($allFiles[$name])) {
      return [];
    }

    // Ensure we return an array.
    $uploadedFiles = $allFiles[$name];
    if (!is_array($uploadedFiles)) {
      $uploadedFiles = [$uploadedFiles];
    }
    return $uploadedFiles;
  }

}
