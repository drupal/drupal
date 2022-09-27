<?php

namespace Drupal\Tests\rdf\Functional;

use Drupal\Core\Url;
use Drupal\Tests\node\Functional\NodeTestBase;
use Drupal\Tests\rdf\Traits\RdfParsingTrait;

/**
 * Tests the RDFa markup of Nodes.
 *
 * @group rdf
 * @group legacy
 */
class NodeAttributesTest extends NodeTestBase {

  use RdfParsingTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['rdf'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * URI of the front page of the Drupal site.
   *
   * @var string
   */
  protected $baseUri;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
    // Prepares commonly used URIs.
    $this->baseUri = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
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
    $node_uri = $node->toUrl('canonical', ['absolute' => TRUE])->toString();

    $this->drupalGet($node->toUrl());
    // Inspects RDF graph output.
    // Node type.
    $expected_value = [
      'type' => 'uri',
      'value' => 'http://rdfs.org/sioc/ns#Item',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $node_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Node type found in RDF output (sioc:Item).');
    // Node type.
    $expected_value = [
      'type' => 'uri',
      'value' => 'http://xmlns.com/foaf/0.1/Document',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $node_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Node type found in RDF output (foaf:Document).');
    // Node title.
    $expected_value = [
      'type' => 'literal',
      'value' => $node->getTitle(),
      'lang' => 'en',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $node_uri, 'http://purl.org/dc/terms/title', $expected_value), 'Node title found in RDF output (dc:title).');
    // Node date (date format must be UTC).
    $expected_value = [
      'type' => 'literal',
      'value' => \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'custom', 'c', 'UTC'),
      'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $node_uri, 'http://purl.org/dc/terms/date', $expected_value), 'Node date found in RDF output (dc:date).');
    // Node date (date format must be UTC).
    $expected_value = [
      'type' => 'literal',
      'value' => \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'custom', 'c', 'UTC'),
      'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $node_uri, 'http://purl.org/dc/terms/created', $expected_value), 'Node date found in RDF output (dc:created).');
  }

}
