<?php

namespace Drupal\block_test\Plugin\Condition;

use Drupal\Core\Condition\Attribute\Condition;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'missing_schema' condition.
 */
#[Condition(
  id: "missing_schema",
  label: new TranslatableMarkup("Missing schema"),
)]
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
