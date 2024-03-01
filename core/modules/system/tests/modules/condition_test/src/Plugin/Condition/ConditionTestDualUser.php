<?php

namespace Drupal\condition_test\Plugin\Condition;

use Drupal\Core\Condition\Attribute\Condition;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a condition that requires two users.
 */
#[Condition(
  id: "condition_test_dual_user",
  label: new TranslatableMarkup("Dual user"),
  context_definitions: [
    "user1" => new EntityContextDefinition(
      data_type: "entity:user",
      label: new TranslatableMarkup("User 1"),
    ),
    "user2" => new EntityContextDefinition(
      data_type: "entity:user",
      label: new TranslatableMarkup("User 2"),
    ),
  ]
)]
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
