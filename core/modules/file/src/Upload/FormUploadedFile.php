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
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\file\Validation\UploadedFileValidatorInterface::validate()
   *   instead.
   *
   * @see https://www.drupal.org/node/3375456
   */
  public function isValid(): bool {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\file\Validation\UploadedFileValidatorInterface::validate() instead. See https://www.drupal.org/node/3375456', E_USER_DEPRECATED);
    return $this->uploadedFile->isValid();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\file\Validation\UploadedFileValidatorInterface::validate()
   *   instead.
   *
   * @see https://www.drupal.org/node/3375456
   */
  public function getErrorMessage(): string {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\file\Validation\UploadedFileValidatorInterface::validate() instead. See https://www.drupal.org/node/3375456', E_USER_DEPRECATED);
    return $this->uploadedFile->getErrorMessage();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   \Drupal\file\Validation\UploadedFileValidatorInterface::validate()
   *   instead.
   *
   * @see https://www.drupal.org/node/3375456
   */
  public function getError(): int {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\file\Validation\UploadedFileValidatorInterface::validate() instead. See https://www.drupal.org/node/3375456', E_USER_DEPRECATED);
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
