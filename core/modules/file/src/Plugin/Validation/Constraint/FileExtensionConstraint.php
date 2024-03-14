<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * File extension constraint.
 */
#[Constraint(
  id: 'FileExtension',
  label: new TranslatableMarkup('File Extension', [], ['context' => 'Validation']),
  type: 'file'
)]
class FileExtensionConstraint extends SymfonyConstraint {

  /**
   * The error message.
   *
   * @var string
   */
  public string $message = 'Only files with the following extensions are allowed: %files-allowed.';

  /**
   * The allowed file extensions.
   *
   * @var string
   */
  public string $extensions;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'extensions';
  }

}
