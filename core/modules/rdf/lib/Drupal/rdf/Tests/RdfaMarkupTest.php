<?php

/**
 * @file
 * Definition of Drupal\rdf\Tests\RdfaMarkupTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests RDFa markup generation.
 */
class RdfaMarkupTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf', 'field_test', 'rdf_test');

  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'RDFa markup',
      'description' => 'Test RDFa markup generation.',
      'group' => 'RDF',
    );
  }

  /**
   * Tests rdf_rdfa_attributes().
   */
  function testDrupalRdfaAttributes() {
    // Same value as the one in the HTML tag (no callback function).
    $expected_attributes = array(
      'property' => array('dc:title'),
    );
    $mapping = rdf_mapping_load('test_entity', 'test_bundle');
    $attributes = rdf_rdfa_attributes($mapping['title']);
    ksort($expected_attributes);
    ksort($attributes);
    $this->assertEqual($expected_attributes, $attributes);

    // Value different from the one in the HTML tag (callback function).
    $date = 1252750327;
    $isoDate = date('c', $date);
    $expected_attributes = array(
      'datatype' => 'xsd:dateTime',
      'property' => array('dc:created'),
      'content' => $isoDate,
    );
    $mapping = rdf_mapping_load('test_entity', 'test_bundle');
    $attributes = rdf_rdfa_attributes($mapping['created'], $date);
    ksort($expected_attributes);
    ksort($attributes);
    $this->assertEqual($expected_attributes, $attributes);

    // Same value as the one in the HTML tag with datatype.
    $expected_attributes = array(
      'datatype' => 'foo:bar1type',
      'property' => array('foo:bar1'),
    );
    $mapping = rdf_mapping_load('test_entity', 'test_bundle');
    $attributes = rdf_rdfa_attributes($mapping['foobar1']);
    ksort($expected_attributes);
    ksort($attributes);
    $this->assertEqual($expected_attributes, $attributes);

    // ObjectProperty mapping (rel).
    $expected_attributes = array(
      'rel' => array('sioc:has_creator', 'dc:creator'),
    );
    $mapping = rdf_mapping_load('test_entity', 'test_bundle');
    $attributes = rdf_rdfa_attributes($mapping['foobar_objproperty1']);
    ksort($expected_attributes);
    ksort($attributes);
    $this->assertEqual($expected_attributes, $attributes);

    // Inverse ObjectProperty mapping (rev).
    $expected_attributes = array(
      'rev' => array('sioc:reply_of'),
    );
    $mapping = rdf_mapping_load('test_entity', 'test_bundle');
    $attributes = rdf_rdfa_attributes($mapping['foobar_objproperty2']);
    ksort($expected_attributes);
    ksort($attributes);
    $this->assertEqual($expected_attributes, $attributes);
  }

  /**
   * Tests if file fields in teasers have correct resources.
   *
   * Ensure that file fields have the correct resource as the object in RDFa
   * when displayed as a teaser.
   */
  function testAttributesInMarkupFile() {
    // Create a user to post the image.
    $admin_user = $this->drupalCreateUser(array('edit own article content', 'revert article revisions', 'administer content types'));
    $this->drupalLogin($admin_user);

    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $bundle_name = "article";

    $field_name = 'file_test';
    $field = array(
      'field_name' => $field_name,
      'type' => 'file',
    );
    field_create_field($field);
    $instance = array(
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $bundle_name,
    );
    field_create_instance($instance);

    entity_get_form_display('node', $bundle_name, 'default')
      ->setComponent($field_name, array(
        'type' => 'file_generic',
      ))
      ->save();
    entity_get_display('node', $bundle_name, 'teaser')
      ->setComponent($field_name, array(
        'type' => 'file_default',
      ))
      ->save();

    // Set the RDF mapping for the new field.
    $rdf_mapping = rdf_mapping_load('node', $bundle_name);
    $rdf_mapping += array($field_name => array('predicates' => array('rdfs:seeAlso'), 'type' => 'rel'));
    $rdf_mapping_save = array('mapping' => $rdf_mapping, 'type' => 'node', 'bundle' => $bundle_name);
    rdf_mapping_save($rdf_mapping_save);

    // Get the test file that simpletest provides.
    $file = current($this->drupalGetTestFiles('text'));

    // Prepare image variables.
    $image_field = "field_image";
    // Get the test image that simpletest provides.
    $image = current($this->drupalGetTestFiles('image'));

    // Create an array for drupalPost with the field names as the keys and
    // the URIs for the test files as the values.
    $edit = array("files[" . $field_name . "_" . $langcode . "_0]" => drupal_realpath($file->uri),
                  "files[" . $image_field . "_" . $langcode . "_0]" => drupal_realpath($image->uri));

    // Create node and save, then edit node to upload files.
    $node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1));
    $this->drupalPost('node/' . $node->nid . '/edit', $edit, t('Save'));

    // Prepares filenames for lookup in RDF graph.
    $node = node_load($node->nid);
    $node_uri = url('node/' . $node->nid, array('absolute' => TRUE));
    $file_uri = file_create_url(file_load($node->file_test['und'][0]['fid'])->uri);
    $image_uri = image_style_url('medium', file_load($node->field_image['und'][0]['fid'])->uri);
    $base_uri = url('<front>', array('absolute' => TRUE));

    // Edits the node to add tags.
    $tag1 = $this->randomName(8);
    $tag2 = $this->randomName(8);
    $edit = array();
    $edit['field_tags[' . Language::LANGCODE_NOT_SPECIFIED . ']'] = "$tag1, $tag2";
    $this->drupalPost('node/' . $node->nid . '/edit', $edit, t('Save'));
    $term_1_id = key(taxonomy_term_load_multiple_by_name($tag1));
    $taxonomy_term_1_uri = url('taxonomy/term/' . $term_1_id, array('absolute' => TRUE));
    $term_2_id = key(taxonomy_term_load_multiple_by_name($tag2));
    $taxonomy_term_2_uri = url('taxonomy/term/' . $term_2_id, array('absolute' => TRUE));

    // Parses front page where the node is displayed in its teaser form.
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
    $parser->parse($graph, $this->drupalGet('node'), 'rdfa', $base_uri);
    $rdf_output = $graph->toArray();

    // Inspects RDF graph output.
    // Node relations to attached file.
    $expected_value = array(
      'type' => 'uri',
      'value' => $file_uri,
    );
    $this->assertTrue($graph->hasProperty($node_uri, 'http://www.w3.org/2000/01/rdf-schema#seeAlso', $expected_value), 'Node to file relation found in RDF output (rdfs:seeAlso).');

    // Node relations to attached image.
    $expected_value = array(
      'type' => 'uri',
      'value' => $image_uri,
    );
    $this->assertTrue($graph->hasProperty($node_uri, 'http://www.w3.org/2000/01/rdf-schema#seeAlso', $expected_value), 'Node to file relation found in RDF output (rdfs:seeAlso).');
    $expected_value = array(
      'type' => 'uri',
      'value' => $image_uri,
    );
    $this->assertTrue($graph->hasProperty($node_uri, 'http://ogp.me/ns#image', $expected_value), 'Node to file relation found in RDF output (og:image).');
    // Image type.
    $expected_value = array(
      'type' => 'uri',
      'value' => 'http://xmlns.com/foaf/0.1/Image',
    );
    $this->assertTrue($graph->hasProperty($image_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Image type found in RDF output (foaf:Image).');

    // Node relations to taxonomy terms.
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
    $this->assertTrue($graph->hasProperty($taxonomy_term_1_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Taxonomy term type found in RDF output (skos:Concept).');
    $expected_value = array(
      'type' => 'literal',
      'value' => $tag1,
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($taxonomy_term_1_uri, 'http://www.w3.org/2000/01/rdf-schema#label', $expected_value), 'Taxonomy term name found in RDF output (rdfs:label).');
    $expected_value = array(
      'type' => 'literal',
      'value' => $tag1,
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($taxonomy_term_1_uri, 'http://www.w3.org/2004/02/skos/core#prefLabel', $expected_value), 'Taxonomy term name found in RDF output (skos:prefLabel).');
    // Term 2.
    $expected_value = array(
      'type' => 'uri',
      'value' => 'http://www.w3.org/2004/02/skos/core#Concept',
    );
    $this->assertTrue($graph->hasProperty($taxonomy_term_2_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Taxonomy term type found in RDF output (skos:Concept).');
    $expected_value = array(
      'type' => 'literal',
      'value' => $tag2,
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($taxonomy_term_2_uri, 'http://www.w3.org/2000/01/rdf-schema#label', $expected_value), 'Taxonomy term name found in RDF output (rdfs:label).');
    $expected_value = array(
      'type' => 'literal',
      'value' => $tag2,
      'lang' => 'en',
    );
    $this->assertTrue($graph->hasProperty($taxonomy_term_2_uri, 'http://www.w3.org/2004/02/skos/core#prefLabel', $expected_value), 'Taxonomy term name found in RDF output (skos:prefLabel).');
  }
}
