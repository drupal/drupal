<?php

/**
 * @file
 * Definition of Drupal\rdf\Tests\RdfaMarkupTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests RDFa markup generation.
 */
class RdfaMarkupTest extends WebTestBase {
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'RDFa markup',
      'description' => 'Test RDFa markup generation.',
      'group' => 'RDF',
    );
  }

  function setUp() {
    parent::setUp('rdf', 'field_test', 'rdf_test');
  }

  /**
   * Test rdf_rdfa_attributes().
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
   * Ensure that file fields have the correct resource as the object in RDFa
   * when displayed as a teaser.
   */
  function testAttributesInMarkupFile() {
    // Create a user to post the image.
    $admin_user = $this->drupalCreateUser(array('edit own article content', 'revert revisions', 'administer content types'));
    $this->drupalLogin($admin_user);

    $langcode = LANGUAGE_NOT_SPECIFIED;
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
      'display' => array(
        'teaser' => array(
          'type' => 'file_default',
        ),
      ),
    );
    field_create_instance($instance);

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
    // the uris for the test files as the values.
    $edit = array("files[" . $field_name . "_" . $langcode . "_0]" => drupal_realpath($file->uri),
                  "files[" . $image_field . "_" . $langcode . "_0]" => drupal_realpath($image->uri));

    // Create node and save, then edit node to upload files.
    $node = $this->drupalCreateNode(array('type' => 'article', 'promote' => 1));
    $this->drupalPost('node/' . $node->nid . '/edit', $edit, t('Save'));

    // Get filenames and nid for comparison with HTML output.
    $file_filename = $file->filename;
    $image_filename = $image->filename;
    $nid = $node->nid;
    // Navigate to front page, where node is displayed in teaser form.
    $this->drupalGet('node');

    // We only check to make sure that the resource attribute contains '.txt'
    // instead of the full file name because the filename is altered on upload.
    $file_rel = $this->xpath('//div[contains(@about, :node-uri)]//div[contains(@rel, "rdfs:seeAlso") and contains(@resource, ".txt")]', array(
      ':node-uri' => 'node/' . $nid,
    ));
    $this->assertTrue(!empty($file_rel), t('Attribute \'rel\' set on file field. Attribute \'resource\' is also set.'));
    $image_rel = $this->xpath('//div[contains(@about, :node-uri)]//div[contains(@rel, "rdfs:seeAlso") and contains(@resource, :image)]//img[contains(@typeof, "foaf:Image")]', array(
      ':node-uri' => 'node/' . $nid,
      ':image' => $image_filename,
    ));

    $this->assertTrue(!empty($image_rel), t('Attribute \'rel\' set on image field. Attribute \'resource\' is also set.'));

    // Edits the node to add tags.
    $tag1 = $this->randomName(8);
    $tag2 = $this->randomName(8);
    $edit = array();
    $edit['field_tags[' . LANGUAGE_NOT_SPECIFIED . ']'] = "$tag1, $tag2";
    $this->drupalPost('node/' . $node->nid . '/edit', $edit, t('Save'));
    // Ensures the RDFa markup for the relationship between the node and its
    // tags is correct.
    $term_rdfa_meta = $this->xpath('//div[@about=:node-url and contains(@typeof, "sioc:Item") and contains(@typeof, "foaf:Document")]//ul[@class="links"]/li[@rel="dc:subject"]/a[@typeof="skos:Concept" and text()=:term-name]', array(
      ':node-url' => url('node/' . $node->nid),
      ':term-name' => $tag1,
    ));
    $this->assertTrue(!empty($term_rdfa_meta), t('Property dc:subject is present for the tag1 field item.'));
    $term_rdfa_meta = $this->xpath('//div[@about=:node-url and contains(@typeof, "sioc:Item") and contains(@typeof, "foaf:Document")]//ul[@class="links"]/li[@rel="dc:subject"]/a[@typeof="skos:Concept" and text()=:term-name]', array(
      ':node-url' => url('node/' . $node->nid),
      ':term-name' => $tag2,
    ));
    $this->assertTrue(!empty($term_rdfa_meta), t('Property dc:subject is present for the tag2 field item.'));
  }
}
