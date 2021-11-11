<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * CKEditor 5 element.
 *
 * @Constraint(
 *   id = "CKEditor5Element",
 *   label = @Translation("CKEditor 5 element", context = "Validation"),
 * )
 */
class CKEditor5ElementConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The following tag is not valid HTML: %provided_element.';

}
