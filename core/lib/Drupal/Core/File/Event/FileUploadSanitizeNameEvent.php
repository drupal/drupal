<?php

namespace Drupal\Core\File\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * An event during file upload that lets subscribers sanitize the filename.
 *
 * @see \Drupal\file\Upload\FileUploadHandlerInterface::handleFileUpload()
 * @see \Drupal\file\Plugin\rest\resource\FileUploadResource::prepareFilename()
 * @see \Drupal\system\EventSubscriber\SecurityFileUploadEventSubscriber::sanitizeName()
 */
class FileUploadSanitizeNameEvent extends Event {

  /**
   * The name of the file being uploaded.
   *
   * @var string
   */
  protected $filename = '';

  /**
   * A list of allowed extensions.
   *
   * @var string[]
   */
  protected $allowedExtensions = [];

  /**
   * Indicates the filename has changed for security reasons.
   *
   * @var bool
   */
  protected $isSecurityRename = FALSE;

  /**
   * Constructs a file upload sanitize name event object.
   *
   * @param string $filename
   *   The full filename (with extension, but not directory) being uploaded.
   * @param string $allowed_extensions
   *   A list of allowed extensions. If empty all extensions are allowed.
   */
  public function __construct(string $filename, string $allowed_extensions) {
    $this->setFilename($filename);
    if ($allowed_extensions !== '') {
      $this->allowedExtensions = array_unique(explode(' ', trim(strtolower($allowed_extensions))));
    }
  }

  /**
   * Gets the filename.
   *
   * @return string
   *   The filename.
   */
  public function getFilename(): string {
    return $this->filename;
  }

  /**
   * Sets the filename.
   *
   * @param string $filename
   *   The filename to use for the uploaded file.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown when $filename contains path information.
   */
  public function setFilename(string $filename): self {
    if (dirname($filename) !== '.') {
      throw new \InvalidArgumentException(sprintf('$filename must be a filename with no path information, "%s" provided', $filename));
    }
    $this->filename = $filename;
    return $this;
  }

  /**
   * Gets the list of allowed extensions.
   *
   * @return string[]
   *   The list of allowed extensions.
   */
  public function getAllowedExtensions(): array {
    return $this->allowedExtensions;
  }

  /**
   * Sets the security rename flag.
   *
   * @return $this
   */
  public function setSecurityRename(): self {
    $this->isSecurityRename = TRUE;
    return $this;
  }

  /**
   * Gets the security rename flag.
   *
   * @return bool
   *   TRUE if there is a rename for security reasons, otherwise FALSE.
   */
  public function isSecurityRename(): bool {
    return $this->isSecurityRename;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \RuntimeException
   *   Thrown whenever this method is called. This event should always be fully
   *   processed so that SecurityFileUploadEventSubscriber::sanitizeName()
   *   gets a chance to run.
   *
   * @see \Drupal\system\EventSubscriber\SecurityFileUploadEventSubscriber
   */
  public function stopPropagation(): void {
    throw new \RuntimeException('Propagation cannot be stopped for the FileUploadSanitizeNameEvent');
  }

}
