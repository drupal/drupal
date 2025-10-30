<?php

declare(strict_types=1);

namespace Drupal\Core\Menu\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\RangeConstraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;

/**
 * Validates the link depth of a menu tree.
 */
#[Constraint(
  id: 'MenuLinkDepth',
  label: new TranslatableMarkup('Menu link depth', options: ['context' => 'Validation']),
  type: ['integer'],
)]
class MenuLinkDepthConstraint extends RangeConstraint {

  /**
   * @param string|int $baseLevel
   *   The initial level of menu items that are being exposed (zero-based).
   * @param array<string, mixed> $args
   *   Additional arguments to pass to parent constructor.
   */
  #[HasNamedArguments]
  public function __construct(public readonly string|int $baseLevel = 0, ...$args) {
    parent::__construct(...$args);
  }

}
