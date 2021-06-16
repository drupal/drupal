<?php

namespace Drupal\block_test\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;

/**
 * Provides a 'missing_schema' condition.
 *
 * @Condition(
 *   id = "missing_schema",
 *   label = @Translation("Missing schema"),
 * )
 */
class MissingSchema extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return 'Summary';
  }

}
