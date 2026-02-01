<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * A (placed) CKEditor 5 toolbar item's conditions must be met.
 *
 * @internal
 */
#[Constraint(
  id: 'CKEditor5ToolbarItemConditionsMet',
  label: new TranslatableMarkup('CKEditor 5 toolbar item conditions must be met', [], ['context' => 'Validation'])
)]
class ToolbarItemConditionsMetConstraint extends SymfonyConstraint {

  public function __construct(
    mixed $options = NULL,
    public $imageUploadStatusRequiredMessage = 'The %toolbar_item toolbar item requires image uploads to be enabled.',
    public $filterRequiredMessage = 'The %toolbar_item toolbar item requires the %filter filter to be enabled.',
    public $singleMissingRequiredPluginMessage = 'The %toolbar_item toolbar item requires the %plugin plugin to be enabled.',
    public $multipleMissingRequiredPluginMessage = 'The %toolbar_item toolbar item requires the %plugins plugins to be enabled.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
