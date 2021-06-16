<?php

namespace Drupal\entity_test\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem;

/**
 * Defines the 'field_method_invocation_order_test' entity field type.
 *
 * @FieldType(
 *   id = "auto_incrementing_test",
 *   label = @Translation("Auto incrementing test field item"),
 *   description = @Translation("An entity field designed to test the field method invocation order."),
 *   category = @Translation("Number"),
 *   no_ui = TRUE,
 * )
 */
class AutoIncrementingTestItem extends IntegerItem {

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    $this->value = static::getIncrementedFieldValue();
  }

  /**
   * Gets an incremented field value.
   *
   * @return int
   *   The incremented field value.
   */
  private static function getIncrementedFieldValue() {
    static $cache = 0;
    return ++$cache;
  }

}
