<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
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
   * Validation constraint option to impose attributes to be specified.
   *
   * @var null|array
   */
  public $requiredAttributes = NULL;

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    ?array $requiredAttributes = NULL,
    public $message = 'The following tag is not valid HTML: %provided_element.',
    public $missingRequiredAttributeMessage = 'The following tag is missing the required attribute <code>@required_attribute_name</code>: <code>@provided_element</code>.',
    public $requiredAttributeMinValuesMessage = 'The following tag does not have the minimum of @min_attribute_value_count allowed values for the required attribute <code>@required_attribute_name</code>: <code>@provided_element</code>.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->requiredAttributes = $requiredAttributes ?? $this->requiredAttributes;
  }

}
