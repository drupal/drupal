<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for the FieldDefinition class.
 *
 * @group Entity
 * @group field
 * @coversDefaultClass \Drupal\Core\Field\FieldDefinition
 */
class FieldDefinitionTest extends UnitTestCase {

  /**
   * A dummy field type name.
   *
   * @var string
   */
  protected $fieldType;

  /**
   * A dummy field type definition.
   *
   * @var array
   */
  protected $fieldTypeDefinition;

  /**
   * The test field storage definition.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  protected $storageDefinition;

  /**
   * A flag for setting if the field storage supports translation.
   *
   * @var bool
   */
  protected $storageSupportsTranslation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->fieldType = $this->randomMachineName();
    $this->fieldTypeDefinition = [
      'id' => $this->fieldType,
      'field_settings' => [
        'some_instance_setting' => 'value 2',
      ],
      'storage_settings' => [
        'some_storage_setting' => 'some value',
      ],
    ];
    $field_type_manager = $this->prophesize(FieldTypePluginManagerInterface::class);
    $field_type_manager->getDefinitions()->willReturn([$this->fieldType => $this->fieldTypeDefinition]);
    $field_type_manager->getDefinition()->willReturn($this->fieldTypeDefinition);
    $field_type_manager->getDefaultFieldSettings($this->fieldType)->willReturn($this->fieldTypeDefinition['field_settings']);
    $field_type_manager->getDefaultStorageSettings($this->fieldType)->willReturn($this->fieldTypeDefinition['storage_settings']);

    $storage_definition = $this->prophesize(FieldStorageDefinitionInterface::class);
    $storage_definition->getMainPropertyName()->willReturn('value');
    $storage_definition->getType()->willReturn($this->fieldType);
    $storage_definition->getName()->willReturn('test_field_name');
    $storage_supports_translation = &$this->storageSupportsTranslation;
    $storage_definition->isTranslatable()->will(function () use (&$storage_supports_translation) {
      return $storage_supports_translation;
    });
    $storage_definition->getSettings()->willReturn($this->fieldTypeDefinition['storage_settings']);
    $storage_definition->getSetting('some_storage_setting')->willReturn($this->fieldTypeDefinition['storage_settings']['some_storage_setting']);

    $this->storageDefinition = $storage_definition->reveal();

    $entity_field_manager = $this->prophesize(EntityFieldManagerInterface::class);
    $entity_field_manager->getFieldStorageDefinitions('entity_test')->willReturn([
      'foo' => $storage_definition->reveal(),
    ]);

