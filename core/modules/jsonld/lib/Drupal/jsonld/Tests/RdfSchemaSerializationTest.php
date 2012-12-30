<?php
/**
 * @file
 * Contains RdfSchemaSerializationTest.
 */

namespace Drupal\jsonld\Tests;

use Drupal\jsonld\JsonldRdfSchemaNormalizer;
use Drupal\jsonld\JsonldEncoder;
use Drupal\rdf\SiteSchema\BundleSchema;
use Drupal\rdf\SiteSchema\SiteSchema;
use Drupal\simpletest\DrupalUnitTestBase;
use Symfony\Component\Serializer\Serializer;

class RdfSchemaSerializationTest extends DrupalUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Site schema JSON-LD serialization',
      'description' => 'Tests the JSON-LD serialization of the RDF site schema.',
      'group' => 'JSON-LD',
    );
  }

  /**
   * Tests the serialization of site schemas.
   */
  function testSchemaSerialization() {
    // In order to use url() the url_alias table must be installed, so system
    // is enabled.
    $this->enableModules(array('system'));

    $entity_type = $bundle = 'entity_test';

    // Set up the bundle schema for the entity_test bundle.
    $schema = new SiteSchema(SiteSchema::CONTENT_DEPLOYMENT);
    $bundle_schema = $schema->bundle($entity_type, $bundle);
    // Set up the serializer.
    $setup_helper = new JsonldTestSetupHelper();
    $normalizer = new JsonldRdfSchemaNormalizer($setup_helper->getSiteSchemaManager(), $setup_helper->getRdfMappingManager());
    $serializer = new Serializer(array($normalizer), array(new JsonldEncoder()));

    $serialized = $serializer->serialize($bundle_schema, 'jsonld');
    $decoded = json_decode($serialized);
    $parsed_term = $decoded[0];

    $this->assertEqual($parsed_term->{'@id'}, $bundle_schema->getUri(), 'JSON-LD for schema term uses correct @id.');
    $this->assertEqual($parsed_term->{'@type'}, 'http://www.w3.org/2000/01/rdf-schema#class', 'JSON-LD for schema term uses correct @type.');
    // The @id and @type should be placed in the beginning of the array.
    $array_keys = array_keys((array) $parsed_term);
    $this->assertEqual(array('@id', '@type'), array_slice($array_keys, 0, 2), 'JSON-LD keywords are placed before other properties.');
    $this->assertTrue(isset($parsed_term->{'http://www.w3.org/2000/01/rdf-schema#isDefinedBy'}), 'Other properties of the term are included.');
  }
}
