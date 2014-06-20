<?php
/**
 * @file
 * Contains \Drupal\rdf\Tests\Field\FieldRdfaTestBase.
 */

namespace Drupal\rdf\Tests\Field;

use Drupal\field\Tests\FieldUnitTestBase;

abstract class FieldRdfaTestBase extends FieldUnitTestBase {

  /**
   * The machine name of the field type to test.
   *
   * @var string
   */
  protected $fieldType;

  /**
   * The name of the field to create for testing.
   *
   * @var string
   */
  protected $fieldName = 'field_test';

  /**
   * The URI to identify the entity.
   *
   * @var string
   */
  protected $uri = 'http://ex.com';

  /**
   * The entity to render for testing.
   *
   * @var \Drupal\Core\Entity\ContentEntityBase
   */
  protected $entity;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf', 'menu_link');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('system', array('router'));
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Helper function to test the formatter's RDFa.
   *
   * @param array $formatter
   *   An associative array describing the formatter to test and its settings
   *   containing:
   *   - type: The machine name of the field formatter to test.
   *   - settings: The settings of the field formatter to test.
   * @param string $property
   *   The property that should be found.
   * @param array $expected_rdf_value
   *   An associative array describing the expected value of the property
   *   containing:
   *   - value: The actual value of the string or URI.
   *   - type: The type of RDF value, e.g. 'literal' for a string, or 'uri'.
   *   Defaults to 'literal'.
   *   - datatype: (optional) The datatype of the value (e.g. xsd:dateTime).
   */
  protected function assertFormatterRdfa($formatter, $property, $expected_rdf_value) {
    $expected_rdf_value += array('type' => 'literal');

    // The field formatter will be rendered inside the entity. Set the field
    // formatter in the entity display options before rendering the entity.
    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->fieldName, $formatter)
      ->save();
    $build = entity_view($this->entity, 'default');
    $output = drupal_render($build);
    $graph = new \EasyRdf_Graph($this->uri, $output, 'rdfa');
    $this->assertTrue($graph->hasProperty($this->uri, $property, $expected_rdf_value), "Formatter {$formatter['type']} exposes data correctly for {$this->fieldType} fields.");
  }

  /**
   * Creates the field for testing.
   */
  protected function createTestField() {
    entity_create('field_config', array(
      'name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => $this->fieldType,
    ))->save();
    entity_create('field_instance_config', array(
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'bundle' => 'entity_test',
    ))->save();
  }

  /**
   * Gets the absolute URI of an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The entity for which to generate the URI.
   *
   * @return string
   *   The absolute URI.
   */
  protected function getAbsoluteUri($entity) {
    return $entity->url('canonical', array('absolute' => TRUE));
  }

}
