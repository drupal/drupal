<?php

namespace Drupal\Tests\rdf\Functional;

use Drupal\Core\Url;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestBase;
use Drupal\Tests\rdf\Traits\RdfParsingTrait;

/**
 * Tests the RDFa markup of Taxonomy terms.
 *
 * @group rdf
 * @group legacy
 */
class TaxonomyAttributesTest extends TaxonomyTestBase {

  use RdfParsingTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['rdf', 'views'];

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
   * Vocabulary created for testing purposes.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->vocabulary = $this->createVocabulary();

    // RDF mapping - term bundle.
    rdf_get_mapping('taxonomy_term', $this->vocabulary->id())
      ->setBundleMapping(['types' => ['skos:Concept']])
      ->setFieldMapping('name', [
        'properties' => ['rdfs:label', 'skos:prefLabel'],
      ])
      ->save();

    // Prepares commonly used URIs.
    $this->baseUri = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
  }

  /**
   * Creates a random term and ensures the RDF output is correct.
   */
  public function testTaxonomyTermRdfaAttributes() {
    $term = $this->createTerm($this->vocabulary);
    $term_uri = $term->toUrl('canonical', ['absolute' => TRUE])->toString();

    $this->drupalGet('taxonomy/term/' . $term->id());
    $this->drupalGet($term->toUrl());

    // Inspects RDF graph output.
    // Term type.
    $expected_value = [
      'type' => 'uri',
      'value' => 'http://www.w3.org/2004/02/skos/core#Concept',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $term_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Term type found in RDF output (skos:Concept).');
    // Term label.
    $expected_value = [
      'type' => 'literal',
      'value' => $term->getName(),
      'lang' => 'en',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $term_uri, 'http://www.w3.org/2000/01/rdf-schema#label', $expected_value), 'Term label found in RDF output (rdfs:label).');

    // Term label.
    $expected_value = [
      'type' => 'literal',
      'value' => $term->getName(),
      'lang' => 'en',
    ];
    $this->assertTrue($this->hasRdfProperty($this->getSession()->getPage()->getContent(), $this->baseUri, $term_uri, 'http://www.w3.org/2004/02/skos/core#prefLabel', $expected_value), 'Term label found in RDF output (skos:prefLabel).');

    // @todo Add test for term description once it is a field:
    //   https://www.drupal.org/node/569434.
  }

}
