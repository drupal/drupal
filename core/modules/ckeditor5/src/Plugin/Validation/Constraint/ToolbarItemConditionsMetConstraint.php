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

  /**
   * The violation message when the required image upload status is not set.
   *
   * @var string
   */
  public $imageUploadStatusRequiredMessage = 'The %toolbar_item toolbar item requires image uploads to be enabled.';

  /**
   * The violation message when a required filter is missing.
   *
   * @var string
   */
  public $filterRequiredMessage = 'The %toolbar_item toolbar item requires the %filter filter to be enabled.';

  /**
   * The violation message when 1 required plugin is missing.
   *
   * @var string
   */
  public $singleMissingRequiredPluginMessage = 'The %toolbar_item toolbar item requires the %plugin plugin to be enabled.';

  /**
   * The violation message when >1 required plugin is missing.
   *
   * @var string
   */
  public $multipleMissingRequiredPluginMessage = 'The %toolbar_item toolbar item requires the %plugins plugins to be enabled.';

}
