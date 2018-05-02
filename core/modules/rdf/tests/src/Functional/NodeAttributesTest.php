<?php

namespace Drupal\Tests\rdf\Functional;

use Drupal\Tests\node\Functional\NodeTestBase;

/**
 * Tests the RDFa markup of Nodes.
 *
 * @group rdf
 */
class NodeAttributesTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['rdf'];

  protected function setUp() {
    parent::setUp();

    rdf_get_mapping('node', 'article')
      ->setBundleMapping([
        'types' => ['sioc:Item', 'foaf:Document'],
      ])
      ->setFieldMapping('title', [
        'properties' => ['dc:title'],
      ])
      ->setFieldMapping('created', [
        'properties' => ['dc:date', 'dc:created'],
        'datatype' => 'xsd:dateTime',
        'datatype_callback' => ['callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value'],
      ])
      ->save();
  }

  /**
   * Creates a node of type article and tests its RDFa markup.
   */
  public function testNodeAttributes() {
    // Create node with single quotation mark title to ensure it does not get
    // escaped more than once.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => $this->randomMachineName(8) . "'",
    ]);

    $node_uri = $node->url('canonical', ['absolute' => TRUE]);
    $base_uri = \Drupal::url('<front>', [], ['absolute' => TRUE]);

    // Parses front page where the node is displayed in its teaser form.
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
    $parser->parse($graph, $this->drupalGet('node/' . $node->id()), 'rdfa', $base_uri);

    // Inspects RDF graph output.
    // Node type.
    $expected_value = [
      'type' => 'uri',
      'value' => 'http://rdfs.org/sioc/ns#Item',
    ];
    $this->assertTrue($graph->hasProperty($node_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Node type found in RDF output (sioc:Item).');
    // Node type.
    $expected_value = [
      'type' => 'uri',
      'value' => 'http://xmlns.com/foaf/0.1/Document',
    ];
    $this->assertTrue($graph->hasProperty($node_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Node type found in RDF output (foaf:Document).');
    // Node title.
    $expected_value = [
      'type' => 'literal',
      'value' => $node->getTitle(),
      'lang' => 'en',
    ];
    $this->assertTrue($graph->hasProperty($node_uri, 'http://purl.org/dc/terms/title', $expected_value), 'Node title found in RDF output (dc:title).');
    // Node date (date format must be UTC).
    $expected_value = [
      'type' => 'literal',
      'value' => \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'custom', 'c', 'UTC'),
      'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime',
    ];
    $this->assertTrue($graph->hasProperty($node_uri, 'http://purl.org/dc/terms/date', $expected_value), 'Node date found in RDF output (dc:date).');
    // Node date (date format must be UTC).
    $expected_value = [
      'type' => 'literal',
      'value' => \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'custom', 'c', 'UTC'),
      'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime',
    ];
    $this->assertTrue($graph->hasProperty($node_uri, 'http://purl.org/dc/terms/created', $expected_value), 'Node date found in RDF output (dc:created).');
  }

}
