<?php

namespace Drupal\file\Upload;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Provides a bridge to Symfony UploadedFile.
 */
class FormUploadedFile implements UploadedFileInterface {

  /**
   * The wrapped uploaded file.
   *
   * @var \Symfony\Component\HttpFoundation\File\UploadedFile
   */
  protected $uploadedFile;

  /**
   * Creates a new FormUploadedFile.
   *
   * @param \Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile
   *   The wrapped Symfony uploaded file.
   */
  public function __construct(UploadedFile $uploadedFile) {
    $this->uploadedFile = $uploadedFile;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientOriginalName(): string {
    return $this->uploadedFile->getClientOriginalName();
  }

  /**
   * {@inheritdoc}
   */
  public function isValid(): bool {
    return $this->uploadedFile->isValid();
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage(): string {
    return $this->uploadedFile->getErrorMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function getError(): int {
    return $this->uploadedFile->getError();
  }

  /**
   * {@inheritdoc}
   */
  public function getSize(): int {
    return $this->uploadedFile->getSize();
  }

  /**
   * {@inheritdoc}
   */
  public function getRealPath() {
    return $this->uploadedFile->getRealPath();
  }

  /**
   * {@inheritdoc}
   */
  public function getPathname(): string {
    return $this->uploadedFile->getPathname();
  }

  /**
   * {@inheritdoc}
   */
  public function getFilename(): string {
    return $this->uploadedFile->getFilename();
  }

}
