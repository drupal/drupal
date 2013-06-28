<?php

/**
 * @file
 * Definition of Drupal\rdf\Tests\GetNamespacesTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for RDF namespaces XML serialization.
 */
class GetNamespacesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf', 'rdf_test_namespaces');

  public static function getInfo() {
    return array(
      'name' => 'RDF namespaces serialization test',
      'description' => 'Confirm that the serialization of RDF namespaces in present in the HTML markup.',
      'group' => 'RDF',
    );
  }

  /**
   * Tests RDF namespaces.
   */
  function testGetRdfNamespaces() {
    // Fetches the front page and extracts RDFa 1.1 prefixes.
    $this->drupalGet('');

    $element = $this->xpath('//html[contains(@prefix, :prefix_binding)]', array(
      ':prefix_binding' => 'rdfs: http://www.w3.org/2000/01/rdf-schema#',
    ));
    $this->assertTrue(!empty($element), 'A prefix declared once is displayed.');

    $element = $this->xpath('//html[contains(@prefix, :prefix_binding)]', array(
      ':prefix_binding' => 'foaf: http://xmlns.com/foaf/0.1/',
    ));
    $this->assertTrue(!empty($element), 'The same prefix declared in several implementations of hook_rdf_namespaces() is valid as long as all the namespaces are the same.');

    $element = $this->xpath('//html[contains(@prefix, :prefix_binding)]', array(
      ':prefix_binding' => 'foaf1: http://xmlns.com/foaf/0.1/',
    ));
    $this->assertTrue(!empty($element), 'Two prefixes can be assigned the same namespace.');

    $element = $this->xpath('//html[contains(@prefix, :prefix_binding)]', array(
      ':prefix_binding' => 'dc: http://purl.org/dc/terms/',
    ));
    $this->assertTrue(!empty($element), 'When a prefix has conflicting namespaces, the first declared one is used.');
  }
}
