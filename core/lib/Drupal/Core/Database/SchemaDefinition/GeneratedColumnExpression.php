<?php

declare(strict_types=1);

namespace Drupal\Core\Database\SchemaDefinition;

use Drupal\Core\Database\Expression;

/**
 * A value object representing an expression for a generated column.
 */
final class GeneratedColumnExpression extends Expression {

  /**
   * Constructor.
   *
   * @param string $expression
   *   The expression text.
   */
  public function __construct(
    string $expression,
  ) {
    parent::__construct($expression);
  }

}
