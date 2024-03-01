<?php

namespace Drupal\condition_test\Plugin\Condition;

use Drupal\Core\Condition\Attribute\Condition;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a condition that has a no existing context.
 */
#[Condition(
  id: "condition_test_no_existing_type",
  label: new TranslatableMarkup("No existing type"),
  context_definitions: [
    "no_existing_type" => new ContextDefinition(
      data_type: "no_existing_type",
      label: new TranslatableMarkup("No existing type"),
    ),
  ]
)]
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
