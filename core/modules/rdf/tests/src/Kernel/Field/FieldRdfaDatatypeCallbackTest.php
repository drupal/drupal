<?php

namespace Drupal\Tests\rdf\Kernel\Field;

use Drupal\entity_test\Entity\EntityTest;

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

  protected function setUp() {
    parent::setUp();

    $this->createTestField();

    $this->installConfig(array('filter'));

    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, array(
      'properties' => array('schema:interactionCount'),
      'datatype_callback' => array(
        'callable' => 'Drupal\rdf\Tests\Field\TestDataConverter::convertFoo',
      ),
    ))->save();

    // Set up test values.
    $this->testValue = $this->randomMachineName();
    $this->entity = EntityTest::create();
    $this->entity->{$this->fieldName}->value = $this->testValue;
    $this->entity->save();

    $this->uri = $this->getAbsoluteUri($this->entity);
  }

  /**
   * Tests the default formatter.
   */
  public function testDefaultFormatter() {
    // Expected value is the output of the datatype callback, not the raw value.
    $this->assertFormatterRdfa(array('type' => 'text_default'), 'http://schema.org/interactionCount', array('value' => 'foo' . $this->testValue));
  }

}
