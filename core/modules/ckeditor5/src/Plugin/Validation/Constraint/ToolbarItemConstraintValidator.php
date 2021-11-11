<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Toolbar item constraint validator.
 *
 * @internal
 */
class ToolbarItemConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use PluginManagerDependentValidatorTrait;

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($toolbar_item, Constraint $constraint) {
    if (!$constraint instanceof ToolbarItemConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\ToolbarItem');
    }

    if ($toolbar_item === NULL) {
      return;
    }

    if (!static::isValidToolbarItem($toolbar_item)) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('%toolbar_item', $toolbar_item)
        ->setInvalidValue($toolbar_item)
        ->addViolation();
    }
  }

  /**
   * Validates the given toolbar item.
   *
   * @param string $toolbar_item
   *   A toolbar item as expected by CKEditor 5.
   *
   * @return bool
   *   Whether the given toolbar item is valid or not.
   */
  protected function isValidToolbarItem(string $toolbar_item): bool {
    // Special case: the toolbar group separator.
    // @see https://ckeditor.com/docs/ckeditor5/latest/features/toolbar/toolbar.html#separating-toolbar-items
    if ($toolbar_item === '|') {
      return TRUE;
    }

    // Special case: the breakpoint separator.
    // @see https://ckeditor.com/docs/ckeditor5/latest/features/toolbar/toolbar.html#explicit-wrapping-breakpoint
    if ($toolbar_item === '-') {
      return TRUE;
    }

    $available_toolbar_items = array_keys($this->pluginManager->getToolbarItems());
    return in_array($toolbar_item, $available_toolbar_items, TRUE);
  }

}
