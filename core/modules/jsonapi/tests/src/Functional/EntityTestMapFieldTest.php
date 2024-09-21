<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTestMapField;
use Drupal\user\Entity\User;

/**
 * JSON:API integration test for the "EntityTestMapField" content entity type.
 *
 * @group jsonapi
 */
class EntityTestMapFieldTest extends ResourceTestBase {

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
  protected static $resourceTypeName = 'entity_test_map_field--entity_test_map_field';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * {@inheritdoc}
   *
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
  protected $defaultTheme = 'stark';

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
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/entity_test_map_field/entity_test_map_field/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    $author = User::load(0);
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'entity_test_map_field--entity_test_map_field',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'created' => (new \DateTime())->setTimestamp((int) $this->entity->get('created')->value)->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'langcode' => 'en',
          'name' => 'Llama',
          'data' => static::$mapValue,
          'drupal_internal__id' => 1,
        ],
        'relationships' => [
          'user_id' => [
            'data' => [
              'id' => $author->uuid(),
              'meta' => [
                'drupal_internal__target_id' => (int) $author->id(),
              ],
              'type' => 'user--user',
            ],
            'links' => [
              'related' => ['href' => $self_url . '/user_id'],
              'self' => ['href' => $self_url . '/relationships/user_id'],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    return [
      'data' => [
        'type' => 'entity_test_map_field--entity_test_map_field',
        'attributes' => [
          'name' => 'Drama llama',
          'data' => static::$mapValue,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method): string {
    return "The 'administer entity_test content' permission is required.";
  }

  /**
   * {@inheritdoc}
   */
  protected function getSparseFieldSets() {
    // EntityTestMapField's owner field name is `user_id`, not `uid`, which
    // breaks nested sparse fieldset tests.
    return array_diff_key(parent::getSparseFieldSets(), array_flip([
      'nested_empty_fieldset',
      'nested_fieldset_with_owner_fieldset',
    ]));
  }

}
