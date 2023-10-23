<?php

declare(strict_types=1);

namespace Drupal\file\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * A constraint for UploadedFile objects.
 */
class UploadedFileConstraint extends Constraint {

  /**
   * The upload max size. Defaults to checking the environment.
   *
   * @var int|null
   */
  public ?int $maxSize;

  /**
   * The upload ini size error message.
   *
   * @var string
   */
  public string $uploadIniSizeErrorMessage = 'The file %file could not be saved because it exceeds %maxsize, the maximum allowed size for uploads.';

  /**
   * The upload form size error message.
   *
   * @var string
   */
  public string $uploadFormSizeErrorMessage = 'The file %file could not be saved because it exceeds %maxsize, the maximum allowed size for uploads.';

  /**
   * The upload partial error message.
   *
   * @var string
   */
  public string $uploadPartialErrorMessage = 'The file %file could not be saved because the upload did not complete.';

  /**
   * The upload no file error message.
   *
   * @var string
   */
  public string $uploadNoFileErrorMessage = 'The file %file could not be saved because the upload did not complete.';

  /**
   * The generic file upload error message.
   *
   * @var string
   */
  public string $uploadErrorMessage = 'The file %file could not be saved. An unknown error has occurred.';

}
