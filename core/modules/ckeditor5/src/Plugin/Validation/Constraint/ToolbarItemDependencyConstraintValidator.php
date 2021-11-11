<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Toolbar item dependency constraint validator.
 *
 * @internal
 */
class ToolbarItemDependencyConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use PluginManagerDependentValidatorTrait;

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($toolbar_item, Constraint $constraint) {
    if (!$constraint instanceof ToolbarItemDependencyConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\ToolbarItemDependency');
    }

    $toolbar_items = $this->context->getRoot()->get('settings.toolbar.items')->toArray();
    if (!in_array($constraint->toolbarItem, $toolbar_items, TRUE)) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('%toolbar_item', $constraint->toolbarItem)
        ->addViolation();
    }
  }

}
