<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * File extension secure constraint.
 */
#[Constraint(
  id: 'FileExtensionSecure',
  label: new TranslatableMarkup('File Extension Secure', [], ['context' => 'Validation']),
  type: 'file'
)]
class FileExtensionSecureConstraint extends SymfonyConstraint {

  /**
   * The error message.
   *
   * @var string
   */
  public string $message = 'For security reasons, your upload has been rejected.';

}
