<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * A CKEditor 5 toolbar item.
 *
 * @see https://ckeditor.com/docs/ckeditor5/latest/features/toolbar/toolbar.html
 */
#[Constraint(
  id: 'CKEditor5ToolbarItem',
  label: new TranslatableMarkup('CKEditor 5 toolbar item', [], ['context' => 'Validation'])
)]
class ToolbarItemConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The provided toolbar item %toolbar_item is not valid.';

}
