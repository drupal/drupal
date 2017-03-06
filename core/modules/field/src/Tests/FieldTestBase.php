<?php

namespace Drupal\field\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Parent class for Field API tests.
 *
 * @deprecated Scheduled for removal in Drupal 9.0.0.
 *   Use \Drupal\Tests\field\Functional\FieldTestBase instead.
 */
abstract class FieldTestBase extends WebTestBase {

  /**
   * Generate random values for a field_test field.
   *
   * @param $cardinality
   *   Number of values to generate.
   * @return
   *   An array of random values, in the format expected for field values.
   */
  public function _generateTestFieldValues($cardinality) {
    $values = [];
    for ($i = 0; $i < $cardinality; $i++) {
      // field_test fields treat 0 as 'empty value'.
      $values[$i]['value'] = mt_rand(1, 127);
    }
    return $values;
  }

  /**
   * Assert that a field has the expected values in an entity.
   *
   * This function only checks a single column in the field values.
   *
   * @param EntityInterface $entity
   *   The entity to test.
   * @param $field_name
   *   The name of the field to test
   * @param $expected_values
   *   The array of expected values.
   * @param $langcode
   *   (Optional) The language code for the values. Defaults to
   *   \Drupal\Core\Language\LanguageInterface::LANGCODE_DEFAULT.
   * @param $column
   *   (Optional) The name of the column to check. Defaults to 'value'.
   */
  public function assertFieldValues(EntityInterface $entity, $field_name, $expected_values, $langcode = LanguageInterface::LANGCODE_DEFAULT, $column = 'value') {
    // Re-load the entity to make sure we have the latest changes.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($entity->getEntityTypeId());
    $storage->resetCache([$entity->id()]);
    $e = $storage->load($entity->id());

    $field = $values = $e->getTranslation($langcode)->$field_name;
    // Filter out empty values so that they don't mess with the assertions.
    $field->filterEmptyItems();
    $values = $field->getValue();
    $this->assertEqual(count($values), count($expected_values), 'Expected number of values were saved.');
    foreach ($expected_values as $key => $value) {
      $this->assertEqual($values[$key][$column], $value, format_string('Value @value was saved correctly.', ['@value' => $value]));
    }
  }

}
