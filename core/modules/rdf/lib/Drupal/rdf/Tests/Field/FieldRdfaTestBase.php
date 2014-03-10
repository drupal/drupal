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
  public static $modules = array('rdf');

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
   * @param string $formatter
   *   The machine name of the formatter to test.
   * @param string $property
   *   The property that should be found.
   * @param string $value
   *   The expected value of the property.
   * @param string $object_type
   *   The object's type, either 'uri' or 'literal'.
   * @param string $datatype
   *   The data type of the property.
   */
  protected function assertFormatterRdfa($formatter, $property, $value, $object_type = 'literal', $datatype = '') {
    // The field formatter will be rendered inside the entity. Set the field
    // formatter in the entity display options before rendering the entity.
    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent($this->fieldName, array('type' => $formatter))
      ->save();
    $build = entity_view($this->entity, 'default');
    $output = drupal_render($build);
    $graph = new \EasyRdf_Graph($this->uri, $output, 'rdfa');
    $expected_value = array(
      'type' => $object_type,
      'value' => $value,
    );
    if ($datatype) {
      $expected_value['datatype'] = $datatype;
    }
    $this->assertTrue($graph->hasProperty($this->uri, $property, $expected_value), "Formatter $formatter exposes data correctly for {$this->fieldType} fields.");
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
