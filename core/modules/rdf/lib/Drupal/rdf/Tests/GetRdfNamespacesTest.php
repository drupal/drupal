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
  public static function getInfo() {
    return array(
      'name' => 'RDF namespaces',
      'description' => 'Test hook_rdf_namespaces() and ensure only "safe" namespaces are returned.',
      'group' => 'RDF',
    );
  }

  function setUp() {
    parent::setUp('rdf', 'rdf_test');
  }

  /**
   * Test getting RDF namesapces.
   */
  function testGetRdfNamespaces() {
    // Get all RDF namespaces.
    $ns = rdf_get_namespaces();

    $this->assertEqual($ns['rdfs'], 'http://www.w3.org/2000/01/rdf-schema#', t('A prefix declared once is included.'));
    $this->assertEqual($ns['foaf'], 'http://xmlns.com/foaf/0.1/', t('The same prefix declared in several implementations of hook_rdf_namespaces() is valid as long as all the namespaces are the same.'));
    $this->assertEqual($ns['foaf1'], 'http://xmlns.com/foaf/0.1/', t('Two prefixes can be assigned the same namespace.'));
    $this->assertTrue(!isset($ns['dc']), t('A prefix with conflicting namespaces is discarded.'));
  }
}
