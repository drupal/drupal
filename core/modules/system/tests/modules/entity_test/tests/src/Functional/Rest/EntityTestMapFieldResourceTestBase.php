<?php

namespace Drupal\Tests\entity_test\Functional\Rest;

use Drupal\entity_test\Entity\EntityTestMapField;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\Tests\Traits\ExpectDeprecationTrait;
use Drupal\user\Entity\User;

abstract class EntityTestMapFieldResourceTestBase extends EntityResourceTestBase {

  use ExpectDeprecationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_test_map_field';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * @var \Drupal\entity_test\Entity\EntityTestMapField
   */
  protected $entity;

  /**
   * The complex nested value to assign to a @FieldType=map field.
   *
   * @var array
   */
  protected static $mapValue = [
    'key1' => 'value',
    'key2' => 'no, val you',
    'π' => 3.14159,
    TRUE => 42,
    'nested' => [
      'bird' => 'robin',
      'doll' => 'Russian',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer entity_test content']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $entity = EntityTestMapField::create([
      'name' => 'Llama',
      'type' => 'entity_test_map_field',
      'data' => [
        static::$mapValue,
      ],
    ]);
    $entity->setOwnerId(0);
    $entity->save();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $author = User::load(0);
    return [
      'uuid' => [
        [
          'value' => $this->entity->uuid(),
        ],
      ],
      'id' => [
        [
          'value' => 1,
        ],
      ],
      'name' => [
        [
          'value' => 'Llama',
        ],
      ],
      'langcode' => [
        [
          'value' => 'en',
        ],
      ],
      'created' => [
        [
          'value' => (new \DateTime())->setTimestamp((int) $this->entity->get('created')->value)->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'format' => \DateTime::RFC3339,
        ],
      ],
      'user_id' => [
        [
          'target_id' => (int) $author->id(),
          'target_type' => 'user',
          'target_uuid' => $author->uuid(),
          'url' => $author->toUrl()->toString(),
        ],
      ],
      'data' => [
        static::$mapValue,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return [
      'name' => [
        [
          'value' => 'Dramallama',
        ],
      ],
      'data' => [
        0 => static::$mapValue,
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

    return "The 'administer entity_test content' permission is required.";
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    return ['user.permissions'];
  }

}
