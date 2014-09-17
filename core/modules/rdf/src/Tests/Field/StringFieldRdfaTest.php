<?php
/**
 * @file
 * Contains \Drupal\rdf\Tests\Field\TextFieldRdfaTest.
 */

namespace Drupal\rdf\Tests\Field;

/**
 * Tests RDFa output by text field formatters.
 *
 * @group rdf
 */
class StringFieldRdfaTest extends FieldRdfaTestBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldType = 'string';

  /**
   * The 'value' property value for testing.
   *
   * @var string
   */
  protected $testValue = 'test_text_value';

  /**
   * The 'summary' property value for testing.
   *
   * @var string
   */
  protected $testSummary = 'test_summary_value';

  public function setUp() {
    parent::setUp();

    $this->createTestField();

    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, array(
      'properties' => array('schema:text'),
    ))->save();

    // Set up test entity.
    $this->entity = entity_create('entity_test');
    $this->entity->{$this->fieldName}->value = $this->testValue;
    $this->entity->{$this->fieldName}->summary = $this->testSummary;
  }

  /**
   * Tests string formatters.
   */
  public function testStringFormatters() {
    // Tests the string formatter.
    $this->assertFormatterRdfa(array('type'=>'string'), 'http://schema.org/text', array('value' => $this->testValue));
  }
}
