<?php

declare(strict_types=1);

namespace Drupal\file\Validation\Constraint;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * A constraint for UploadedFile objects.
 */
class UploadedFileConstraint extends Constraint {

  /**
   * The upload max size. Defaults to checking the environment.
   */
  public ?int $maxSize = NULL;

  /**
   * The upload ini size error message.
   */
  public string $uploadIniSizeErrorMessage = 'The file %file could not be saved because it exceeds %maxsize, the maximum allowed size for uploads.';

  /**
   * The upload form size error message.
   */
  public string $uploadFormSizeErrorMessage = 'The file %file could not be saved because it exceeds %maxsize, the maximum allowed size for uploads.';

  /**
   * The upload partial error message.
   */
  public string $uploadPartialErrorMessage = 'The file %file could not be saved because the upload did not complete.';

  /**
   * The upload no file error message.
   */
  public string $uploadNoFileErrorMessage = 'The file %file could not be saved because the upload did not complete.';

  /**
   * The generic file upload error message.
   */
  public string $uploadErrorMessage = 'The file %file could not be saved. An unknown error has occurred.';

  #[HasNamedArguments]
  public function __construct(
    ?array $options = NULL,
    ?int $maxSize = NULL,
    ?string $uploadIniSizeErrorMessage = NULL,
    ?string $uploadFormSizeErrorMessage = NULL,
    ?string $uploadPartialErrorMessage = NULL,
    ?string $uploadNoFileErrorMessage = NULL,
    ?string $uploadErrorMessage = NULL,
    mixed ...$args,
  ) {
    if (is_array($options)) {
      @trigger_error(sprintf('Passing an array of options to configure the "%s" constraint is deprecated in drupal:11.4.0 and is removed in drupal:12.0.0. Use named arguments instead. See https://www.drupal.org/node/3554746', static::class), E_USER_DEPRECATED);
    }

    parent::__construct($options, ...$args);

    $this->maxSize = $maxSize ?? $this->maxSize;
    $this->uploadIniSizeErrorMessage = $uploadIniSizeErrorMessage ?? $this->uploadIniSizeErrorMessage;
    $this->uploadFormSizeErrorMessage = $uploadFormSizeErrorMessage ?? $this->uploadFormSizeErrorMessage;
    $this->uploadPartialErrorMessage = $uploadPartialErrorMessage ?? $this->uploadPartialErrorMessage;
    $this->uploadNoFileErrorMessage = $uploadNoFileErrorMessage ?? $this->uploadNoFileErrorMessage;
    $this->uploadErrorMessage = $uploadErrorMessage ?? $this->uploadErrorMessage;
  }

}
