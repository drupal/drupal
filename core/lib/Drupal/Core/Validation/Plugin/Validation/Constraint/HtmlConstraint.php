<?php

declare(strict_types=1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Valid HTML constraint.
 *
 * Determines if a string is valid HTML5.
 */
#[Constraint(
  id: 'Html',
  label: new TranslatableMarkup('Valid HTML', [], ['context' => 'Validation'])
)]
class HtmlConstraint extends SymfonyConstraint {

  /**
   * Constructs an HtmlConstraint object.
   *
   * @param string $mode
   *   The html parser mode, valid values are 'fragment' or 'document'.
   * @param array|null $groups
   *   The validation groups.
   * @param mixed $payload
   *   The payload.
   *
   * @throws \Symfony\Component\Validator\Exception\InvalidArgumentException
   *   Thrown when the mode is not 'fragment' or 'document'.
   */
  public function __construct(
    public readonly string $mode,
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    if (!in_array($mode, ['fragment', 'document'], TRUE)) {
      throw new InvalidArgumentException('Invalid HTML parsing mode. the `mode` argument must be "fragment" or "document".');
    }
    parent::__construct(groups: $groups, payload: $payload);
  }

}
