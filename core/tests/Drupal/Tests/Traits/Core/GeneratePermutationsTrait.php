<?php

declare(strict_types=1);

namespace Drupal\Tests\Traits\Core;

/**
 * Adds ability to convert a list of parameters into a stack of permutations.
 */
trait GeneratePermutationsTrait {

  /**
   * Converts a list of possible parameters into a stack of permutations.
   *
   * Takes a list of parameters containing possible values, and converts all of
   * them into a list of items containing every possible permutation.
   *
   * Example:
   * @code
   * $parameters = [
   *   'one' => [0, 1],
   *   'two' => [2, 3],
   * ];
   * $permutations = $this->generatePermutations($parameters);
   * // Result:
   * $permutations == [
   *   ['one' => 0, 'two' => 2],
   *   ['one' => 1, 'two' => 2],
   *   ['one' => 0, 'two' => 3],
   *   ['one' => 1, 'two' => 3],
   * ]
   * @endcode
   *
   * @param array $parameters
   *   An associative array of parameters, keyed by parameter name, and whose
   *   values are arrays of parameter values.
   *
   * @return array[]
   *   A list of permutations, which is an array of arrays. Each inner array
   *   contains the full list of parameters that have been passed, but with a
   *   single value only.
   */
  public static function generatePermutations(array $parameters) {
    $all_permutations = [[]];
    foreach ($parameters as $parameter => $values) {
      $new_permutations = [];
      // Iterate over all values of the parameter.
      foreach ($values as $value) {
        // Iterate over all existing permutations.
        foreach ($all_permutations as $permutation) {
          // Add the new parameter value to existing permutations.
          $new_permutations[] = $permutation + [$parameter => $value];
        }
      }
      // Replace the old permutations with the new permutations.
      $all_permutations = $new_permutations;
    }
    return $all_permutations;
  }

}
