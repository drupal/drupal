<?php

namespace Drupal\Tests\rest\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\rest\RestResourceConfigInterface;

/**
 * Tests that rest.settings is converted to rest_resource_config entities.
 *
 * @see https://www.drupal.org/node/2308745
 * @see rest_update_8201()
 * @see rest_post_update_create_rest_resource_config_entities()
 *
 * @group rest
 */
class RestConfigurationEntitiesUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['rest', 'serialization'];

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.rest-rest_update_8201.php',
    ];
  }

  /**
   * Tests rest_update_8201().
   */
  public function testResourcesConvertedToConfigEntities() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $resource_config_storage */
    $resource_config_storage = $this->container->get('entity_type.manager')->getStorage('rest_resource_config');

    // Make sure we have the expected values before the update.
    $rest_settings = $this->config('rest.settings');
    $this->assertTrue(array_key_exists('resources', $rest_settings->getRawData()));
    $this->assertTrue(array_key_exists('entity:node', $rest_settings->getRawData()['resources']));
    $resource_config_entities = $resource_config_storage->loadMultiple();
    $this->assertIdentical([], array_keys($resource_config_entities));

    $this->runUpdates();

    // Make sure we have the expected values after the update.
    $rest_settings = $this->config('rest.settings');
    $this->assertFalse(array_key_exists('resources', $rest_settings->getRawData()));
    $resource_config_entities = $resource_config_storage->loadMultiple();
    $this->assertIdentical(['entity.node'], array_keys($resource_config_entities));
    $node_resource_config_entity = $resource_config_entities['entity.node'];
    $this->assertIdentical(RestResourceConfigInterface::RESOURCE_GRANULARITY, $node_resource_config_entity->get('granularity'));
    $this->assertIdentical([
      'methods' => ['GET'],
      'formats' => ['json'],
      'authentication' => ['basic_auth'],
    ], $node_resource_config_entity->get('configuration'));
    $this->assertIdentical(['module' => ['basic_auth', 'node', 'serialization']], $node_resource_config_entity->getDependencies());
  }

}
