<?php
/**
 * @file
 * Contains \Drupal\rdf\Tests\Field\TextFieldRdfaTest.
 */

namespace Drupal\rdf\Tests\Field;

use Drupal\rdf\Tests\Field\FieldRdfaTestBase;

/**
 * Tests the placement of RDFa in text field formatters.
 */
class TextFieldRdfaTest extends FieldRdfaTestBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldType = 'text';

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

  /**
   * {@inheritdoc}
   */
  public static $modules = array('text');

  public static function getInfo() {
    return array(
      'name'  => 'Field formatter: text',
      'description'  => 'Tests RDFa output by text field formatters.',
      'group' => 'RDF',
    );
  }

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
   * Tests all formatters.
   *
   * @todo Check for the summary mapping.
   */
  public function testAllFormatters() {
    // Tests the default formatter.
    $this->assertFormatterRdfa(array('type'=>'text_default'), 'http://schema.org/text', array('value' => $this->testValue));
    // Tests the plain formatter.
    $this->assertFormatterRdfa(array('type'=>'string'), 'http://schema.org/text', array('value' => $this->testValue));
    // Tests the summary formatter.
    $this->assertFormatterRdfa(array('type'=>'text_summary_or_trimmed'), 'http://schema.org/text', array('value' => $this->testValue));
    // Tests the trimmed formatter.
    $this->assertFormatterRdfa(array('type'=>'text_trimmed'), 'http://schema.org/text', array('value' => $this->testValue));
  }
}
