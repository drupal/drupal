<?php

namespace Drupal\Tests\rdf\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests hook_rdf_namespaces().
 *
 * @group rdf
 */
class GetRdfNamespacesTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['rdf', 'rdf_test_namespaces'];

  /**
   * Tests getting RDF namespaces.
   */
  public function testGetRdfNamespaces() {
    // Get all RDF namespaces.
    $ns = rdf_get_namespaces();

    $this->assertEqual($ns['rdfs'], 'http://www.w3.org/2000/01/rdf-schema#', 'A prefix declared once is included.');
    $this->assertEqual($ns['foaf'], 'http://xmlns.com/foaf/0.1/', 'The same prefix declared in several implementations of hook_rdf_namespaces() is valid as long as all the namespaces are the same.');
    $this->assertEqual($ns['foaf1'], 'http://xmlns.com/foaf/0.1/', 'Two prefixes can be assigned the same namespace.');

    // Enable rdf_conflicting_namespaces to ensure that an exception is thrown
    // when RDF namespaces are conflicting.
    \Drupal::service('module_installer')->install(['rdf_conflicting_namespaces'], TRUE);
    try {
      $ns = rdf_get_namespaces();
      $this->fail('Expected exception not thrown for conflicting namespace declaration.');
    }
    catch (\Exception $e) {
      $this->pass('Expected exception thrown: ' . $e->getMessage());
    }
  }

}
