<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * A CKEditor 5 toolbar item.
 *
 * @Constraint(
 *   id = "CKEditor5ToolbarItem",
 *   label = @Translation("CKEditor 5 toolbar item", context = "Validation"),
 * )
 *
 * @see https://ckeditor.com/docs/ckeditor5/latest/features/toolbar/toolbar.html
 */
class ToolbarItemConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The provided toolbar item %toolbar_item is not valid.';

}
