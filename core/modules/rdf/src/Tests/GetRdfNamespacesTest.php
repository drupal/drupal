<?php

/**
 * @file
 * Definition of Drupal\rdf\Tests\GetRdfNamespacesTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for RDF namespaces declaration with hook_rdf_namespaces().
 */
class GetRdfNamespacesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf', 'rdf_test_namespaces');

  public static function getInfo() {
    return array(
      'name' => 'RDF namespaces',
      'description' => 'Test hook_rdf_namespaces().',
      'group' => 'RDF',
    );
  }

  /**
   * Tests getting RDF namespaces.
   */
  function testGetRdfNamespaces() {
    // Get all RDF namespaces.
    $ns = rdf_get_namespaces();

    $this->assertEqual($ns['rdfs'], 'http://www.w3.org/2000/01/rdf-schema#', 'A prefix declared once is included.');
    $this->assertEqual($ns['foaf'], 'http://xmlns.com/foaf/0.1/', 'The same prefix declared in several implementations of hook_rdf_namespaces() is valid as long as all the namespaces are the same.');
    $this->assertEqual($ns['foaf1'], 'http://xmlns.com/foaf/0.1/', 'Two prefixes can be assigned the same namespace.');

    // Enable rdf_conflicting_namespaces to ensure that an exception is thrown
    // when RDF namespaces are conflicting.
    \Drupal::moduleHandler()->install(array('rdf_conflicting_namespaces'), TRUE);
    try {
      $ns = rdf_get_namespaces();
      $this->fail('Expected exception not thrown for conflicting namespace declaration.');
    }
    catch (\Exception $e) {
      $this->pass('Expected exception thrown: ' . $e->getMessage());
    }
  }
}
