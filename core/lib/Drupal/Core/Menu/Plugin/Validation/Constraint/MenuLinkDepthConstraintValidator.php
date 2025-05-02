<?php

declare(strict_types=1);

namespace Drupal\Core\Menu\Plugin\Validation\Constraint;

use Drupal\Core\Config\Schema\TypeResolver;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\RangeConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\RuntimeException;

/**
 * Validates the MenuLinkDepthConstraint constraint.
 */
class MenuLinkDepthConstraintValidator extends RangeConstraintValidator implements ContainerInjectionInterface {

  public function __construct(
    protected readonly MenuLinkTreeInterface $menuLinkTree,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    assert($constraint instanceof MenuLinkDepthConstraint);

    // The depth can never exceed the maximum depth of a menu tree.
    $constraint->max = $this->menuLinkTree->maxDepth();

    $base_level = $constraint->baseLevel;
    // The base level might be a dynamic name that needs to be resolved.
    if (is_string($base_level)) {
      $base_level = TypeResolver::resolveDynamicTypeName($base_level, $this->context->getObject());
    }
    if (!is_numeric($base_level)) {
      throw new RuntimeException('The `base` option must be a number, or an expression that resolves to one.');
    }
    // Clamp $base_level (which is zero-based) to at least 0 and no more than
    // the maximum depth of a menu tree.
    $base_level = max(0, $base_level);
    $base_level = min($base_level, $constraint->max);

    $constraint->max -= $base_level;
    // We're validating a depth -- i.e., the number of levels in the menu tree
    // that should be visible. 0 effectively means "show only one level", but
    // it's not a valid value: we can't show 0 levels of the tree, after all (
    // the minimum number of levels we *could* show is 1). The discrepancy is
    // due to $base_level being zero-based, so adjust for that.
    if ($constraint->max === 0) {
      $constraint->max = 1;
    }

    parent::validate($value, $constraint);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get(MenuLinkTreeInterface::class),
    );
  }

}
