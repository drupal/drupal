<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
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

  /**
   * When Source Editing is configured to allow self-XSS.
   *
   * @var string
   */
  public $message = 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: %dangerous_tag.';

}
