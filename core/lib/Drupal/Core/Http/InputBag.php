<?php

namespace Drupal\Core\Http;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Forward compatibility class for Symfony 5.
 *
 * @internal only used as a bridge from Symfony 4 to Symfony 5, will be removed
 *   in drupal:10.0.0.
 */
final class InputBag extends ParameterBag {

  /**
   * Returns the parameters.
   *
   * @param string|null $key
   *   The name of the parameter to return or null to get them all.
   *
   * @return array
   *   An array of parameters.
   */
  public function all(string $key = NULL): array {
    if ($key === NULL) {
      return $this->parameters;
    }

    $value = $this->parameters[$key] ?? [];
    if (!is_array($value)) {
      throw new \UnexpectedValueException(sprintf('Unexpected value for parameter "%s": expecting "array", got "%s".', $key, get_debug_type($value)));
    }

    return $value;
  }

}
