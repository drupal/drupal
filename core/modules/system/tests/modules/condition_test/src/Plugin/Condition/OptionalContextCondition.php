<?php

namespace Drupal\condition_test\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;

/**
 * Provides a condition with an optional node context.
 *
 * The context type entity:node is used since that would allow to also use this
 * for web tests with the node route context.
 *
 * @Condition(
 *   id = "condition_test_optional_context",
 *   label = @Translation("Optional context"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"), required = FALSE),
 *   }
 * )
 */
class OptionalContextCondition extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Grant access if no context value is given.
    return !$this->getContextValue('node');
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Context with optional context.');
  }

}
