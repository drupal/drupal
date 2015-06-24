<?php

/**
 * @file
 * Contains \Drupal\rdf\Tests\ImageFieldAttributesTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\image\Entity\ImageStyle;
use Drupal\image\Tests\ImageFieldTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests the RDFa markup of imagefields.
 *
 * @group rdf
 */
class ImageFieldAttributesTest extends ImageFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf', 'image');

  /**
   * The name of the image field used in the test.
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

    $this->fieldName = 'field_image';

    // Create the image field.
    $this->createImageField($this->fieldName, 'article');

    // Set the RDF mapping for the new field.
    rdf_get_mapping('node', 'article')
      ->setFieldMapping($this->fieldName, array(
        'properties' => array('og:image'),
        'mapping_type' => 'rel',
      ))
      ->setBundleMapping(array('types' => array()))
      ->save();

    // Get the test image that simpletest provides.
    $image = current($this->drupalGetTestFiles('image'));

    // Save a node with the image.
    $nid = $this->uploadNodeImage($image, $this->fieldName, 'article', $this->randomMachineName());
    $this->node = Node::load($nid);
    $this->file = file_load($this->node->{$this->fieldName}->target_id);
  }

  /**
   * Tests that image fields in teasers have correct resources.
   */
  function testNodeTeaser() {
    // Set the display options for the teaser.
    $display_options = array(
      'type' => 'image',
      'settings' => array('image_style' => 'medium', 'image_link' => 'content'),
    );
    $display = entity_get_display('node', 'article', 'teaser');
    $display->setComponent($this->fieldName, $display_options)
      ->save();

    // Render the teaser.
    $node_render_array = node_view($this->node, 'teaser');
    $html = \Drupal::service('renderer')->renderRoot($node_render_array);

    // Parse the teaser.
    $parser = new \EasyRdf_Parser_Rdfa();
    $graph = new \EasyRdf_Graph();
    $base_uri = \Drupal::url('<front>', [], ['absolute' => TRUE]);
    $parser->parse($graph, $html, 'rdfa', $base_uri);

    // Construct the node and image URIs for testing.
    $node_uri = $this->node->url('canonical', ['absolute' => TRUE]);
    $image_uri = ImageStyle::load('medium')->buildUrl($this->file->getFileUri());

    // Test relations from node to image.
    $expected_value = array(
      'type' => 'uri',
      'value' => $image_uri,
    );
    $this->assertTrue($graph->hasProperty($node_uri, 'http://ogp.me/ns#image', $expected_value), 'Node to file relation found in RDF output (og:image).');

    // Test image type.
    $expected_value = array(
      'type' => 'uri',
      'value' => 'http://xmlns.com/foaf/0.1/Image',
    );
    $this->assertTrue($graph->hasProperty($image_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', $expected_value), 'Image type found in RDF output (foaf:Image).');
  }

}
