<?php

namespace Drupal\rdf\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\taxonomy\Tests\TaxonomyTestBase;

/**
 * Tests RDFa markup generation for taxonomy term fields.
 *
 * @group rdf
 */
class EntityReferenceFieldAttributesTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf', 'field_test', 'file', 'image');

  /**
   * The name of the taxonomy term reference field used in the test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The vocabulary object used in the test.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('bypass node access', 'administer taxonomy'));
    $this->drupalLogin($web_user);
    $this->vocabulary = $this->createVocabulary();

    // Create the field.
    $this->fieldName = 'field_taxonomy_test';
    $handler_settings = array(
      'target_bundles' => array(
        $this->vocabulary->id() => $this->vocabulary->id(),
      ),
      'auto_create' => TRUE,
    );
    $this->createEntityReferenceField('node', 'article', $this->fieldName, 'Tags', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    entity_get_form_display('node', 'article', 'default')
      ->setComponent($this->fieldName, array('type' => 'options_select'))
      ->save();
    entity_get_display('node', 'article', 'full')
      ->setComponent($this->fieldName, array('type' => 'entity_reference_label'))
      ->save();

    // Set the RDF mapping for the new field.
    rdf_get_mapping('node', 'article')
      ->setFieldMapping($this->fieldName, array(
        'properties' => array('dc:subject'),
        'mapping_type' => 'rel',
      ))
      ->save();

    rdf_get_mapping('taxonomy_term', $this->vocabulary->id())
      ->setBundleMapping(array('types' => array('skos:Concept')))
      ->setFieldMapping('name', array('properties' => array('rdfs:label')))
      ->save();
  }

  /**
   * Tests if file fields in teasers have correct resources.
   *
   * Ensure that file fields have the correct resource as the object in RDFa
   * when displayed as a teaser.
   */
  function testNodeTeaser() {
    // Set the teaser display to show this field.
    entity_get_display('node', 'article', 'teaser')
      ->setComponent($this->fieldName, array('type' => 'entity_reference_label'))
      ->save();

    // Create a term in each vocabulary.
    $term1 = $this->createTerm($this->vocabulary);
    $term2 = $this->createTerm($this->vocabulary);
    $taxonomy_term_1_uri = $term1->url('canonical', ['absolute' => TRUE]);
    $taxonomy_term_2_uri = $term2->url('canonical', ['absolute' => TRUE]);

    // Create the node.
    $node = $this->drupalCreateNode(array('type' => 'article'));
    $node->set($this->fieldName, array(
      array('target_id' => $term1->id()),
      array('target_id' => $term2->id()),
    ));

    // Render the node.
    $node_render_array = entity_view_multiple(array($node), 'teaser');
    $html = \Drupal::service('renderer')->renderRoot($node_render_array);

    // Parse the teaser.
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
    $base_uri = \Drupal::url('<front>', [], ['absolute' => TRUE]);
    $parser->parse($graph, $html, 'rdfa', $base_uri);

    // Node relations to taxonomy terms.
    $node_uri = $node->url('canonical', ['absolute' => TRUE]);
    $expected_value = array(
      'type' => 'uri',
      'value' => $taxonomy_term_1_uri,
    );
    $this->assertTrue($graph->hasProperty($node_uri, 'http://purl.org/dc/terms/subject', $expected_value), 'Node to term relation found in RDF output (dc:subject).');
    $expected_value = array(
      'type' => 'uri',
      'value' => $taxonomy_term_2_uri,
    );
    $this->assertTrue($graph->hasProperty($node_uri, 'http://purl.org/dc/terms/subject', $expected_value), 'Node to term relation found in RDF output (dc:subject).');
    // Taxonomy terms triples.
    // Term 1.
    $expected_value = array(
      'type' => 'uri',
      'value' => 'http://www.w3.org/2004/02/skos/core#Concept',
    );
    // @todo Enable with https://www.drupal.org/node/2072791.
    //$this->assertTrue($graph->hasProperty($taxonomy_term_1_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Taxonomy term type found in RDF output (skos:Concept).');
    $expected_value = array(
      'type' => 'literal',
      'value' => $term1->getName(),
    );
    //$this->assertTrue($graph->hasProperty($taxonomy_term_1_uri, 'http://www.w3.org/2000/01/rdf-schema#label', $expected_value), 'Taxonomy term name found in RDF output (rdfs:label).');
    // Term 2.
    $expected_value = array(
      'type' => 'uri',
      'value' => 'http://www.w3.org/2004/02/skos/core#Concept',
    );
    //$this->assertTrue($graph->hasProperty($taxonomy_term_2_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Taxonomy term type found in RDF output (skos:Concept).');
    $expected_value = array(
      'type' => 'literal',
      'value' => $term2->getName(),
    );
    //$this->assertTrue($graph->hasProperty($taxonomy_term_2_uri, 'http://www.w3.org/2000/01/rdf-schema#label', $expected_value), 'Taxonomy term name found in RDF output (rdfs:label).');
  }

}
