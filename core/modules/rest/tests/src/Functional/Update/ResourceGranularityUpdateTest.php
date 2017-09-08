<?php

namespace Drupal\Tests\rest\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests method-granularity REST config is simplified to resource-granularity.
 *
 * @see https://www.drupal.org/node/2721595
 * @see rest_post_update_resource_granularity()
 *
 * @group rest
 */
class ResourceGranularityUpdateTest extends UpdatePathTestBase {

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
      __DIR__ . '/../../../fixtures/update/drupal-8.rest-rest_post_update_resource_granularity.php',
    ];
  }

  /**
   * Tests rest_post_update_simplify_resource_granularity().
   */
  public function testMethodGranularityConvertedToResourceGranularity() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $resource_config_storage */
    $resource_config_storage = $this->container->get('entity_type.manager')->getStorage('rest_resource_config');

    // Make sure we have the expected values before the update.
    $resource_config_entities = $resource_config_storage->loadMultiple();
    $this->assertIdentical(['entity.comment', 'entity.node', 'entity.user'], array_keys($resource_config_entities));
    $this->assertIdentical('method', $resource_config_entities['entity.node']->get('granularity'));
    $this->assertIdentical('method', $resource_config_entities['entity.comment']->get('granularity'));
    $this->assertIdentical('method', $resource_config_entities['entity.user']->get('granularity'));

    // Read the existing 'entity:comment' and 'entity:user' resource
    // configuration so we can verify it after the update.
    $comment_resource_configuration = $resource_config_entities['entity.comment']->get('configuration');
    $user_resource_configuration = $resource_config_entities['entity.user']->get('configuration');

    $this->runUpdates();

    // Make sure we have the expected values after the update.
    $resource_config_entities = $resource_config_storage->loadMultiple();
    $this->assertIdentical(['entity.comment', 'entity.node', 'entity.user'], array_keys($resource_config_entities));
    // 'entity:node' should be updated.
    $this->assertIdentical('resource', $resource_config_entities['entity.node']->get('granularity'));
    $this->assertidentical($resource_config_entities['entity.node']->get('configuration'), [
      'methods' => ['GET', 'POST', 'PATCH', 'DELETE'],
      'formats' => ['hal_json'],
      'authentication' => ['basic_auth'],
    ]);
    // 'entity:comment' should be unchanged.
    $this->assertIdentical('method', $resource_config_entities['entity.comment']->get('granularity'));
    $this->assertIdentical($comment_resource_configuration, $resource_config_entities['entity.comment']->get('configuration'));
    // 'entity:user' should be unchanged.
    $this->assertIdentical('method', $resource_config_entities['entity.user']->get('granularity'));
    $this->assertIdentical($user_resource_configuration, $resource_config_entities['entity.user']->get('configuration'));
  }

}
