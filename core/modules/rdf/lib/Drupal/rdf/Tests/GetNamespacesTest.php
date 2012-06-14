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
  public static function getInfo() {
    return array(
      'name' => 'RDF namespaces serialization test',
      'description' => 'Confirm that the serialization of RDF namespaces in present in the HTML markup.',
      'group' => 'RDF',
    );
  }

  function setUp() {
    parent::setUp('rdf', 'rdf_test');
  }

  /**
   * Test RDF namespaces.
   */
  function testGetRdfNamespaces() {
    // Fetches the front page and extracts RDFa 1.1 prefixes.
    $this->drupalGet('');

    $element = $this->xpath('//html[contains(@prefix, :prefix_binding)]', array(
      ':prefix_binding' => 'rdfs: http://www.w3.org/2000/01/rdf-schema#',
    ));
    $this->assertTrue(!empty($element), t('A prefix declared once is displayed.'));

    $element = $this->xpath('//html[contains(@prefix, :prefix_binding)]', array(
      ':prefix_binding' => 'foaf: http://xmlns.com/foaf/0.1/',
    ));
    $this->assertTrue(!empty($element), t('The same prefix declared in several implementations of hook_rdf_namespaces() is valid as long as all the namespaces are the same.'));

    $element = $this->xpath('//html[contains(@prefix, :prefix_binding)]', array(
      ':prefix_binding' => 'foaf1: http://xmlns.com/foaf/0.1/',
    ));
    $this->assertTrue(!empty($element), t('Two prefixes can be assigned the same namespace.'));

    $element = $this->xpath('//html[contains(@prefix, :prefix_binding)]', array(
      ':prefix_binding' => 'dc: ',
    ));
    $this->assertTrue(empty($element), t('A prefix with conflicting namespaces is discarded.'));
  }
}
