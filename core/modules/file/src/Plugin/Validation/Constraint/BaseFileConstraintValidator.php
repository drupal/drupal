<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\file\FileInterface;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Provides a base class for file constraint validators.
 */
abstract class BaseFileConstraintValidator extends ConstraintValidator {

  /**
   * Checks the value is of type FileInterface.
   *
   * @param mixed $value
   *   The value to check.
   *
   * @return \Drupal\file\FileInterface
   *   The file.
   *
   * @throw Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown if the value is not a FileInterface.
   */
  protected function assertValueIsFile(mixed $value): FileInterface {
    if (!$value instanceof FileInterface) {
      throw new UnexpectedTypeException($value, FileInterface::class);
    }
    return $value;
  }

}
