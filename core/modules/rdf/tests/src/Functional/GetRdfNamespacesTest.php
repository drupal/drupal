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
  protected static $modules = ['rdf', 'rdf_test_namespaces'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests getting RDF namespaces.
   */
  public function testGetRdfNamespaces() {
    // Fetches the front page and extracts RDFa 1.1 prefixes.
    $this->drupalGet('');

    // We have to use the find() method on the driver directly because //html is
    // prepended to all xpath queries otherwise.
    $driver = $this->getSession()->getDriver();

    $element = $driver->find('//html[contains(@prefix, "rdfs: http://www.w3.org/2000/01/rdf-schema#")]');
    $this->assertCount(1, $element, 'A prefix declared once is displayed.');

    $element = $driver->find('//html[contains(@prefix, "foaf: http://xmlns.com/foaf/0.1/")]');
    $this->assertCount(1, $element, 'The same prefix declared in several implementations of hook_rdf_namespaces() is valid as long as all the namespaces are the same.');

    $element = $driver->find('//html[contains(@prefix, "foaf1: http://xmlns.com/foaf/0.1/")]');
    $this->assertCount(1, $element, 'Two prefixes can be assigned the same namespace.');

    $element = $driver->find('//html[contains(@prefix, "dc: http://purl.org/dc/terms/")]');
    $this->assertCount(1, $element, 'When a prefix has conflicting namespaces, the first declared one is used.');

    // Get all RDF namespaces.
    $ns = rdf_get_namespaces();

    $this->assertEquals('http://www.w3.org/2000/01/rdf-schema#', $ns['rdfs'], 'A prefix declared once is included.');
    $this->assertEquals('http://xmlns.com/foaf/0.1/', $ns['foaf'], 'The same prefix declared in several implementations of hook_rdf_namespaces() is valid as long as all the namespaces are the same.');
    $this->assertEquals('http://xmlns.com/foaf/0.1/', $ns['foaf1'], 'Two prefixes can be assigned the same namespace.');

    // Enable rdf_conflicting_namespaces to ensure that an exception is thrown
    // when RDF namespaces are conflicting.
    \Drupal::service('module_installer')->install(['rdf_conflicting_namespaces'], TRUE);
    try {
      $ns = rdf_get_namespaces();
      $this->fail('Expected exception not thrown for conflicting namespace declaration.');
    }
    catch (\Exception $e) {
      // Expected exception; just continue testing.
    }
  }

}
