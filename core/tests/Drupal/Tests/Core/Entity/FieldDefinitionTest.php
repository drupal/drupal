<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\FieldDefinitionTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Field\FieldDefinition;
use Drupal\Tests\UnitTestCase;

/**
 * Tests \Drupal\Core\Field\FieldDefinition.
 *
 * @group Entity
 */
class FieldDefinitionTest extends UnitTestCase {

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
  protected function setUp() {
    parent::setUp();

    // Prepare a container with a mock typed data object, that returns no
    // type definitions.
    // @todo: Overhaul how field definitions deal with dependencies and improve
    // unit tests. See https://drupal.org/node/2143555.
    $typed_data = $this->getMockBuilder('Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->getMock();

    $typed_data
      ->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValue(NULL));

    $container = $this->getMock('Drupal\Core\DependencyInjection\Container');
    $container
      ->expects($this->any())
      ->method('get')
      ->will($this->returnValue($typed_data));

    \Drupal::setContainer($container);
  }

  /**
   * Tests field name methods.
   */
  public function testFieldName() {
    $definition = new FieldDefinition();
    $field_name = $this->randomName();
    $definition->setName($field_name);
    $this->assertEquals($field_name, $definition->getName());
  }

  /**
   * Tests field label methods.
   */
  public function testFieldLabel() {
    $definition = new FieldDefinition();
    $label = $this->randomName();
    $definition->setLabel($label);
    $this->assertEquals($label, $definition->getLabel());
  }

  /**
   * Tests field description methods.
   */
  public function testFieldDescription() {
    $definition = new FieldDefinition();
    $description = $this->randomName();
    $definition->setDescription($description);
    $this->assertEquals($description, $definition->getDescription());
  }

  /**
   * Tests field type methods.
   */
  public function testFieldType() {
    $field_type = $this->randomName();
    $definition = FieldDefinition::create($field_type);
    $this->assertEquals($field_type, $definition->getType());
  }

  /**
   * Tests field settings methods.
   */
  public function testFieldSettings() {
    $definition = new FieldDefinition();
    $setting = $this->randomName();
    $value = $this->randomName();
    $definition->setSetting($setting, $value);
    $this->assertEquals($value, $definition->getSetting($setting));
    $this->assertEquals(array($setting => $value), $definition->getSettings());
  }

  /**
   * Tests field default value.
   */
  public function testFieldDefaultValue() {
    $definition = new FieldDefinition();
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
    $definition = new FieldDefinition();
    $this->assertFalse($definition->isTranslatable());
    $definition->setTranslatable(TRUE);
    $this->assertTrue($definition->isTranslatable());
    $definition->setTranslatable(FALSE);
    $this->assertFalse($definition->isTranslatable());
  }

  /**
   * Tests field cardinality.
   */
  public function testFieldCardinality() {
    $definition = new FieldDefinition();
    $this->assertEquals(1, $definition->getCardinality());
    // @todo: Add more tests when this can be controlled.
  }

  /**
   * Tests required.
   */
  public function testFieldRequired() {
    $definition = new FieldDefinition();
    $this->assertFalse($definition->isRequired());
    $definition->setRequired(TRUE);
    $this->assertTrue($definition->isRequired());
    $definition->setRequired(FALSE);
    $this->assertFalse($definition->isRequired());
  }

  /**
   * Tests configurable.
   */
  public function testFieldConfigurable() {
    $definition = new FieldDefinition();
    $this->assertFalse($definition->isConfigurable());
  }

}
