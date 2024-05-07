<?php

namespace Drupal\config_test\Entity;

/**
 * Defines the ConfigQueryTest configuration entity used by the query test.
 *
 * @ConfigEntityType(
 *   id = "config_query_test",
 *   label = @Translation("Test configuration for query"),
 *   handlers = {
 *     "storage" = "Drupal\config_test\ConfigTestStorage",
 *     "list_builder" = "Drupal\Core\Config\Entity\ConfigEntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\config_test\ConfigTestForm"
 *     }
 *   },
 *   config_prefix = "query",
 *   config_export = {
 *     "id",
 *     "label",
 *     "array",
 *     "number",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   }
 * )
 *
 * @see \Drupal\system\Tests\Entity\ConfigEntityQueryTest
 */
class ConfigQueryTest extends ConfigTest {

  /**
   * A number used by the sort tests.
   *
   * @var int
   */
  public $number;

  /**
   * An array used by the wildcard tests.
   *
   * @var array
   */
  public $array = [];

  /**
   * {@inheritdoc}
   */
  public function concatProtectedProperty(string $value1, string $value2): static {
    // This method intentionally does not have the config action attribute to
    // ensure it is still discovered.
    return parent::concatProtectedProperty($value1, $value2);
  }

}
