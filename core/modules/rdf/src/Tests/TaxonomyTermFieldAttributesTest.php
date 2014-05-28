<?php

/**
 * @file
 * Contains \Drupal\rdf\Tests\TaxonomyTermFieldAttributesTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\taxonomy\Tests\TaxonomyTestBase;

/**
 * Tests RDFa markup generation for taxonomy term fields.
 */
class TaxonomyTermFieldAttributesTest extends TaxonomyTestBase {

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

  public static function getInfo() {
    return array(
      'name' => 'RDFa markup for taxonomy term fields',
      'description' => 'Tests the RDFa markup of taxonomy term fields.',
      'group' => 'RDF',
    );
  }

  public function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('bypass node access', 'administer taxonomy'));
    $this->drupalLogin($web_user);
    $this->vocabulary = $this->createVocabulary();

    // Setup a field and instance.
    $this->fieldName = 'field_taxonomy_test';

    // Create the field.
    $this->createTaxonomyTermReferenceField($this->fieldName, $this->vocabulary);

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
      ->setComponent($this->fieldName, array('type' => 'taxonomy_term_reference_link'))
      ->save();

    // Create a term in each vocabulary.
    $term1 = $this->createTerm($this->vocabulary);
    $term2 = $this->createTerm($this->vocabulary);
    $taxonomy_term_1_uri = url('taxonomy/term/' . $term1->id(), array('absolute' => TRUE));
    $taxonomy_term_2_uri = url('taxonomy/term/' . $term2->id(), array('absolute' => TRUE));

    // Create the node.
    $node = $this->drupalCreateNode(array('type' => 'article'));
    $node->set($this->fieldName, array(
      array('target_id' => $term1->id()),
      array('target_id' => $term2->id()),
    ));

    // Render the node.
    $node_render_array = entity_view_multiple(array($node), 'teaser');
    $html = drupal_render($node_render_array);

    // Parse the teaser.
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
    $base_uri = url('<front>', array('absolute' => TRUE));
    $parser->parse($graph, $html, 'rdfa', $base_uri);

    // Node relations to taxonomy terms.
    $node_uri = url('node/' . $node->id(), array('absolute' => TRUE));
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
    // @todo enable with https://drupal.org/node/2072791
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

  /**
   * Create the taxonomy term reference field for testing.
   *
   * @param string $field_name
   *   The name of the field to create.
   * @param \Drupal\taxonomy\Entity\Vocabulary $vocabulary
   *   The vocabulary that the field should use.
   *
   * @todo Move this to TaxonomyTestBase, like the other field modules.
   */
  protected function createTaxonomyTermReferenceField($field_name, $vocabulary) {
    entity_create('field_config', array(
      'name' => $field_name,
      'entity_type' => 'node',
      'type' => 'taxonomy_term_reference',
      'cardinality' => FieldDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $vocabulary->id(),
            'parent' => '0',
          ),
        ),
      ),
    ))->save();
    entity_create('field_instance_config', array(
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'article',
    ))->save();
    entity_get_form_display('node', 'article', 'default')
      ->setComponent($field_name, array('type' => 'options_select'))
      ->save();
    entity_get_display('node', 'article', 'full')
      ->setComponent($field_name, array('type' => 'taxonomy_term_reference_link'))
      ->save();
  }

}
