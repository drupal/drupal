<?php

namespace Drupal\Tests\rdf\Kernel\Field;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests RDFa output by telephone field formatters.
 *
 * @group rdf
 */
class TelephoneFieldRdfaTest extends FieldRdfaTestBase {

  /**
   * A test value for the telephone field.
   *
   * @var string
   */
  protected $testValue;

  /**
   * {@inheritdoc}
   */
  protected $fieldType = 'telephone';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['telephone', 'text'];

  protected function setUp(): void {
    parent::setUp();

    $this->createTestField();

    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, [
      'properties' => ['schema:telephone'],
    ])->save();

    // Set up test values.
    $this->testValue = '555-555-5555';
    $this->entity = EntityTest::create([]);
    $this->entity->{$this->fieldName}->value = $this->testValue;
  }

  /**
   * Tests the field formatters.
   */
  public function testAllFormatters() {
    // Tests the plain formatter.
    $this->assertFormatterRdfa(['type' => 'string'], 'http://schema.org/telephone', ['value' => $this->testValue]);
    // Tests the telephone link formatter.
    $this->assertFormatterRdfa(['type' => 'telephone_link'], 'http://schema.org/telephone', ['value' => 'tel:' . $this->testValue, 'type' => 'uri']);

    $formatter = [
      'type' => 'telephone_link',
      'settings' => ['title' => 'Contact us'],
    ];
    $expected_rdf_value = [
      'value' => 'tel:' . $this->testValue,
      'type' => 'uri',
    ];
    // Tests the telephone link formatter with custom title.
    $this->assertFormatterRdfa($formatter, 'http://schema.org/telephone', $expected_rdf_value);
  }

}
