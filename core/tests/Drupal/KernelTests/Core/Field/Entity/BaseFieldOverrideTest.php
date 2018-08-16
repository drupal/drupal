<?php

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
  public static $modules = [
    'system',
    'user',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('base_field_override');
  }

  /**
   * @covers ::getClass
   *
   * @dataProvider getClassTestCases
   */
  public function testGetClass($field_type, $base_field_class, $expected_override_class) {
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
  public function getClassTestCases() {
    return [
      'String (default class)' => [
        'string',
        FALSE,
        FieldItemList::class,
      ],
      'String (overriden class)' => [
        'string',
        static::class,
        static::class,
      ],
    ];
  }

  /**
   * Test the default value callback.
   */
  public function testDefaultValueCallback() {
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
   * A default value callback which returns a primitive value.
   *
   * @return int
   *   A primitive default value.
   */
  public static function defaultValueCallbackPrimitive() {
    return 99;
  }

}
