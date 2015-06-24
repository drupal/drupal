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
   * TRUE if verbose debugging is enabled.
   *
   * @var bool
   */
  protected $debug = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf');

  /**
   * @var string
   */
  protected $testValue;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

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
    $output = \Drupal::service('renderer')->renderRoot($build);
    $graph = new \EasyRdf_Graph($this->uri, $output, 'rdfa');
    $this->setRawContent($output);

    // If verbose debugging is turned on, display the HTML and parsed RDF
    // in the results.
    if ($this->debug) {
      debug($output);
      debug($graph->toRdfPhp());
    }

    $this->assertTrue($graph->hasProperty($this->uri, $property, $expected_rdf_value), "Formatter {$formatter['type']} exposes data correctly for {$this->fieldType} fields.");
  }

  /**
   * Creates the field for testing.
   *
   * @param array $field_settings
   *   (optional) An array of field settings.
   */
  protected function createTestField($field_settings = array()) {
    entity_create('field_storage_config', array(
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => $this->fieldType,
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'bundle' => 'entity_test',
      'settings' => $field_settings,
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

  /**
   * Parses a content and return the html element.
   *
   * @param string $content
   *   The html to parse.
   *
   * @return array
   *   An array containing simplexml objects.
   */
  protected function parseContent($content) {
    $htmlDom = new \DOMDocument();
    @$htmlDom->loadHTML('<?xml encoding="UTF-8">' . $content);
    $elements = simplexml_import_dom($htmlDom);

    return $elements;
  }

  /**
   * Performs an xpath search on a certain content.
   *
   * The search is relative to the root element of the $content variable.
   *
   * @param string $content
   *   The html to parse.
   * @param string $xpath
   *   The xpath string to use in the search.
   * @param array $arguments
   *   Some arguments for the xpath.
   *
   * @return array|FALSE
   *   The return value of the xpath search. For details on the xpath string
   *   format and return values see the SimpleXML documentation,
   *   http://php.net/manual/function.simplexml-element-xpath.php.
   */
  protected function xpathContent($content, $xpath, array $arguments = array()) {
    if ($elements = $this->parseContent($content)) {
      $xpath = $this->buildXPathQuery($xpath, $arguments);
      $result = $elements->xpath($xpath);
      // Some combinations of PHP / libxml versions return an empty array
      // instead of the documented FALSE. Forcefully convert any falsish values
      // to an empty array to allow foreach(...) constructions.
      return $result ? $result : array();
    }
    else {
      return FALSE;
    }
  }

}
