<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
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
   * The allowed file extensions.
   *
   * @var string
   */
  public string $extensions;

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?string $extensions = NULL,
    public string $message = 'Only files with the following extensions are allowed: %files-allowed.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->extensions = $extensions ?? $this->extensions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'extensions';
  }

}
