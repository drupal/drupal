<?php

/**
 * @file
 * Contains SiteSchemaTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\rdf\SiteSchema\BundleSchema;
use Drupal\rdf\SiteSchema\SiteSchema;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for RDF namespaces XML serialization.
 */
class SiteSchemaTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'RDF site schema test',
      'description' => 'Confirm that site-generated schemas are created for entity, bundle, field, and field property.',
      'group' => 'RDF',
    );
  }

  /**
   * Tests site-generated schema.
   */
  function testSiteSchema() {
    $entity_type = $bundle = 'entity_test';
    $schema = new SiteSchema(SiteSchema::SYNDICATION);
    $schema_path = 'site-schema/syndication/';

    // Bundle.
    $bundle_schema = $schema->bundle($entity_type, $bundle);
    $bundle_uri = url("$schema_path$entity_type/$bundle", array('absolute' => TRUE));
    $bundle_properties = array(
      'http://www.w3.org/2000/01/rdf-schema#isDefinedBy' => url($schema_path, array('absolute' => TRUE)),
      'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' => 'http://www.w3.org/2000/01/rdf-schema#class',
      'http://www.w3.org/2000/01/rdf-schema#subClassOf' => url("$schema_path$entity_type", array('absolute' => TRUE)),
    );
    $this->assertEqual($bundle_schema->getUri(), $bundle_uri, 'Bundle term URI is generated correctly.');
    $this->assertEqual($bundle_schema->getProperties(), $bundle_properties, 'Bundle term properties are generated correctly.');
  }

}
