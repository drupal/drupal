<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Uniquely labeled list item constraint.
 *
 * @internal
 */
#[Constraint(
  id: 'UniqueLabelInList',
  label: new TranslatableMarkup('Unique label in list', [], ['context' => 'Validation'])
)]
class UniqueLabelInListConstraint extends SymfonyConstraint {

  /**
   * The key of the label that this validation constraint should check.
   *
   * @var null|string
   */
  public $labelKey = NULL;

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?string $labelKey = NULL,
    public $message = 'The label %label is not unique.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->labelKey = $labelKey ?? $this->labelKey;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['labelKey'];
  }

}
