<?php

/**
 * @file
 * Contains Drupal\rdf\Tests\TaxonomyAttributesTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\taxonomy\Tests\TaxonomyTestBase;

/**
 * Tests the RDFa markup of Taxonomy terms.
 */
class TaxonomyAttributesTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf');

  public static function getInfo() {
    return array(
      'name' => 'RDFa markup for taxonomy terms',
      'description' => 'Tests the RDFa markup of taxonomy terms.',
      'group' => 'RDF',
    );
  }

  /**
   * Creates a random term and ensures the RDF output is correct.
   */
  function testTaxonomyTermRdfaAttributes() {
    $vocabulary = $this->createVocabulary();
    $term = $this->createTerm($vocabulary);
    $term_uri = url('taxonomy/term/' . $term->id(), array('absolute' => TRUE));

    // Parses the term's page and checks that the RDF output is correct.
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
    $base_uri = url('<front>', array('absolute' => TRUE));
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
      'value' => $term->label(),
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($term_uri, 'http://www.w3.org/2000/01/rdf-schema#label', $expected_value), 'Term label found in RDF output (rdfs:label).');
    // Term label.
    $expected_value = array(
      'type' => 'literal',
      'value' => $term->label(),
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($term_uri, 'http://www.w3.org/2004/02/skos/core#prefLabel', $expected_value), 'Term label found in RDF output (skos:prefLabel).');
  }
}
