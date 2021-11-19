<?php

namespace Drupal\file\Upload;

use Drupal\file\FileInterface;

/**
 * Value object for a file upload result.
 */
class FileUploadResult {

  /**
   * If the filename was renamed for security reasons.
   *
   * @var bool
   */
  protected $securityRename = FALSE;

  /**
   * The sanitized filename.
   *
   * @var string
   */
  protected $sanitizedFilename;

  /**
   * The original filename.
   *
   * @var string
   */
  protected $originalFilename;

  /**
   * The File entity.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * Flags the result as having had a security rename.
   *
   * @return $this
   */
  public function setSecurityRename(): FileUploadResult {
    $this->securityRename = TRUE;
    return $this;
  }

  /**
   * Sets the sanitized filename.
   *
   * @param string $sanitizedFilename
   *   The sanitized filename.
   *
   * @return $this
   */
  public function setSanitizedFilename(string $sanitizedFilename): FileUploadResult {
    $this->sanitizedFilename = $sanitizedFilename;
    return $this;
  }

  /**
   * Gets the original filename.
   *
   * @return string
   */
  public function getOriginalFilename(): string {
    return $this->originalFilename;
  }

  /**
   * Sets the original filename.
   *
   * @param string $originalFilename
   *   The original filename.
   *
   * @return $this
   */
  public function setOriginalFilename(string $originalFilename): FileUploadResult {
    $this->originalFilename = $originalFilename;
    return $this;
  }

  /**
   * Sets the File entity.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   *
   * @return $this
   */
  public function setFile(FileInterface $file): FileUploadResult {
    $this->file = $file;
    return $this;
  }

  /**
   * Returns if there was a security rename.
   *
   * @return bool
   */
  public function isSecurityRename(): bool {
    return $this->securityRename;
  }

  /**
   * Returns if there was a file rename.
   *
   * @return bool
   */
  public function isRenamed(): bool {
    return $this->originalFilename !== $this->sanitizedFilename;
  }

  /**
   * Gets the sanitized filename.
   *
   * @return string
   */
  public function getSanitizedFilename(): string {
    return $this->sanitizedFilename;
  }

  /**
   * Gets the File entity.
   *
   * @return \Drupal\file\FileInterface
   */
  public function getFile(): FileInterface {
    return $this->file;
  }

}
