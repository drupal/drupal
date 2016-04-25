<?php

namespace Drupal\rdf\Tests;

use Drupal\file\Tests\FileFieldTestBase;
use Drupal\file\Entity\File;

/**
 * Tests the RDFa markup of filefields.
 *
 * @group rdf
 */
class FileFieldAttributesTest extends FileFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf', 'file');

  /**
   * The name of the file field used in the test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The file object used in the test.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * The node object used in the test.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  protected function setUp() {
    parent::setUp();
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $this->fieldName = strtolower($this->randomMachineName());

    $type_name = 'article';
    $this->createFileField($this->fieldName, 'node', $type_name);

    // Set the teaser display to show this field.
    entity_get_display('node', 'article', 'teaser')
      ->setComponent($this->fieldName, array('type' => 'file_default'))
      ->save();

    // Set the RDF mapping for the new field.
    $mapping = rdf_get_mapping('node', 'article');
    $mapping->setFieldMapping($this->fieldName, array('properties' => array('rdfs:seeAlso'), 'mapping_type' => 'rel'))->save();

    $test_file = $this->getTestFile('text');

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $this->fieldName, $type_name);

    $node_storage->resetCache(array($nid));
    $this->node = $node_storage->load($nid);
    $this->file = File::load($this->node->{$this->fieldName}->target_id);
  }

  /**
   * Tests if file fields in teasers have correct resources.
   *
   * Ensure that file fields have the correct resource as the object in RDFa
   * when displayed as a teaser.
   */
  function testNodeTeaser() {
    // Render the teaser.
    $node_render_array = entity_view_multiple(array($this->node), 'teaser');
    $html = \Drupal::service('renderer')->renderRoot($node_render_array);

    // Parses front page where the node is displayed in its teaser form.
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
    $base_uri = \Drupal::url('<front>', [], ['absolute' => TRUE]);
    $parser->parse($graph, $html, 'rdfa', $base_uri);

    $node_uri = $this->node->url('canonical', ['absolute' => TRUE]);
    $file_uri = file_create_url($this->file->getFileUri());

    // Node relation to attached file.
    $expected_value = array(
      'type' => 'uri',
      'value' => $file_uri,
    );
    $this->assertTrue($graph->hasProperty($node_uri, 'http://www.w3.org/2000/01/rdf-schema#seeAlso', $expected_value), 'Node to file relation found in RDF output (rdfs:seeAlso).');
    $this->drupalGet('node');
  }

}
