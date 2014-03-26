<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\FieldDefinitionTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests \Drupal\Core\Field\FieldDefinition.
 *
 * @group Entity
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
   * @var string
   */
  protected $fieldTypeDefinition;


  public static function getInfo() {
    return array(
      'name' => 'Field definition test',
      'description' => 'Unit test for FieldDefinition.',
      'group' => 'Entity'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // Mock the field type manager and place it in the container.
    $field_type_manager = $this->getMock('Drupal\Core\Field\FieldTypePluginManagerInterface');

    $this->fieldType = $this->randomName();
    $this->fieldTypeDefinition = array(
      'id' => $this->fieldType,
      'settings' => array(
        'some_setting' => 'value 1'
      ),
      'instance_settings' => array(
        'some_instance_setting' => 'value 2',
      ),
    );

    $field_type_manager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue(array($this->fieldType => $this->fieldTypeDefinition)));
    $field_type_manager->expects($this->any())
      ->method('getDefinition')
      ->with($this->fieldType)
      ->will($this->returnValue($this->fieldTypeDefinition));
    $field_type_manager->expects($this->any())
      ->method('getDefaultSettings')
      ->with($this->fieldType)
      ->will($this->returnValue($this->fieldTypeDefinition['settings']));
    $field_type_manager->expects($this->any())
      ->method('getDefaultInstanceSettings')
      ->with($this->fieldType)
      ->will($this->returnValue($this->fieldTypeDefinition['instance_settings']));

    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $field_type_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests field name methods.
   */
  public function testFieldName() {
    $definition = FieldDefinition::create($this->fieldType);
    $field_name = $this->randomName();
    $definition->setName($field_name);
    $this->assertEquals($field_name, $definition->getName());
  }

  /**
   * Tests field label methods.
   */
  public function testFieldLabel() {
    $definition = FieldDefinition::create($this->fieldType);
    $label = $this->randomName();
    $definition->setLabel($label);
    $this->assertEquals($label, $definition->getLabel());
  }

  /**
   * Tests field description methods.
   */
  public function testFieldDescription() {
    $definition = FieldDefinition::create($this->fieldType);
    $description = $this->randomName();
    $definition->setDescription($description);
    $this->assertEquals($description, $definition->getDescription());
  }

  /**
   * Tests field type methods.
   */
  public function testFieldType() {
    $definition = FieldDefinition::create($this->fieldType);
    $this->assertEquals($this->fieldType, $definition->getType());
  }

  /**
   * Tests field settings methods.
   */
  public function testFieldSettings() {
    $definition = FieldDefinition::create($this->fieldType);
    $setting = $this->randomName();
    $value = $this->randomName();
    $definition->setSetting($setting, $value);
    $this->assertEquals($value, $definition->getSetting($setting));
    $default_settings = $this->fieldTypeDefinition['settings'] + $this->fieldTypeDefinition['instance_settings'];
    $this->assertEquals(array($setting => $value) + $default_settings, $definition->getSettings());
  }

  /**
   * Tests the initialization of default field settings.
   */
  public function testDefaultFieldSettings() {
    $definition = FieldDefinition::create($this->fieldType);
    $expected_settings = $this->fieldTypeDefinition['settings'] + $this->fieldTypeDefinition['instance_settings'];
    $this->assertEquals($expected_settings, $definition->getSettings());
    foreach ($expected_settings as $setting => $value) {
      $this->assertEquals($value, $definition->getSetting($setting));
    }
  }

  /**
   * Tests field default value.
   */
  public function testFieldDefaultValue() {
    $definition = FieldDefinition::create($this->fieldType);
    $setting = 'default_value';
    $value = $this->randomName();
    $definition->setSetting($setting, $value);
    $entity = $this->getMockBuilder('Drupal\Core\Entity\Entity')
      ->disableOriginalConstructor()
      ->getMock();
    $this->assertEquals($value, $definition->getDefaultValue($entity));
  }

  /**
   * Tests field translatable methods.
   */
  public function testFieldTranslatable() {
    $definition = FieldDefinition::create($this->fieldType);
    $this->assertFalse($definition->isTranslatable());
    $definition->setTranslatable(TRUE);
    $this->assertTrue($definition->isTranslatable());
    $definition->setTranslatable(FALSE);
    $this->assertFalse($definition->isTranslatable());
  }

  /**
   * Tests field revisionable methods.
   */
  public function testFieldRevisionable() {
    $definition = FieldDefinition::create($this->fieldType);
    $this->assertFalse($definition->isRevisionable());
    $definition->setRevisionable(TRUE);
    $this->assertTrue($definition->isRevisionable());
    $definition->setRevisionable(FALSE);
    $this->assertFalse($definition->isRevisionable());
  }

  /**
   * Tests field cardinality.
   */
  public function testFieldCardinality() {
    $definition = FieldDefinition::create($this->fieldType);
    $this->assertEquals(1, $definition->getCardinality());
    $definition->setCardinality(2);
    $this->assertEquals(2, $definition->getCardinality());
    $definition->setCardinality(FieldDefinitionInterface::CARDINALITY_UNLIMITED);
    $this->assertEquals(FieldDefinitionInterface::CARDINALITY_UNLIMITED, $definition->getCardinality());
  }

  /**
   * Tests required.
   */
  public function testFieldRequired() {
    $definition = FieldDefinition::create($this->fieldType);
    $this->assertFalse($definition->isRequired());
    $definition->setRequired(TRUE);
    $this->assertTrue($definition->isRequired());
    $definition->setRequired(FALSE);
    $this->assertFalse($definition->isRequired());
  }

  /**
   * Tests provider.
   */
  public function testFieldProvider() {
    $definition = FieldDefinition::create($this->fieldType);
    $provider = $this->randomName();
    $definition->setProvider($provider);
    $this->assertEquals($provider, $definition->getProvider());
  }

  /**
   * Tests custom storage.
   */
  public function testCustomStorage() {
    $definition = FieldDefinition::create($this->fieldType);
    $this->assertFalse($definition->hasCustomStorage());
    $definition->setCustomStorage(TRUE);
    $this->assertTrue($definition->hasCustomStorage());
    $definition->setCustomStorage(FALSE);
    $this->assertFalse($definition->hasCustomStorage());
  }

}
