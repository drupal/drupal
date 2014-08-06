<?php
/**
 * @file
 * Contains \Drupal\rdf\Tests\Field\FieldRdfaDatatypeCallbackTest.
 */

namespace Drupal\rdf\Tests\Field;

/**
 * Tests the RDFa output of a text field formatter with a datatype callback.
 *
 * @group rdf
 */
class FieldRdfaDatatypeCallbackTest extends FieldRdfaTestBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldType = 'text';

  /**
   * {@inheritdoc}
   */
  public static $modules = array('text', 'filter');

  public function setUp() {
    parent::setUp();

    $this->createTestField();

    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, array(
      'properties' => array('schema:interactionCount'),
      'datatype_callback' => array(
        'callable' => 'Drupal\rdf\Tests\Field\TestDataConverter::convertFoo',
      ),
    ))->save();

    // Set up test values.
    $this->test_value = $this->randomMachineName();
    $this->entity = entity_create('entity_test');
    $this->entity->{$this->fieldName}->value = $this->test_value;
    $this->entity->save();

    $this->uri = $this->getAbsoluteUri($this->entity);
  }

  /**
   * Tests the default formatter.
   */
  public function testDefaultFormatter() {
    // Expected value is the output of the datatype callback, not the raw value.
    $this->assertFormatterRdfa(array('type'=>'text_default'), 'http://schema.org/interactionCount', array('value' => 'foo' . $this->test_value));
  }

}

