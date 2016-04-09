<?php

namespace Drupal\condition_test\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;

/**
 * Provides a condition that requires two users.
 *
 * @Condition(
 *   id = "condition_test_dual_user",
 *   label = @Translation("Dual user"),
 *   context = {
 *     "user1" = @ContextDefinition("entity:user", label = @Translation("User 1")),
 *     "user2" = @ContextDefinition("entity:user", label = @Translation("User 2"))
 *   }
 * )
 */
class ConditionTestDualUser extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $user1 = $this->getContextValue('user1');
    $user2 = $this->getContextValue('user2');
    return $user1->id() === $user2->id();
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('This condition has two users.');
  }

}
