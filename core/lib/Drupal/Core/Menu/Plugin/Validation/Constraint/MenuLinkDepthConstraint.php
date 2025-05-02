<?php

declare(strict_types=1);

namespace Drupal\Core\Menu\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\RangeConstraint;

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
   * The initial level of menu items that are being exposed (zero-based).
   *
   * @var string|int
   */
  public string|int $baseLevel = 0;

}
