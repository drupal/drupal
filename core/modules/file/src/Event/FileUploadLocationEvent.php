<?php

namespace Drupal\file\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a class for a file upload event.
 */
class FileUploadLocationEvent extends Event {

  /**
   * Upload location.
   *
   * @var string
   */
  protected $uploadLocation = '';

  /**
   * Files uploaded.
   *
   * @var \Symfony\Component\HttpFoundation\File\UploadedFile[]
   */
  protected $filesUpload = [];

  /**
   * Form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * Managed file element.
   *
   * @var array
   */
  protected $element = [];

  /**
   * Constructs a new FileUploadLocationEvent.
   *
   * @param string|null $uploadLocation
   *   Existing upload location.
   * @param \Symfony\Component\HttpFoundation\File\UploadedFile|\Symfony\Component\HttpFoundation\File\UploadedFile[] $filesUpload
   *   Uploaded file or files depending if the field is multiple or not.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $element
   *   Managed file element.
   */
  public function __construct(?string $uploadLocation, $filesUpload, FormStateInterface $formState, array $element) {
    $this->uploadLocation = (array) $uploadLocation;
    $this->filesUpload = $filesUpload;
    $this->formState = $formState;
    $this->element = $element;
  }

  /**
   * Gets upload location.
   *
   * @return string
   *   Upload location.
   */
  public function getUploadLocation(): ?string {
    return $this->uploadLocation;
  }

  /**
   * Gets files uploaded.
   *
   * @return array
   *   Uploaded files.
   */
  public function getFilesUpload(): array {
    return $this->filesUpload;
  }

  /**
   * Gets form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   Form state.
   */
  public function getFormState(): FormStateInterface {
    return $this->formState;
  }

  /**
   * Gets managed file element.
   *
   * @return array
   *   Element.
   */
  public function getElement(): array {
    return $this->element;
  }

  /**
   * Sets new upload location.
   *
   * @param string $uploadLocation
   *   New value for upload location.
   *
   * @return $this
   */
  public function setUploadLocation(string $uploadLocation): FileUploadLocationEvent {
    $this->uploadLocation = $uploadLocation;
    return $this;
  }

}
