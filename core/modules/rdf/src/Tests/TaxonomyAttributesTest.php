<?php

/**
 * @file
 * Contains \Drupal\rdf\Tests\TaxonomyAttributesTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\taxonomy\Tests\TaxonomyTestBase;

/**
 * Tests the RDFa markup of Taxonomy terms.
 *
 * @group rdf
 */
class TaxonomyAttributesTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf', 'views');

  /**
   * Vocabulary created for testing purposes.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  protected function setUp() {
    parent::setUp();

    $this->vocabulary = $this->createVocabulary();

    // RDF mapping - term bundle.
    rdf_get_mapping('taxonomy_term', $this->vocabulary->id())
      ->setBundleMapping(array('types' => array('skos:Concept')))
      ->setFieldMapping('name', array(
        'properties' => array('rdfs:label', 'skos:prefLabel'),
      ))
      ->save();
  }

  /**
   * Creates a random term and ensures the RDF output is correct.
   */
  function testTaxonomyTermRdfaAttributes() {
    $term = $this->createTerm($this->vocabulary);
    $term_uri = $term->url('canonical', ['absolute' => TRUE]);

    // Parses the term's page and checks that the RDF output is correct.
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
    $base_uri = \Drupal::url('<front>', [], ['absolute' => TRUE]);
    $parser->parse($graph, $this->drupalGet('taxonomy/term/' . $term->id()), 'rdfa', $base_uri);

    // Inspects RDF graph output.
    // Term type.
    $expected_value = array(
      'type' => 'uri',
      'value' => 'http://www.w3.org/2004/02/skos/core#Concept',
    );
    $this->assertTrue($graph->hasProperty($term_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Term type found in RDF output (skos:Concept).');
    // Term label.
    $expected_value = array(
      'type' => 'literal',
      'value' => $term->getName(),
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($term_uri, 'http://www.w3.org/2000/01/rdf-schema#label', $expected_value), 'Term label found in RDF output (rdfs:label).');
    // Term label.
    $expected_value = array(
      'type' => 'literal',
      'value' => $term->getName(),
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($term_uri, 'http://www.w3.org/2004/02/skos/core#prefLabel', $expected_value), 'Term label found in RDF output (skos:prefLabel).');

    // @todo Add test for term description once it is a field:
    //   https://www.drupal.org/node/569434.
  }
}
