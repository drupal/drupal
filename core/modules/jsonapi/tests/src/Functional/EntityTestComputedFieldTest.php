<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTestComputedField;
use Drupal\user\Entity\User;

/**
 * JSON:API integration test for the "EntityTestComputedField" content entity type.
 *
 * @group jsonapi
 */
class EntityTestComputedFieldTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_test_computed_field';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'entity_test_computed_field--entity_test_computed_field';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\entity_test\Entity\EntityTestComputedField
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer entity_test content']);

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
    $entity_test = EntityTestComputedField::create([
      'name' => 'Llama',
      'type' => 'entity_test_computed_field',
    ]);

    $entity_test->setOwnerId(0);
    $entity_test->save();

    return $entity_test;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/entity_test_computed_field/entity_test_computed_field/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'entity_test_computed_field--entity_test_computed_field',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'created' => (new \DateTime())->setTimestamp($this->entity->get('created')->value)->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'name' => 'Llama',
          'drupal_internal__id' => 1,
          'computed_string_field' => NULL,
          'computed_test_cacheable_string_field' => 'computed test cacheable string field',
        ],
        'relationships' => [
          'computed_reference_field' => [
            'data' => NULL,
            'links' => [
              'related' => ['href' => $self_url . '/computed_reference_field'],
              'self' => ['href' => $self_url . '/relationships/computed_reference_field'],
            ],
          ],
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
        'type' => 'entity_test_computed_field--entity_test_computed_field',
        'attributes' => [
          'name' => 'Drama llama',
        ],
      ],
    ];
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

  protected function getExpectedCacheContexts(array $sparse_fieldset = NULL) {
    $cache_contexts = parent::getExpectedCacheContexts($sparse_fieldset);
    if ($sparse_fieldset === NULL || in_array('computed_test_cacheable_string_field', $sparse_fieldset)) {
      $cache_contexts = Cache::mergeContexts($cache_contexts, ['url.query_args:computed_test_cacheable_string_field']);
    }

    return $cache_contexts;
  }

  protected function getExpectedCacheTags(array $sparse_fieldset = NULL) {
    $expected_cache_tags = parent::getExpectedCacheTags($sparse_fieldset);
    if ($sparse_fieldset === NULL || in_array('computed_test_cacheable_string_field', $sparse_fieldset)) {
      $expected_cache_tags = Cache::mergeTags($expected_cache_tags, ['field:computed_test_cacheable_string_field']);
    }

    return $expected_cache_tags;
  }

}
