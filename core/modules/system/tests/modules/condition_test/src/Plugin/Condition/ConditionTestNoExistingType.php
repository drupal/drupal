<?php

namespace Drupal\condition_test\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;

/**
 * Provides a condition that has a no existing context.
 *
 * @Condition(
 *   id = "condition_test_no_existing_type",
 *   label = @Translation("No existing type"),
 *   context = {
 *     "no_existing_type" = @ContextDefinition("no_existing_type", label = @Translation("No existing type")),
 *   }
 * )
 */
class ConditionTestNoExistingType extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Condition that requires a non-existent context.');
  }

}
