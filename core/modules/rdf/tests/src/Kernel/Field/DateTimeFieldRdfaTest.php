<?php

namespace Drupal\Tests\rdf\Kernel\Field;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests RDFa output by datetime field formatters.
 *
 * @group rdf
 * @group legacy
 */
class DateTimeFieldRdfaTest extends FieldRdfaTestBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldType = 'datetime';

  /**
   * The 'value' property value for testing.
   *
   * @var string
   */
  protected $testValue = '2014-01-28T06:01:01';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['datetime'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createTestField();

    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, [
      'properties' => ['schema:dateCreated'],
    ])->save();

    // Set up test entity.
    $this->entity = EntityTest::create([]);
    $this->entity->{$this->fieldName}->value = $this->testValue;
  }

  /**
   * Tests the default formatter.
   */
  public function testDefaultFormatter() {
    $this->assertFormatterRdfa(['type' => 'datetime_default'], 'http://schema.org/dateCreated', ['value' => $this->testValue . 'Z', 'type' => 'literal', 'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime']);
  }

}
