<?php

namespace Drupal\Core\Database;

/**
 * A value object representing an expression with its arguments.
 */
class Expression {

  /**
   * Constructor.
   *
   * @param string $expression
   *   The expression text. Never include user input in the expression text;
   *   pass it through $arguments placeholders instead.
   * @param array<string,string|int|float|bool> $arguments
   *   (optional) The expression arguments, keyed by placeholder name.
   */
  public function __construct(
    public readonly string $expression,
    public readonly array $arguments = [],
  ) {
  }

}
