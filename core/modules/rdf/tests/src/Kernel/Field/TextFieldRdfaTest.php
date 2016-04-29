<?php

namespace Drupal\Tests\rdf\Kernel\Field;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests RDFa output by text field formatters.
 *
 * @group rdf
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
  public static $modules = array('text', 'filter');

  protected function setUp() {
    parent::setUp();

    $this->installConfig(array('filter'));

    $this->createTestField();

    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, array(
      'properties' => array('schema:text'),
    ))->save();

    // Set up test entity.
    $this->entity = EntityTest::create();
    $this->entity->{$this->fieldName}->value = $this->testValue;
    $this->entity->{$this->fieldName}->summary = $this->testSummary;
  }

  /**
   * Tests all formatters.
   *
   * @todo Check for the summary mapping.
   */
  public function testAllFormatters() {
    $formatted_value = strip_tags($this->entity->{$this->fieldName}->processed);

    // Tests the default formatter.
    $this->assertFormatterRdfa(array('type' => 'text_default'), 'http://schema.org/text', array('value' => $formatted_value));
    // Tests the summary formatter.
    $this->assertFormatterRdfa(array('type' => 'text_summary_or_trimmed'), 'http://schema.org/text', array('value' => $formatted_value));
    // Tests the trimmed formatter.
    $this->assertFormatterRdfa(array('type' => 'text_trimmed'), 'http://schema.org/text', array('value' => $formatted_value));
  }
}
