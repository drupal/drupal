<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Defines an encoding constraint for files.
 */
#[Constraint(
  id: 'FileEncoding',
  label: new TranslatableMarkup('File encoding', [], ['context' => 'Validation'])
)]
class FileEncodingConstraint extends SymfonyConstraint {

  /**
   * The error message.
   *
   * @var string
   */
  public string $message = "The file is encoded with %detected. It must be encoded with %encoding";

  /**
   * The allowed file encodings.
   *
   * @var array
   */
  public array $encodings;

}
