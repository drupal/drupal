<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal
 *   This API is experimental.
 */
final class RecipeFileException extends \RuntimeException {

  /**
   * Constructs a RecipeFileException object.
   *
   * @param string $path
   *   The path of the offending recipe file.
   * @param string $message
   *   (optional) The exception message.
   * @param \Symfony\Component\Validator\ConstraintViolationList|null $violations
   *   (optional) A list of validation constraint violations in the recipe file,
   *   if any.
   * @param int $code
   *   (optional) The exception code.
   * @param \Throwable|null $previous
   *   (optional) The previous exception, if any.
   */
  public function __construct(
    public readonly string $path,
    string $message = '',
    public readonly ?ConstraintViolationList $violations = NULL,
    int $code = 0,
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct($message, $code, $previous);
  }

  /**
   * Creates an instance of this exception from a set of validation errors.
   *
   * @param string $path
   *   The path of the offending recipe file.
   * @param \Symfony\Component\Validator\ConstraintViolationList $violations
   *   The list of validation constraint violations.
   *
   * @return static
   */
  public static function fromViolationList(string $path, ConstraintViolationList $violations): static {
    $lines = ["Validation errors were found in $path:"];

    foreach ($violations as $violation) {
      $lines[] = sprintf('- %s: %s', $violation->getPropertyPath(), $violation->getMessage());
    }
    return new static($path, implode("\n", $lines), $violations);
  }

}