    $typed_data_manager = $this->prophesize(TypedDataManager::class);

    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $field_type_manager->reveal());
    $container->set('entity_field.manager', $entity_field_manager->reveal());
    $container->set('typed_data_manager', $typed_data_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::getName
   * @dataProvider factoryTypeProvider
   */
  public function testFieldName($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);
    $this->assertEquals($this->storageDefinition->getName(), $definition->getName());
  }

  /**
   * @covers ::getLabel
   * @dataProvider factoryTypeProvider
   */
  public function testFieldLabel($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);
    $label = $this->randomMachineName();
    $definition->setLabel($label);
    $this->assertEquals($label, $definition->getLabel());
  }

  /**
   * @covers ::setTargetBundle
   * @covers ::getTargetBundle
   * @dataProvider factoryTypeProvider
   */
  public function testBundle($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);
    $bundle = $this->randomMachineName();
    $definition->setTargetBundle($bundle);
    $this->assertEquals($bundle, $definition->getTargetBundle());
  }

  /**
   * @covers ::getDescription
   * @dataProvider factoryTypeProvider
   */
  public function testFieldDescription($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);
    $description = $this->randomMachineName();
    $definition->setDescription($description);
    $this->assertEquals($description, $definition->getDescription());
  }

  /**
   * @covers ::getType
   * @dataProvider factoryTypeProvider
   */
  public function testFieldType($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);
    $this->assertEquals($this->fieldType, $definition->getType());
  }

  /**
   * @covers ::getSetting
   * @covers ::setSetting
   * @covers ::getSettings
   * @dataProvider factoryTypeProvider
   */
  public function testFieldSettings($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);
    $setting = $this->randomMachineName();
    $value = $this->randomMachineName();
    $definition->setSetting($setting, $value);
    $this->assertEquals($value, $definition->getSetting($setting));
    $default_settings = $this->fieldTypeDefinition['field_settings'] + $this->fieldTypeDefinition['storage_settings'];
    $this->assertEquals([$setting => $value] + $default_settings, $definition->getSettings());
  }

  /**
   * @covers ::getSetting
   * @covers ::setSetting
   * @covers ::getSettings
   * @dataProvider factoryTypeProvider
   */
  public function testDefaultFieldSettings($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);
    $expected_settings = $this->fieldTypeDefinition['field_settings'] + $this->fieldTypeDefinition['storage_settings'];
    $this->assertEquals($expected_settings, $definition->getSettings());
    foreach ($expected_settings as $setting => $value) {
      $this->assertEquals($value, $definition->getSetting($setting));
    }
  }

  /**
   * @covers ::getDefaultValue
   * @covers ::setDefaultValue
   * @dataProvider factoryTypeProvider
   */
  public function testFieldDefaultValue($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);

    $this->assertEquals([], $definition->getDefaultValueLiteral());

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
    $this->assertEquals($expected_default_value, $definition->getDefaultValue($entity));

    $data_definition = $this->getMockBuilder('Drupal\Core\TypedData\DataDefinition')
      ->disableOriginalConstructor()
      ->getMock();
    $data_definition->expects($this->any())
      ->method('getClass')
      ->will($this->returnValue('Drupal\Core\Field\FieldItemBase'));
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
   * Tests field translatable methods.
   *
   * @covers ::isTranslatable
   * @covers ::setTranslatable
   * @dataProvider factoryTypeProvider
   */
  public function testFieldTranslatable($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);
    $this->assertFalse($definition->isTranslatable());
    $definition->setTranslatable(TRUE);
    $this->assertTrue($definition->isTranslatable());
    $definition->setTranslatable(FALSE);
    $this->assertFalse($definition->isTranslatable());

    $this->storageSupportsTranslation = FALSE;
    $definition->setTranslatable(TRUE);
    $this->assertFalse($this->storageDefinition->isTranslatable());
    $this->assertFalse($definition->isTranslatable());
  }

  /**
   * Tests required.
   *
   * @covers ::isRequired
   * @covers ::setRequired
   * @dataProvider factoryTypeProvider
   */
  public function testFieldRequired($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);
    $this->assertFalse($definition->isRequired());
    $definition->setRequired(TRUE);
    $this->assertTrue($definition->isRequired());
    $definition->setRequired(FALSE);
    $this->assertFalse($definition->isRequired());
  }

  /**
   * Tests default value callbacks.
   *
   * @covers ::setDefaultValueCallback
   * @dataProvider factoryTypeProvider
   */
  public function testDefaultValueCallback($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);
    $callback = get_class($this) . '::mockDefaultValueCallback';
    // setDefaultValueCallback returns $this.
    $this->assertSame($definition, $definition->setDefaultValueCallback($callback));
    $this->assertSame($callback, $definition->getDefaultValueCallback());
  }

  /**
   * Tests invalid default value callbacks.
   *
   * @covers ::setDefaultValueCallback
   * @dataProvider factoryTypeProvider
   */
  public function testInvalidDefaultValueCallback($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);
    // setDefaultValueCallback returns $this.
    $this->setExpectedException(\InvalidArgumentException::class);
    $definition->setDefaultValueCallback([get_class($this), 'mockDefaultValueCallback']);
  }

  /**
   * Tests NULL default value callbacks.
   *
   * @covers ::setDefaultValueCallback
   * @dataProvider factoryTypeProvider
   */
  public function testNullDefaultValueCallback($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);
    // setDefaultValueCallback returns $this.
    $this->assertSame($definition, $definition->setDefaultValueCallback(NULL));
    $this->assertSame(NULL, $definition->getDefaultValueCallback());
  }

  /**
   * Tests the display configuration settings.
   *
   * @covers ::isDisplayConfigurable
   * @covers ::getDisplayOptions
   * @dataProvider factoryTypeProvider
   */
  public function testDisplayConfigurationSettings($factory_name) {
    $definition = $this->initializeFieldUsingFactory($factory_name);
    $this->assertEquals(FALSE, $definition->isDisplayConfigurable('foo'));
    $this->assertEquals(NULL, $definition->getDisplayOptions('foo'));

    $definition->setDisplayConfigurable('foo', TRUE);
    $this->assertEquals(TRUE, $definition->isDisplayConfigurable('foo'));
    $this->assertEquals(['region' => 'hidden'], $definition->getDisplayOptions('foo'));

    $definition->setDisplayOptions('foo', ['foo' => 'bar']);
    $this->assertEquals(['foo' => 'bar'], $definition->getDisplayOptions('foo'));
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

  /**
   * A data provider for all the types of factories that can create definitions.
   */
  public function factoryTypeProvider() {
    return [
      '::createFromFieldStorageDefinition factory' => [
        'createFromFieldStorageDefinition',
      ],
      '::create factory' => [
        'create',
      ],
      '::createFromDataType factory' => [
        'createFromDataType',
      ],
      '::createFromItemType factory' => [
        'createFromItemType',
      ],
    ];
  }

  /**
   * Creates a bundle field using a specified factory.
   *
   * @param string $factory_name
   *   The factory name to use.
   *
   * @return \Drupal\Core\Field\FieldDefinition
   */
  protected function initializeFieldUsingFactory($factory_name) {
    switch ($factory_name) {
      case 'createFromFieldStorageDefinition':
        return FieldDefinition::createFromFieldStorageDefinition($this->storageDefinition);

      case 'create':
        $definition = FieldDefinition::create($this->fieldType);
        $definition->setFieldStorageDefinition($this->storageDefinition);
        return $definition;

      case 'createFromDataType':
        $definition = FieldDefinition::createFromDataType($this->fieldType);
        $definition->setFieldStorageDefinition($this->storageDefinition);
        return $definition;

      case 'createFromItemType':
        $definition = FieldDefinition::createFromItemType($this->fieldType);
        $definition->setFieldStorageDefinition($this->storageDefinition);
        return $definition;
    }
  }

}
