<?php

namespace Drupal\Tests\field\Kernel\Number;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the new entity API for the number field type.
 *
 * @group field
 */
class NumberItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [];

  protected function setUp() {
    parent::setUp();

    // Create number field storages and fields for validation.
    foreach (['integer', 'float', 'decimal'] as $type) {
      FieldStorageConfig::create([
        'entity_type' => 'entity_test',
        'field_name' => 'field_' . $type,
        'type' => $type,
      ])->save();
      FieldConfig::create([
        'entity_type' => 'entity_test',
        'field_name' => 'field_' . $type,
        'bundle' => 'entity_test',
      ])->save();
    }
  }

  /**
   * Tests using entity fields of the number field type.
   */
  public function testNumberItem() {
    // Verify entity creation.
    $entity = EntityTest::create();
    $integer = rand(0, 10);
    $entity->field_integer = $integer;
    $float = 3.14;
    $entity->field_float = $float;
    $entity->field_decimal = '20-40';
    $violations = $entity->validate();
    $this->assertIdentical(1, count($violations), 'Wrong decimal value causes validation error');
    $decimal = '31.3';
    $entity->field_decimal = $decimal;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertTrue($entity->field_integer instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_integer[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_integer->value, $integer);
    $this->assertEqual($entity->field_integer[0]->value, $integer);
    $this->assertTrue($entity->field_float instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_float[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_float->value, $float);
    $this->assertEqual($entity->field_float[0]->value, $float);
    $this->assertTrue($entity->field_decimal instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->field_decimal[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->field_decimal->value, (float) $decimal);
    $this->assertEqual($entity->field_decimal[0]->value, (float) $decimal);

    // Verify changing the number value.
    $new_integer = rand(11, 20);
    $new_float = rand(1001, 2000) / 100;
    $new_decimal = '18.2';
    $entity->field_integer->value = $new_integer;
    $this->assertEqual($entity->field_integer->value, $new_integer);
    $entity->field_float->value = $new_float;
    $this->assertEqual($entity->field_float->value, $new_float);
    $entity->field_decimal->value = $new_decimal;
    $this->assertEqual($entity->field_decimal->value, (float) $new_decimal);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = EntityTest::load($id);
    $this->assertEqual($entity->field_integer->value, $new_integer);
    $this->assertEqual($entity->field_float->value, $new_float);
    $this->assertEqual($entity->field_decimal->value, (float) $new_decimal);

    // Test sample item generation.
    $entity = EntityTest::create();
    $entity->field_integer->generateSampleItems();
    $entity->field_float->generateSampleItems();
    $entity->field_decimal->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

  /**
   * Tests constraints on numeric item fields.
   *
   * @dataProvider dataNumberFieldSettingsProvider
   *
   * @param string $type
   *   The field type.
   * @param int|float $min
   *   The minimum field value.
   * @param int|float $max
   *   The maximum field value.
   * @param int|float $value
   *   The test value.
   * @param bool $expect_constraints
   *   If TRUE this data set will trigger a validation constraint.
   * @param string $expected_constraint_message
   *   The expected constraint violation message.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testConstraints($type, $min, $max, $value, $expect_constraints, $expected_constraint_message = '') {
    $field = FieldConfig::loadByName('entity_test', 'entity_test', 'field_' . $type);
    $field->setSetting('min', $min);
    $field->setSetting('max', $max);
    $field->save();

    $entity = EntityTest::create();
    $entity->{'field_' . $type} = $value;
    $violations = $entity->validate();
    $this->assertEquals($expect_constraints, $violations->count() > 0);
    if ($expect_constraints) {
      $this->assertEquals($expected_constraint_message, $violations->get(0)->getMessage());
    }

  }

  /**
   * Data provider for testConstraints.
   *
   * @return \Generator
   *   The test data.
   */
  public function dataNumberFieldSettingsProvider() {
    yield ['integer', NULL, NULL, -100, FALSE];
    yield ['integer', 0, NULL, -100, TRUE, '<em class="placeholder">field_integer</em>: the value may be no less than <em class="placeholder">0</em>.'];
    yield ['integer', 10, NULL, 100, FALSE];
    yield ['integer', 10, NULL, 5, TRUE, '<em class="placeholder">field_integer</em>: the value may be no less than <em class="placeholder">10</em>.'];
    yield ['integer', 10, 20, 25, TRUE, '<em class="placeholder">field_integer</em>: the value may be no greater than <em class="placeholder">20</em>.'];
    yield ['integer', 10, 20, 15, FALSE];

    yield ['float', NULL, NULL, -100, FALSE];
    yield ['float', 0.003, NULL, 0.0029, TRUE, '<em class="placeholder">field_float</em>: the value may be no less than <em class="placeholder">0.003</em>.'];
    yield ['float', 10.05, NULL, 13.4, FALSE];
    yield ['float', 10, NULL, 9.999, TRUE, '<em class="placeholder">field_float</em>: the value may be no less than <em class="placeholder">10</em>.'];
    yield ['float', 1, 2, 2.5, TRUE, '<em class="placeholder">field_float</em>: the value may be no greater than <em class="placeholder">2</em>.'];
    yield ['float', 1, 2, 1.5, FALSE];

    yield ['decimal', NULL, NULL, -100, FALSE];
    yield ['decimal', 0.001, NULL, -0.05, TRUE, '<em class="placeholder">field_decimal</em>: the value may be no less than <em class="placeholder">0.001</em>.'];
    yield ['decimal', 10.05, NULL, 13.4, FALSE];
    yield ['decimal', 10, NULL, 9.999, TRUE, '<em class="placeholder">field_decimal</em>: the value may be no less than <em class="placeholder">10</em>.'];
    yield ['decimal', 1, 2, 2.5, TRUE, '<em class="placeholder">field_decimal</em>: the value may be no greater than <em class="placeholder">2</em>.'];
    yield ['decimal', 1, 2, 1.5, FALSE];
  }

}
