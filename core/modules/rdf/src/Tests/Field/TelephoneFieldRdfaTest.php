<?php
/**
 * @file
 * Contains \Drupal\rdf\Tests\Field\TelephoneFieldRdfaTest.
 */

namespace Drupal\rdf\Tests\Field;

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
  public static $modules = array('telephone', 'text');

  protected function setUp() {
    parent::setUp();

    $this->createTestField();

    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, array(
      'properties' => array('schema:telephone'),
    ))->save();

    // Set up test values.
    $this->testValue = '555-555-5555';
    $this->entity = entity_create('entity_test', array());
    $this->entity->{$this->fieldName}->value = $this->testValue;
  }

  /**
   * Tests the field formatters.
   */
  public function testAllFormatters() {
    // Tests the plain formatter.
    $this->assertFormatterRdfa(array('type' => 'text_plain'), 'http://schema.org/telephone', array('value' => $this->testValue));
    // Tests the telephone link formatter.
    $this->assertFormatterRdfa(array('type' => 'telephone_link'), 'http://schema.org/telephone', array('value' => 'tel:' . $this->testValue, 'type' => 'uri'));

    $formatter = array(
      'type' => 'telephone_link',
      'settings' => array('title' => 'Contact us'),
    );
    $expected_rdf_value = array(
      'value' => 'tel:' . $this->testValue,
      'type' => 'uri',
    );
    // Tests the telephone link formatter with custom title.
    $this->assertFormatterRdfa($formatter, 'http://schema.org/telephone', $expected_rdf_value);
  }
}
