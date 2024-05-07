<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

/**
 * Exception thrown when a recipe has configuration that exists already.
 *
 * @internal
 *   This API is experimental.
 */
class RecipePreExistingConfigException extends \RuntimeException {

  /**
   * Constructs a RecipePreExistingConfigException.
   *
   * @param string $configName
   *   The configuration name that has missing dependencies.
   * @param string $message
   *   [optional] The Exception message to throw.
   * @param int $code
   *   [optional] The Exception code.
   * @param null|\Throwable $previous
   *   [optional] The previous throwable used for the exception chaining.
   */
  public function __construct(public readonly string $configName, string $message = "", int $code = 0, ?\Throwable $previous = NULL) {
    parent::__construct($message, $code, $previous);
  }

}
