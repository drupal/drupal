<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * File name length constraint.
 */
#[Constraint(
  id: 'FileNameLength',
  label: new TranslatableMarkup('File Name Length', [], ['context' => 'Validation']),
  type: 'file'
)]
class FileNameLengthConstraint extends SymfonyConstraint {

  /**
   * The maximum file name length.
   *
   * @var int
   */
  public int $maxLength = 240;

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?int $maxLength = NULL,
    public string $messageEmpty = "The file's name is empty. Enter a name for the file.",
    public string $messageTooLong = "The file's name exceeds the %maxLength characters limit. Rename the file and try again.",
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->maxLength = $maxLength ?? $this->maxLength;
  }

}
