<?php

namespace Drupal\Tests\entity_test\Functional\Rest;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

abstract class EntityTestBundleResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_test_bundle';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * @var \Drupal\entity_test\Entity\EntityTestBundle
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer entity_test_bundle content']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $entity_test_bundle = EntityTestBundle::create([
      'id' => 'camelids',
      'label' => 'Camelids',
      'description' => 'Camelids are large, strictly herbivorous animals with slender necks and long legs.',
    ]);
    $entity_test_bundle->save();

    return $entity_test_bundle;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'dependencies' => [],
      'description' => 'Camelids are large, strictly herbivorous animals with slender necks and long legs.',
      'id' => 'camelids',
      'label' => 'Camelids',
      'langcode' => 'en',
      'status' => TRUE,
      'uuid' => $this->entity->uuid(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

}
