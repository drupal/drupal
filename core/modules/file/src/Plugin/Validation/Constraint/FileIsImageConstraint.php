<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * File is image constraint.
 */
#[Constraint(
  id: 'FileIsImage',
  label: new TranslatableMarkup('File Is Image', [], ['context' => 'Validation']),
  type: 'file'
)]
class FileIsImageConstraint extends SymfonyConstraint {

  /**
   * The error message.
   *
   * @var string
   */
  public string $message = 'The image file is invalid or the image type is not allowed. Allowed types: %types';

}
