<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Field\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Field\FieldItemList;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Field\Entity\BaseFieldOverride
 * @group Field
 */
class BaseFieldOverrideTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('base_field_override');
  }

  /**
   * @covers ::getClass
   *
   * @dataProvider getClassTestCases
   */
  public function testGetClass($field_type, $base_field_class, $expected_override_class): void {
    $base_field = BaseFieldDefinition::create($field_type)
      ->setName('Test Field')
      ->setTargetEntityTypeId('entity_test');
    if ($base_field_class) {
      $base_field->setClass($base_field_class);
    }
    $override = BaseFieldOverride::createFromBaseFieldDefinition($base_field, 'test_bundle');
    $this->assertEquals($expected_override_class, ltrim($override->getClass(), '\\'));
  }

  /**
   * Test cases for ::testGetClass.
   */
  public static function getClassTestCases() {
    return [
      'String (default class)' => [
        'string',
        FALSE,
        FieldItemList::class,
      ],
      'String (overridden class)' => [
        'string',
        static::class,
        static::class,
      ],
    ];
  }

  /**
   * Tests the default value callback.
   */
  public function testDefaultValueCallback(): void {
    $base_field = BaseFieldDefinition::create('entity_reference')
      ->setName('Test Field')
      ->setTargetEntityTypeId('entity_test')
      ->setDefaultValueCallback(static::class . '::defaultValueCallbackPrimitive');
    $base_field_override = BaseFieldOverride::createFromBaseFieldDefinition($base_field, 'test_bundle');
    $entity = EntityTest::create([]);

    $this->assertEquals([['target_id' => 99]], $base_field->getDefaultValue($entity));
    $this->assertEquals([['target_id' => 99]], $base_field_override->getDefaultValue($entity));
  }

  /**
   * Tests that some properties are inherited from the BaseFieldDefinition.
   *
   * @covers ::isReadOnly
   * @covers ::isComputed
   * @covers ::isInternal
   * @covers ::getUniqueIdentifier
   */
  public function testInheritedProperties(): void {
    $base_field = BaseFieldDefinition::create('string')
      ->setName('Test Field')
      ->setTargetEntityTypeId('entity_test')
      ->setReadOnly(TRUE)
      /** Ensure that the internal property is inherited from the base field and not the parent class. @see FieldConfigBase::isInternal */
      ->setInternal(TRUE)
      ->setComputed(FALSE);

    // Getters of the properties to check.
    $methods = [
      'getUniqueIdentifier',
      'getClass',
      'isComputed',
      'isReadOnly',
      'isInternal',
    ];

    $override = BaseFieldOverride::createFromBaseFieldDefinition($base_field, 'test_bundle');
    foreach ($methods as $method) {
      $this->assertEquals($base_field->$method(), $override->$method());
    }
  }

  /**
   * A default value callback which returns a primitive value.
   *
   * @return int
   *   A primitive default value.
   */
  public static function defaultValueCallbackPrimitive() {
    return 99;
  }

}
