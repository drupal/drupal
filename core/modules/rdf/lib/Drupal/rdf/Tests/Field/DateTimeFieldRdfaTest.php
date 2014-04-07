<?php
/**
 * @file
 * Contains \Drupal\rdf\Tests\Field\DateTimeFieldRdfaTest.
 */

namespace Drupal\rdf\Tests\Field;

use Drupal\rdf\Tests\Field\FieldRdfaTestBase;

/**
 * Tests the placement of RDFa in text field formatters.
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

  public static function getInfo() {
    return array(
      'name'  => 'Field formatter: datetime',
      'description'  => 'Tests RDFa output by datetime field formatters.',
      'group' => 'RDF',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->createTestField();

    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, array(
      'properties' => array('schema:dateCreated'),
    ))->save();

    // Set up test entity.
    $this->entity = entity_create('entity_test', array());
    $this->entity->{$this->fieldName}->value = $this->testValue;
  }

  /**
   * Tests the default formatter.
   */
  public function testDefaultFormatter() {
    $this->assertFormatterRdfa(array('type'=>'datetime_default'), 'http://schema.org/dateCreated', array('value' => $this->testValue . 'Z', 'type' => 'literal', 'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime'));
  }
}
