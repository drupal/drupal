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
   * @var \Drupal\Core\Entity\EntityNG
   */
  protected $entity;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf');

  /**
   * Helper function to test the formatter's RDFa.
   *
   * @param string $formatter
   *   The machine name of the formatter to test.
   * @param string $property
   *   The property that should be found.
   * @param string $value
   *   The expected value of the property.
   * @param string $object_type
   *   The object's type, either 'uri' or 'literal'.
   */
  protected function assertFormatterRdfa($formatter, $property, $value, $object_type = 'literal') {
    $build = field_view_field($this->entity, $this->fieldName, array('type' => $formatter));
    $rendered = "<div about='$this->uri'>" . drupal_render($build) . '</div>';
    $graph = new \EasyRdf_Graph($this->uri, $rendered, 'rdfa');

    $expected_value = array(
      'type' => $object_type,
      'value' => $value,
    );
    $this->assertTrue($graph->hasProperty($this->uri, $property, $expected_value), "Formatter $formatter exposes data correctly for {$this->fieldType} fields.");
  }

  /**
   * Creates the field for testing.
   */
  protected function createTestField() {
    entity_create('field_entity', array(
      'field_name' => $this->fieldName,
      'type' => $this->fieldType,
    ))->save();
    entity_create('field_instance', array(
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'bundle' => 'entity_test',
    ))->save();
  }

  /**
   * Gets the absolute URI of an entity.
   *
   * @param \Drupal\Core\Entity\EntityNG $entity
   *   The entity for which to generate the URI.
   *
   * @return string
   *   The absolute URI.
   */
  protected function getAbsoluteUri($entity) {
    $uri_info = $entity->uri();
    return url($uri_info['path'], array('absolute' => TRUE));
  }
}
