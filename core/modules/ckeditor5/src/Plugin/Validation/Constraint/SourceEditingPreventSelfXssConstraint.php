<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * For disallowing Source Editing configuration that allows self-XSS.
 *
 * @internal
 */
#[Constraint(
  id: 'SourceEditingPreventSelfXssConstraint',
  label: new TranslatableMarkup('Source Editing should never allow self-XSS.', [], ['context' => 'Validation'])
)]
class SourceEditingPreventSelfXssConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $message = 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: %dangerous_tag.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
