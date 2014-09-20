<?php
/**
 * @file
 * Contains \Drupal\rdf\Tests\Field\NumberFieldRdfaTest.
 */

namespace Drupal\rdf\Tests\Field;

/**
 * Tests RDFa output by number field formatters.
 *
 * @group rdf
 */
class NumberFieldRdfaTest extends FieldRdfaTestBase {

  /**
   * Tests the integer formatter.
   */
  public function testIntegerFormatter() {
    $this->fieldType = 'integer';
    $testValue = 3;
    $this->createTestField();
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa(array('type' => $this->fieldType), 'http://schema.org/baseSalary', array('value' => $testValue));

    // Test that the content attribute is not created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field-item") and @content]');
    $this->assertFalse($result);
  }

  /**
   * Tests the integer formatter with settings.
   */
  public function testIntegerFormatterWithSettings() {
    $this->fieldType = 'integer';
    $formatter = array(
      'type' => $this->fieldType,
      'settings' => array(
        'thousand_separator' => '.',
        'prefix_suffix' => TRUE,
      ),
    );
    $testValue = 3333333.33;
    $field_settings = array(
      'prefix' => '#',
      'suffix' => ' llamas.',
    );
    $this->createTestField($field_settings);
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa($formatter, 'http://schema.org/baseSalary', array('value' => $testValue));

    // Test that the content attribute is created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field-item") and @content=:testValue]', array(':testValue' => $testValue));
    $this->assertTrue($result);
  }

  /**
   * Tests the float formatter.
   */
  public function testFloatFormatter() {
    $this->fieldType = 'float';
    $testValue = 3.33;
    $this->createTestField();
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa(array('type' => $this->fieldType), 'http://schema.org/baseSalary', array('value' => $testValue));

    // Test that the content attribute is not created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field-item") and @content]');
    $this->assertFalse($result);
  }

  /**
   * Tests the float formatter with settings.
   */
  public function testFloatFormatterWithSettings() {
    $this->fieldType = 'float';
    $formatter = array(
      'type' => $this->fieldType,
      'settings' => array(
        'thousand_separator' => '.',
        'decimal_separator' => ',',
        'prefix_suffix' => TRUE,
      ),
    );
    $testValue = 3333333.33;
    $field_settings = array(
      'prefix' => '$',
      'suffix' => ' more.',
    );
    $this->createTestField($field_settings);
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa($formatter, 'http://schema.org/baseSalary', array('value' => $testValue));

    // Test that the content attribute is created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field-item") and @content=:testValue]', array(':testValue' => $testValue));
    $this->assertTrue($result);
  }

  /**
   * Tests the float formatter with a scale. Scale is not exercised.
   */
  public function testFloatFormatterWithScale() {
    $this->fieldType = 'float';
    $formatter = array(
      'type' => $this->fieldType,
      'settings' => array(
        'scale' => 5,
      ),
    );
    $testValue = 3.33;
    $this->createTestField();
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa($formatter, 'http://schema.org/baseSalary', array('value' => $testValue));

    // Test that the content attribute is not created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field-item") and @content]');
    $this->assertFalse($result);
  }

  /**
   * Tests the float formatter with a scale. Scale is exercised.
   */
  public function testFloatFormatterWithScaleExercised() {
    $this->fieldType = 'float';
    $formatter = array(
      'type' => $this->fieldType,
      'settings' => array(
        'scale' => 5,
      ),
    );
    $testValue = 3.1234567;
    $this->createTestField();
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa($formatter, 'http://schema.org/baseSalary', array('value' => $testValue));

    // Test that the content attribute is created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field-item") and @content=:testValue]', array(':testValue' => $testValue));
    $this->assertTrue($result);
  }

  /**
   * Tests the decimal formatter.
   */
  public function testDecimalFormatter() {
    $this->fieldType = 'decimal';
    $testValue = 3.33;
    $this->createTestField();
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa(array('type' => $this->fieldType), 'http://schema.org/baseSalary', array('value' => $testValue));

    // Test that the content attribute is not created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field-item") and @content]');
    $this->assertFalse($result);
  }

  /**
   * Tests the decimal formatter with settings.
   */
  public function testDecimalFormatterWithSettings() {
    $this->fieldType = 'decimal';
    $formatter = array(
      'type' => $this->fieldType,
      'settings' => array(
        'thousand_separator' => 't',
        'decimal_separator' => '#',
        'prefix_suffix' => TRUE,
      ),
    );
    $testValue = 3333333.33;
    $field_settings = array(
      'prefix' => '$',
      'suffix' => ' more.',
    );
    $this->createTestField($field_settings);
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa($formatter, 'http://schema.org/baseSalary', array('value' => $testValue));

    // Test that the content attribute is created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field-item") and @content=:testValue]', array(':testValue' => $testValue));
    $this->assertTrue($result);
  }

  /**
   * Creates the RDF mapping for the field.
   */
  protected function createTestEntity($testValue) {
    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, array(
      'properties' => array('schema:baseSalary'),
    ))->save();

    // Set up test entity.
    $this->entity = entity_create('entity_test', array());
    $this->entity->{$this->fieldName}->value = $testValue;
  }
}
