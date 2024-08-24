<?php

declare(strict_types=1);

namespace Drupal\block_test\Plugin\Condition;

use Drupal\Core\Condition\Attribute\Condition;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'baloney_spam' condition.
 */
#[Condition(
  id: "baloney_spam",
  label: new TranslatableMarkup("Baloney spam"),
)]
class BaloneySpam extends ConditionPluginBase {

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
    return 'Summary';
  }

}
