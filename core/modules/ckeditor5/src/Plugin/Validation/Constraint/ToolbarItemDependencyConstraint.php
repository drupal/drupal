<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * A CKEditor 5 toolbar item.
 *
 * @Constraint(
 *   id = "CKEditor5ToolbarItemDependencyConstraint",
 *   label = @Translation("CKEditor 5 toolbar item dependency", context = "Validation"),
 * )
 *
 * @internal
 */
class ToolbarItemDependencyConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Depends on %toolbar_item, which is not enabled.';

  /**
   * The toolbar item that this validation constraint requires to be enabled.
   *
   * @var null|string
   */
  public $toolbarItem = NULL;

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return ['toolbarItem'];
  }

}
