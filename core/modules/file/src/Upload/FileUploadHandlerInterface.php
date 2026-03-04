<?php

declare(strict_types=1);

namespace Drupal\file\Upload;

use Drupal\Core\File\FileExists;

/**
 * Handles validating and creating file entities from file uploads.
 */
interface FileUploadHandlerInterface {

  /**
   * Creates a file from an upload.
   *
   * @param \Drupal\file\Upload\UploadedFileInterface $uploadedFile
   *   The uploaded file object.
   * @param array $validators
   *   The validators to run against the uploaded file.
   * @param string $destination
   *   The destination directory.
   * @param \Drupal\Core\File\FileExists $fileExists
   *   The behavior when the destination file already exists.
   *
   * @return \Drupal\file\Upload\FileUploadResult
   *   The created file entity.
   *
   * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
   *    Thrown when a file upload error occurred and $throws is TRUE.
   * @throws \Drupal\Core\File\Exception\FileWriteException
   *    Thrown when there is an error moving the file and $throws is TRUE.
   * @throws \Drupal\Core\File\Exception\FileException
   *    Thrown when a file system error occurs and $throws is TRUE.
   * @throws \Drupal\file\Upload\FileValidationException
   *    Thrown when file validation fails and $throws is TRUE.
   * @throws \Drupal\Core\Lock\LockAcquiringException
   *   Thrown when a lock cannot be acquired.
   */
  public function handleFileUpload(UploadedFileInterface $uploadedFile, array $validators = [], string $destination = 'temporary://', FileExists $fileExists = FileExists::Replace): FileUploadResult;

}
