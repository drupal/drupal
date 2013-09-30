<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\FieldDefinitionTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\Field\FieldDefinition;
use Drupal\Tests\UnitTestCase;

/**
 * Tests \Drupal\Core\Entity\Field\FieldDefinition.
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
   * Tests field name methods.
   */
  public function testFieldName() {
    $definition = new FieldDefinition();
    $field_name = $this->randomName();
    $definition->setFieldName($field_name);
    $this->assertEquals($field_name, $definition->getFieldName());
  }

  /**
   * Tests field label methods.
   */
  public function testFieldLabel() {
    $definition = new FieldDefinition();
    $label = $this->randomName();
    $definition->setFieldLabel($label);
    $this->assertEquals($label, $definition->getFieldLabel());
  }

  /**
   * Tests field description methods.
   */
  public function testFieldDescription() {
    $definition = new FieldDefinition();
    $description = $this->randomName();
    $definition->setFieldDescription($description);
    $this->assertEquals($description, $definition->getFieldDescription());
  }

  /**
   * Tests field type methods.
   */
  public function testFieldType() {
    $definition = new FieldDefinition();
    $field_name = $this->randomName();
    $definition->setFieldType($field_name);
    $this->assertEquals($field_name, $definition->getFieldType());
  }

  /**
   * Tests field settings methods.
   */
  public function testFieldSettings() {
    $definition = new FieldDefinition();
    $setting = $this->randomName();
    $value = $this->randomName();
    $definition->setFieldSetting($setting, $value);
    $this->assertEquals($value, $definition->getFieldSetting($setting));
    $this->assertEquals(array($setting => $value), $definition->getFieldSettings());
  }

  /**
   * Tests field default value.
   */
  public function testFieldDefaultValue() {
    $definition = new FieldDefinition();
    $setting = 'default_value';
    $value = $this->randomName();
    $definition->setFieldSetting($setting, $value);
    $entity = $this->getMockBuilder('Drupal\Core\Entity\Entity')
      ->disableOriginalConstructor()
      ->getMock();
    $this->assertEquals($value, $definition->getFieldDefaultValue($entity));
  }

  /**
   * Tests field translatable methods.
   */
  public function testFieldTranslatable() {
    $definition = new FieldDefinition();
    $this->assertFalse($definition->isFieldTranslatable());
    $definition->setTranslatable(TRUE);
    $this->assertTrue($definition->isFieldTranslatable());
    $definition->setTranslatable(FALSE);
    $this->assertFalse($definition->isFieldTranslatable());
  }

  /**
   * Tests field cardinality.
   */
  public function testFieldCardinality() {
    $definition = new FieldDefinition();
    $this->assertEquals(1, $definition->getFieldCardinality());
    // @todo: Add more tests when this can be controlled.
  }

  /**
   * Tests required.
   */
  public function testFieldRequired() {
    $definition = new FieldDefinition();
    $this->assertFalse($definition->isFieldRequired());
    $definition->setFieldRequired(TRUE);
    $this->assertTrue($definition->isFieldRequired());
    $definition->setFieldRequired(FALSE);
    $this->assertFalse($definition->isFieldRequired());
  }

  /**
   * Tests configurable.
   */
  public function testFieldConfigurable() {
    $definition = new FieldDefinition();
    $this->assertFalse($definition->isFieldConfigurable());
  }

}
