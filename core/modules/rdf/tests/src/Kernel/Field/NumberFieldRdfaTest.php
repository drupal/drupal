<?php

namespace Drupal\Tests\rdf\Kernel\Field;

use Drupal\entity_test\Entity\EntityTest;

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
    $this->assertFormatterRdfa(['type' => 'number_integer'], 'http://schema.org/baseSalary', ['value' => $testValue]);

    // Test that the content attribute is not created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field__items") and @content]');
    $this->assertEmpty($result);
  }

  /**
   * Tests the integer formatter with settings.
   */
  public function testIntegerFormatterWithSettings() {
    \Drupal::service('theme_installer')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();
    $this->fieldType = 'integer';
    $formatter = [
      'type' => 'number_integer',
      'settings' => [
        'thousand_separator' => '.',
        'prefix_suffix' => TRUE,
      ],
    ];
    $testValue = 3333333.33;
    $field_settings = [
      'prefix' => '#',
      'suffix' => ' llamas.',
    ];
    $this->createTestField($field_settings);
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa($formatter, 'http://schema.org/baseSalary', ['value' => $testValue]);

    // Test that the content attribute is created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field__item") and @content=:testValue]', [':testValue' => $testValue]);
    $this->assertNotEmpty($result);
  }

  /**
   * Tests the float formatter.
   */
  public function testFloatFormatter() {
    $this->fieldType = 'float';
    $testValue = 3.33;
    $this->createTestField();
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa(['type' => 'number_unformatted'], 'http://schema.org/baseSalary', ['value' => $testValue]);

    // Test that the content attribute is not created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field__items") and @content]');
    $this->assertEmpty($result);
  }

  /**
   * Tests the float formatter with settings.
   */
  public function testFloatFormatterWithSettings() {
    \Drupal::service('theme_installer')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();
    $this->fieldType = 'float';
    $formatter = [
      'type' => 'number_decimal',
      'settings' => [
        'thousand_separator' => '.',
        'decimal_separator' => ',',
        'prefix_suffix' => TRUE,
      ],
    ];
    $testValue = 3333333.33;
    $field_settings = [
      'prefix' => '$',
      'suffix' => ' more.',
    ];
    $this->createTestField($field_settings);
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa($formatter, 'http://schema.org/baseSalary', ['value' => $testValue]);

    // Test that the content attribute is created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field__item") and @content=:testValue]', [':testValue' => $testValue]);
    $this->assertNotEmpty($result);
  }

  /**
   * Tests the float formatter with a scale. Scale is not exercised.
   */
  public function testFloatFormatterWithScale() {
    $this->fieldType = 'float';
    $formatter = [
      'type' => 'number_decimal',
      'settings' => [
        'scale' => 5,
      ],
    ];
    $testValue = 3.33;
    $this->createTestField();
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa($formatter, 'http://schema.org/baseSalary', ['value' => $testValue]);

    // Test that the content attribute is not created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field__items") and @content]');
    $this->assertEmpty($result);
  }

  /**
   * Tests the float formatter with a scale. Scale is exercised.
   */
  public function testFloatFormatterWithScaleExercised() {
    \Drupal::service('theme_installer')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();
    $this->fieldType = 'float';
    $formatter = [
      'type' => 'number_decimal',
      'settings' => [
        'scale' => 5,
      ],
    ];
    $testValue = 3.1234567;
    $this->createTestField();
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa($formatter, 'http://schema.org/baseSalary', ['value' => $testValue]);

    // Test that the content attribute is created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field__item") and @content=:testValue]', [':testValue' => $testValue]);
    $this->assertNotEmpty($result);
  }

  /**
   * Tests the decimal formatter.
   */
  public function testDecimalFormatter() {
    $this->fieldType = 'decimal';
    $testValue = 3.33;
    $this->createTestField();
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa(['type' => 'number_decimal'], 'http://schema.org/baseSalary', ['value' => $testValue]);

    // Test that the content attribute is not created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field__items") and @content]');
    $this->assertEmpty($result);
  }

  /**
   * Tests the decimal formatter with settings.
   */
  public function testDecimalFormatterWithSettings() {
    \Drupal::service('theme_installer')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();
    $this->fieldType = 'decimal';
    $formatter = [
      'type' => 'number_decimal',
      'settings' => [
        'thousand_separator' => 't',
        'decimal_separator' => '#',
        'prefix_suffix' => TRUE,
      ],
    ];
    $testValue = 3333333.33;
    $field_settings = [
      'prefix' => '$',
      'suffix' => ' more.',
    ];
    $this->createTestField($field_settings);
    $this->createTestEntity($testValue);
    $this->assertFormatterRdfa($formatter, 'http://schema.org/baseSalary', ['value' => $testValue]);

    // Test that the content attribute is created.
    $result = $this->xpathContent($this->getRawContent(), '//div[contains(@class, "field__item") and @content=:testValue]', [':testValue' => $testValue]);
    $this->assertNotEmpty($result);
  }

  /**
   * Creates the RDF mapping for the field.
   */
  protected function createTestEntity($testValue) {
    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, [
      'properties' => ['schema:baseSalary'],
    ])->save();

    // Set up test entity.
    $this->entity = EntityTest::create([]);
    $this->entity->{$this->fieldName}->value = $testValue;
  }

}
