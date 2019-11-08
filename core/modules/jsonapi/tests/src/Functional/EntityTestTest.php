<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\rest\Functional\BcTimestampNormalizerUnixTestTrait;
use Drupal\user\Entity\User;

/**
 * JSON:API integration test for the "EntityTest" content entity type.
 *
 * @group jsonapi
 */
class EntityTestTest extends ResourceTestBase {

  use BcTimestampNormalizerUnixTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_test';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'entity_test--entity_test';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * {@inheritdoc}
   *
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
    $field_storage_definition = BaseFieldDefinition::create('string')
      ->setLabel('Internal field')
      ->setInternal(TRUE);
    \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition('internal_string_field', 'entity_test', 'entity_test', $field_storage_definition);

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
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/entity_test/entity_test/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'entity_test--entity_test',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'created' => (new \DateTime())->setTimestamp($this->entity->get('created')->value)->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'field_test_text' => NULL,
          'langcode' => 'en',
          'name' => 'Llama',
          'entity_test_type' => 'entity_test',
          'drupal_internal__id' => 1,
        ],
        'relationships' => [
          'user_id' => [
            'data' => [
              'id' => $author->uuid(),
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
        'type' => 'entity_test--entity_test',
        'attributes' => [
          'name' => 'Dramallama',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    switch ($method) {
      case 'GET':
        return "The 'view test entity' permission is required.";

      case 'POST':
        return "The following permissions are required: 'administer entity_test content' OR 'administer entity_test_with_bundle content' OR 'create entity_test entity_test_with_bundle entities'.";

      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getSparseFieldSets() {
    // EntityTest's owner field name is `user_id`, not `uid`, which breaks
    // nested sparse fieldset tests.
    return array_diff_key(parent::getSparseFieldSets(), array_flip([
      'nested_empty_fieldset',
      'nested_fieldset_with_owner_fieldset',
    ]));
  }

  /**
   * {@inheritdoc}
   */
  protected static function getExpectedCollectionCacheability(AccountInterface $account, array $collection, array $sparse_fieldset = NULL, $filtered = FALSE) {
    $cacheability = parent::getExpectedCollectionCacheability($account, $collection, $sparse_fieldset, $filtered);
    if ($filtered) {
      $cacheability->addCacheTags(['state:jsonapi__entity_test_filter_access_blacklist']);
    }
    return $cacheability;
  }

}
