<?php

namespace Drupal\Core\Condition;

use Drupal\Component\Plugin\Exception\ContextException;

/**
 * Resolves a set of conditions.
 */
trait ConditionAccessResolverTrait {

  /**
   * Resolves the given conditions based on the condition logic ('and'/'or').
   *
   * @param \Drupal\Core\Condition\ConditionInterface[] $conditions
   *   A set of conditions.
   * @param string $condition_logic
   *   The logic used to compute access, either 'and' or 'or'.
   *
   * @return bool
   *   Whether these conditions grant or deny access.
   */
  protected function resolveConditions($conditions, $condition_logic) {
    foreach ($conditions as $condition) {
      try {
        $pass = $condition->execute();
      }
      catch (ContextException $e) {
        // If a condition is missing context, consider that a fail.
        $pass = FALSE;
      }

      // If a condition fails and all conditions were needed, deny access.
      if (!$pass && $condition_logic == 'and') {
        return FALSE;
      }
      // If a condition passes and only one condition was needed, grant access.
      elseif ($pass && $condition_logic == 'or') {
        return TRUE;
      }
    }

    // Return TRUE if logic was 'and', meaning all rules passed.
    // Return FALSE if logic was 'or', meaning no rule passed.
    return $condition_logic == 'and';
  }

}
