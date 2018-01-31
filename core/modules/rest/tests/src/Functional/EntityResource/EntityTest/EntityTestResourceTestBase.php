<?php

namespace Drupal\Tests\rest\Functional\EntityResource\EntityTest;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\rest\Functional\BcTimestampNormalizerUnixTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\Tests\Traits\ExpectDeprecationTrait;
use Drupal\user\Entity\User;

abstract class EntityTestResourceTestBase extends EntityResourceTestBase {

  use BcTimestampNormalizerUnixTestTrait;
  use ExpectDeprecationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_test';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['view test entity']);
        break;
      case 'POST':
        $this->grantPermissionsToTestedRole(['create entity_test entity_test_with_bundle entities']);
        break;
      case 'PATCH':
      case 'DELETE':
        $this->grantPermissionsToTestedRole(['administer entity_test content']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Set flag so that internal field 'internal_string_field' is created.
    // @see entity_test_entity_base_field_info()
    $this->container->get('state')->set('entity_test.internal_field', TRUE);
    \Drupal::entityDefinitionUpdateManager()->applyUpdates();

    $entity_test = EntityTest::create([
      'name' => 'Llama',
      'type' => 'entity_test',
      // Set a value for the internal field to confirm that it will not be
      // returned in normalization.
      // @see entity_test_entity_base_field_info().
      'internal_string_field' => [
        'value' => 'This value shall not be internal!',
      ],
    ]);
    $entity_test->setOwnerId(0);
    $entity_test->save();

    return $entity_test;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $author = User::load(0);
    $normalization = [
      'uuid' => [
        [
          'value' => $this->entity->uuid()
        ]
      ],
      'id' => [
        [
          'value' => 1,
        ],
      ],
      'langcode' => [
        [
          'value' => 'en',
        ],
      ],
      'type' => [
        [
          'value' => 'entity_test',
        ]
      ],
      'name' => [
        [
          'value' => 'Llama',
        ]
      ],
      'created' => [
        $this->formatExpectedTimestampItemValues((int) $this->entity->get('created')->value)
      ],
      'user_id' => [
        [
          'target_id' => (int) $author->id(),
          'target_type' => 'user',
          'target_uuid' => $author->uuid(),
          'url' => $author->toUrl()->toString(),
        ]
      ],
      'field_test_text' => [],
    ];

    return $normalization;
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return [
      'type' => [
        [
          'value' => 'entity_test',
        ],
      ],
      'name' => [
        [
          'value' => 'Dramallama',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    switch ($method) {
      case 'GET':
        return "The 'view test entity' permission is required.";
      case 'POST':
        return "The following permissions are required: 'administer entity_test content' OR 'administer entity_test_with_bundle content' OR 'create entity_test entity_test_with_bundle entities'.";
      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

}
