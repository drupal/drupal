<?php

/**
 * @file
 * Contains \Drupal\Core\Condition\ConditionInterface.
 */

namespace Drupal\Core\Condition;

use Drupal\Core\Executable\ExecutableInterface;

/**
 * An interface for condition plugins.
 *
 * @see \Drupal\Core\Executable\ExecutableInterface
 */
interface ConditionInterface extends ExecutableInterface {

  /**
   * Determines whether condition result will be negated.
   *
   * @return boolean
   *   Whether the condition result will be negated.
   */
  public function isNegated();

  /**
   * Evaluates the condition and returns TRUE or FALSE accordingly.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  public function evaluate();

}
