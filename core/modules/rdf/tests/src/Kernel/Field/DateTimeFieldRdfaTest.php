<?php

namespace Drupal\Tests\rdf\Kernel\Field;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests RDFa output by datetime field formatters.
 *
 * @group rdf
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
  public static $modules = array('datetime');

  protected function setUp() {
    parent::setUp();

    $this->createTestField();

    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, array(
      'properties' => array('schema:dateCreated'),
    ))->save();

    // Set up test entity.
    $this->entity = EntityTest::create(array());
    $this->entity->{$this->fieldName}->value = $this->testValue;
  }

  /**
   * Tests the default formatter.
   */
  public function testDefaultFormatter() {
    $this->assertFormatterRdfa(array('type'=>'datetime_default'), 'http://schema.org/dateCreated', array('value' => $this->testValue . 'Z', 'type' => 'literal', 'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime'));
  }
}
