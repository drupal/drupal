<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * CKEditor 5 element.
 */
#[Constraint(
  id: 'CKEditor5Element',
  label: new TranslatableMarkup('CKEditor 5 element', [], ['context' => 'Validation'])
)]
class CKEditor5ElementConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The following tag is not valid HTML: %provided_element.';

  /**
   * Violation message when a required attribute is missing.
   *
   * @var string
   */
  public $missingRequiredAttributeMessage = 'The following tag is missing the required attribute <code>@required_attribute_name</code>: <code>@provided_element</code>.';

  /**
   * Violation message when a required attribute does not allow enough values.
   *
   * @var string
   */
  public $requiredAttributeMinValuesMessage = 'The following tag does not have the minimum of @min_attribute_value_count allowed values for the required attribute <code>@required_attribute_name</code>: <code>@provided_element</code>.';

  /**
   * Validation constraint option to impose attributes to be specified.
   *
   * @var null|array
   */
  public $requiredAttributes = NULL;

}
