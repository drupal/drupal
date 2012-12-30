<?php
/**
 * @file
 * Contains RdfMappingEventTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\Core\Cache\DatabaseBackend;
use Drupal\rdf\RdfMappingManager;
use Drupal\rdf\EventSubscriber\MappingSubscriber;
use Drupal\rdf_test_mapping\EventSubscriber\TestMappingSubscriber;
use Drupal\rdf\SiteSchema\BundleSchema;
use Drupal\rdf\SiteSchema\SiteSchema;
use Drupal\rdf\SiteSchema\SiteSchemaManager;
use Drupal\simpletest\WebTestBase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Test the RDF mapping events.
 *
 * This is implemented as a WebTest because it depends on entity info.
 */
class RdfMappingEventTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf', 'rdf_test_mapping', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'RDF mapping tests',
      'description' => 'Tests the event-based RDF mapping system.',
      'group' => 'RDF',
    );
  }

  /**
   * Test that other modules can define incoming type mappings.
   */
  public function testMapInputType() {
    $dispatcher = new EventDispatcher();
    $dispatcher->addSubscriber(new MappingSubscriber());
    $dispatcher->addSubscriber(new TestMappingSubscriber());
    $site_schema_manager = new SiteSchemaManager(new DatabaseBackend('cache'));
    $mapping_manager = new RdfMappingManager($dispatcher, $site_schema_manager);

    // Test that a site schema URI is mapped to itself. This is the default
    // behavior.
    $schema = new SiteSchema(SiteSchema::CONTENT_DEPLOYMENT);
    $bundle_schema = $schema->bundle('entity_test', 'entity_test');
    $site_schema_type = $bundle_schema->getUri();
    $typed_data_ids = $mapping_manager->getTypedDataIdsFromTypeUris(array($site_schema_type));
    $this->assertTrue($typed_data_ids['bundle'] == 'entity_test', 'An internal site schema type URI is properly handled.');

    // Test that a module can map an external URI to a site schema URI.
    $typed_data_ids = $mapping_manager->getTypedDataIdsFromTypeUris(array(TestMappingSubscriber::STAGING_SITE_TYPE_URI));
    $this->assertTrue($typed_data_ids['bundle'] == 'entity_test', 'Modules can map external type URIs to a site schema type.');
  }

}
