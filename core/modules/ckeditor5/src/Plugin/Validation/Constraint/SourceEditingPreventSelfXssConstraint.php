<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * For disallowing Source Editing configuration that allows self-XSS.
 *
 * @Constraint(
 *   id = "SourceEditingPreventSelfXssConstraint",
 *   label = @Translation("Source Editing should never allow self-XSS.", context = "Validation"),
 * )
 *
 * @internal
 */
class SourceEditingPreventSelfXssConstraint extends Constraint {

  /**
   * When Source Editing is configured to allow self-XSS.
   *
   * @var string
   */
  public $message = 'The following tag in the Source Editing "Manually editable HTML tags" field is a security risk: %dangerous_tag.';

}
