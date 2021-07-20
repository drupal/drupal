<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for BaseFieldDefinition.
 *
 * @group Entity
 * @coversDefaultClass \Drupal\Core\Field\BaseFieldDefinition
 */
class BaseFieldDefinitionTest extends UnitTestCase {

  /**
   * A dummy field type name.
   *
   * @var string
   */
  protected $fieldType;

  /**
   * A dummy field type definition.
   *
   * @var string
   */
  protected $fieldTypeDefinition;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Mock the field type manager and place it in the container.
    $field_type_manager = $this->createMock('Drupal\Core\Field\FieldTypePluginManagerInterface');

    $this->fieldType = $this->randomMachineName();
    $this->fieldTypeDefinition = [
      'id' => $this->fieldType,
      'storage_settings' => [
        'some_setting' => 'value 1',
      ],
      'field_settings' => [
        'some_instance_setting' => 'value 2',
      ],
    ];

    $field_type_manager->expects($this->any())
      ->method('getDefinitions')
      ->willReturn([$this->fieldType => $this->fieldTypeDefinition]);
    $field_type_manager->expects($this->any())
      ->method('getDefinition')
      ->with($this->fieldType)
      ->willReturn($this->fieldTypeDefinition);
    $field_type_manager->expects($this->any())
      ->method('getDefaultStorageSettings')
      ->with($this->fieldType)
      ->willReturn($this->fieldTypeDefinition['storage_settings']);
    $field_type_manager->expects($this->any())
      ->method('getDefaultFieldSettings')
      ->with($this->fieldType)
      ->willReturn($this->fieldTypeDefinition['field_settings']);

    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $field_type_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests field name methods.
   *
   * @covers ::getName
   */
  public function testFieldName() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $field_name = $this->randomMachineName();
    $definition->setName($field_name);
    $this->assertEquals($field_name, $definition->getName());
  }

  /**
   * Tests field label methods.
   *
   * @covers ::getLabel
   */
  public function testFieldLabel() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $label = $this->randomMachineName();
    $definition->setLabel($label);
    $this->assertEquals($label, $definition->getLabel());
  }

  /**
   * Tests field description methods.
   *
   * @covers ::getDescription
   */
  public function testFieldDescription() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $description = $this->randomMachineName();
    $definition->setDescription($description);
    $this->assertEquals($description, $definition->getDescription());
  }

  /**
   * Tests field type methods.
   *
   * @covers ::getType
   */
  public function testFieldType() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $this->assertEquals($this->fieldType, $definition->getType());
  }

  /**
   * Tests field settings methods.
   *
   * @covers ::getSetting
   * @covers ::setSetting
   * @covers ::getSettings
   */
  public function testFieldSettings() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $setting = $this->randomMachineName();
    $value = $this->randomMachineName();
    $definition->setSetting($setting, $value);
    $this->assertEquals($value, $definition->getSetting($setting));
    $default_settings = $this->fieldTypeDefinition['storage_settings'] + $this->fieldTypeDefinition['field_settings'];
    $this->assertEquals([$setting => $value] + $default_settings, $definition->getSettings());
  }

  /**
   * Tests the initialization of default field settings.
   *
   * @covers ::getSetting
   * @covers ::setSetting
   * @covers ::getSettings
   */
  public function testDefaultFieldSettings() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $expected_settings = $this->fieldTypeDefinition['storage_settings'] + $this->fieldTypeDefinition['field_settings'];
    $this->assertEquals($expected_settings, $definition->getSettings());
    foreach ($expected_settings as $setting => $value) {
      $this->assertEquals($value, $definition->getSetting($setting));
    }
  }

  /**
   * Tests field default value.
   *
   * @covers ::getDefaultValue
   * @covers ::setDefaultValue
   */
  public function testFieldDefaultValue() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $default_value = [
      'value' => $this->randomMachineName(),
    ];
    $expected_default_value = [$default_value];
    $definition->setDefaultValue($default_value);
    $entity = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityBase')
      ->disableOriginalConstructor()
      ->getMock();
    // Set the field item list class to be used to avoid requiring the typed
    // data manager to retrieve it.
    $definition->setClass('Drupal\Core\Field\FieldItemList');
    $definition->setItemDefinition(DataDefinition::createFromDataType('string')->setClass(FieldItemBase::class));
    $this->assertEquals($expected_default_value, $definition->getDefaultValue($entity));

    $data_definition = $this->getMockBuilder('Drupal\Core\TypedData\DataDefinition')
      ->disableOriginalConstructor()
      ->getMock();
    $data_definition->expects($this->any())
      ->method('getClass')
      ->willReturn('Drupal\Core\Field\FieldItemBase');
    $definition->setItemDefinition($data_definition);

    // Set default value only with a literal.
    $definition->setDefaultValue($default_value['value']);
    $this->assertEquals($expected_default_value, $definition->getDefaultValue($entity));

    // Set default value with an indexed array.
    $definition->setDefaultValue($expected_default_value);
    $this->assertEquals($expected_default_value, $definition->getDefaultValue($entity));

    // Set default value with an empty array.
    $definition->setDefaultValue([]);
    $this->assertEquals([], $definition->getDefaultValue($entity));

    // Set default value with NULL.
    $definition->setDefaultValue(NULL);
    $this->assertEquals([], $definition->getDefaultValue($entity));
  }

  /**
   * Tests field initial value.
   *
   * @covers ::getInitialValue
   * @covers ::setInitialValue
   */
  public function testFieldInitialValue() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $definition->setItemDefinition(DataDefinition::createFromDataType('string')->setClass(FieldItemBase::class));
    $default_value = [
      'value' => $this->randomMachineName(),
    ];
    $expected_default_value = [$default_value];
    $definition->setInitialValue($default_value);
    $entity = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityBase')
      ->disableOriginalConstructor()
      ->getMock();
    // Set the field item list class to be used to avoid requiring the typed
    // data manager to retrieve it.
    $definition->setClass('Drupal\Core\Field\FieldItemList');
    $this->assertEquals($expected_default_value, $definition->getInitialValue($entity));

    $data_definition = $this->getMockBuilder('Drupal\Core\TypedData\DataDefinition')
      ->disableOriginalConstructor()
      ->getMock();
    $data_definition->expects($this->any())
      ->method('getClass')
      ->willReturn('Drupal\Core\Field\FieldItemBase');
    $definition->setItemDefinition($data_definition);

    // Set default value only with a literal.
    $definition->setInitialValue($default_value['value']);
    $this->assertEquals($expected_default_value, $definition->getInitialValue($entity));

    // Set default value with an indexed array.
    $definition->setInitialValue($expected_default_value);
    $this->assertEquals($expected_default_value, $definition->getInitialValue($entity));

    // Set default value with an empty array.
    $definition->setInitialValue([]);
    $this->assertEquals([], $definition->getInitialValue($entity));

    // Set default value with NULL.
    $definition->setInitialValue(NULL);
    $this->assertEquals([], $definition->getInitialValue($entity));
  }

  /**
   * Tests field translatable methods.
   *
   * @covers ::isTranslatable
   * @covers ::setTranslatable
   */
  public function testFieldTranslatable() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $this->assertFalse($definition->isTranslatable());
    $definition->setTranslatable(TRUE);
    $this->assertTrue($definition->isTranslatable());
    $definition->setTranslatable(FALSE);
    $this->assertFalse($definition->isTranslatable());
  }

  /**
   * Tests field revisionable methods.
   *
   * @covers ::isRevisionable
   * @covers ::setRevisionable
   */
  public function testFieldRevisionable() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $this->assertFalse($definition->isRevisionable());
    $definition->setRevisionable(TRUE);
    $this->assertTrue($definition->isRevisionable());
    $definition->setRevisionable(FALSE);
    $this->assertFalse($definition->isRevisionable());
  }

  /**
   * Tests field cardinality.
   *
   * @covers ::getCardinality
   * @covers ::setCardinality
   */
  public function testFieldCardinality() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $this->assertEquals(1, $definition->getCardinality());
    $definition->setCardinality(2);
    $this->assertEquals(2, $definition->getCardinality());
    $definition->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $this->assertEquals(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED, $definition->getCardinality());
  }

  /**
   * Tests required.
   *
   * @covers ::isRequired
   * @covers ::setRequired
   */
  public function testFieldRequired() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $this->assertFalse($definition->isRequired());
    $definition->setRequired(TRUE);
    $this->assertTrue($definition->isRequired());
    $definition->setRequired(FALSE);
    $this->assertFalse($definition->isRequired());
  }

  /**
   * Tests storage required.
   *
   * @covers ::isStorageRequired
   * @covers ::setStorageRequired
   */
  public function testFieldStorageRequired() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $this->assertFalse($definition->isStorageRequired());
    $definition->setStorageRequired(TRUE);
    $this->assertTrue($definition->isStorageRequired());
    $definition->setStorageRequired(FALSE);
    $this->assertFalse($definition->isStorageRequired());
  }

  /**
   * Tests provider.
   *
   * @covers ::getProvider
   * @covers ::setProvider
   */
  public function testFieldProvider() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $provider = $this->randomMachineName();
    $definition->setProvider($provider);
    $this->assertEquals($provider, $definition->getProvider());
  }

  /**
   * Tests custom storage.
   *
   * @covers ::hasCustomStorage
   * @covers ::setCustomStorage
   */
  public function testCustomStorage() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $this->assertFalse($definition->hasCustomStorage());
    $definition->setCustomStorage(TRUE);
    $this->assertTrue($definition->hasCustomStorage());
    $definition->setCustomStorage(FALSE);
    $this->assertFalse($definition->hasCustomStorage());
  }

  /**
   * Tests default value callbacks.
   *
   * @covers ::setDefaultValueCallback
   */
  public function testDefaultValueCallback() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    $callback = static::class . '::mockDefaultValueCallback';
    // setDefaultValueCallback returns $this.
    $this->assertSame($definition, $definition->setDefaultValueCallback($callback));
  }

  /**
   * Tests invalid default value callbacks.
   *
   * @covers ::setDefaultValueCallback
   */
  public function testInvalidDefaultValueCallback() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    // setDefaultValueCallback returns $this.
    $this->expectException(\InvalidArgumentException::class);
    $definition->setDefaultValueCallback([static::class, 'mockDefaultValueCallback']);
  }

  /**
   * Tests NULL default value callbacks.
   *
   * @covers ::setDefaultValueCallback
   */
  public function testNullDefaultValueCallback() {
    $definition = BaseFieldDefinition::create($this->fieldType);
    // setDefaultValueCallback returns $this.
    $this->assertSame($definition, $definition->setDefaultValueCallback(NULL));
  }

  /**
   * Provides a Mock base field default value callback.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity interface.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   Field definition.
   *
   * @return string
   *   Default value.
   */
  public static function mockDefaultValueCallback($entity, $definition) {
    return 'a default value';
  }

}
