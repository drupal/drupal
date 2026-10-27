<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\entity_test\EntityTestHelper;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\jsonapi_test_field_type\TestGadget;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * JSON:API resource tests.
 *
 * @internal
 */
#[Group('jsonapi')]
#[RunTestsInSeparateProcesses]
class JsonApiRelationshipTest extends JsonApiFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'basic_auth',
    'entity_test',
    'jsonapi_test_field_type',
  ];

  /**
   * The entity type ID.
   */
  protected string $entityTypeId = 'entity_test';

  /**
   * The entity bundle.
   */
  protected string $bundle = 'entity_test';

  /**
   * The field name.
   */
  protected string $fieldName = 'field_child';

  /**
   * The field name for the to-many entity reference with meta field.
   */
  protected string $fieldNameWithMeta = 'field_ref_with_meta';

  /**
   * The field name for the to-one entity reference with meta field.
   */
  protected string $fieldNameWithMetaSingle = 'field_ref_with_meta_single';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    EntityTestHelper::createBundle($this->bundle, 'Parent', $this->entityTypeId);

    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'type' => 'jsonapi_test_field_type_entity_reference_uuid',
      'entity_type' => $this->entityTypeId,
      'cardinality' => 1,
      'settings' => [
        'target_type' => $this->entityTypeId,
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->bundle,
      'label' => $this->randomString(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [],
      ],
    ])->save();

    // To-many field for PATCH and POST tests.
    FieldStorageConfig::create([
      'field_name' => $this->fieldNameWithMeta,
      'type' => 'jsonapi_test_field_type_entity_reference_with_meta',
      'entity_type' => $this->entityTypeId,
      'cardinality' => -1,
      'settings' => [
        'target_type' => $this->entityTypeId,
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => $this->fieldNameWithMeta,
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->bundle,
      'label' => $this->randomString(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [],
      ],
    ])->save();

    // To-one field for PATCH single-value test.
    FieldStorageConfig::create([
      'field_name' => $this->fieldNameWithMetaSingle,
      'type' => 'jsonapi_test_field_type_entity_reference_with_meta',
      'entity_type' => $this->entityTypeId,
      'cardinality' => 1,
      'settings' => [
        'target_type' => $this->entityTypeId,
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => $this->fieldNameWithMetaSingle,
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->bundle,
      'label' => $this->randomString(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [],
      ],
    ])->save();

    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Test relationships without target_id as main property.
   *
   * @see https://www.drupal.org/project/drupal/issues/3476224
   */
  public function testPatchHandleUUIDPropertyReferenceFieldIssue3127883(): void {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save();
    $user = $this->drupalCreateUser([
      'administer entity_test content',
      'view test entity',
    ]);

    // Create parent and child entities.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $parentEntity = $storage
      ->create([
        'type' => $this->bundle,
      ]);
    $parentEntity->save();
    $childUuid = $this->container->get('uuid')->generate();
    $childEntity = $storage
      ->create([
        'type' => $this->bundle,
        'uuid' => $childUuid,
      ]);
    $childEntity->save();
    $uuid = $childEntity->uuid();
    $this->assertEquals($childUuid, $uuid);

    // 1. Successful PATCH to the related endpoint.
    $url = Url::fromUri(sprintf('internal:/jsonapi/%s/%s/%s/relationships/%s', $this->entityTypeId, $this->bundle, $parentEntity->uuid(), $this->fieldName));
    $request_options = [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
      RequestOptions::JSON => [
        'data' => [
          'id' => $childUuid,
          'type' => sprintf('%s--%s', $this->entityTypeId, $this->bundle),
        ],
      ],
    ];
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
    $parentEntity = $storage->loadUnchanged($parentEntity->id());
    $this->assertEquals($childEntity->uuid(), $parentEntity->get($this->fieldName)->target_uuid);

    // Reset the relationship.
    $parentEntity->set($this->fieldName, NULL)
      ->save();
    $parentEntity = $storage->loadUnchanged($parentEntity->id());
    $this->assertTrue($parentEntity->get($this->fieldName)->isEmpty());

    // 2. Successful PATCH to individual endpoint.
    $url = Url::fromUri(sprintf('internal:/jsonapi/%s/%s/%s', $this->entityTypeId, $this->bundle, $parentEntity->uuid()));
    $request_options[RequestOptions::JSON] = [
      'data' => [
        'id' => $parentEntity->uuid(),
        'type' => sprintf('%s--%s', $this->entityTypeId, $this->bundle),
        'relationships' => [
          $this->fieldName => [
            'data' => [
              [
                'id' => $childUuid,
                'type' => sprintf('%s--%s', $this->entityTypeId, $this->bundle),
              ],
            ],
          ],
        ],
      ],
    ];
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $parentEntity = $storage->loadUnchanged($parentEntity->id());
    $this->assertEquals($childEntity->uuid(), $parentEntity->get($this->fieldName)->target_uuid);
  }

  /**
   * PATCH to a to-many relationship does not allow object injection.
   *
   * A field type combining EntityReferenceItem lineage with
   * serialized_property_names exposes a code path where user-supplied data
   * in meta.data is passed to unserialize(). This test confirms that a gadget
   * chain payload does not result in object instantiation.
   */
  public function testMultiValueRelationshipPatchDoesNotAllowObjectInjection(): void {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save();
    $user = $this->drupalCreateUser([
      'view test entity',
      'edit own entity_test content',
    ]);

    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);

    $parentEntity = $storage->create([
      'type' => $this->bundle,
      'user_id' => $user->id(),
    ]);
    $parentEntity->save();

    $targetEntity = $storage->create(['type' => $this->bundle]);
    $targetEntity->save();

    $url = Url::fromUri(sprintf(
      'internal:/jsonapi/%s/%s/%s/relationships/%s',
      $this->entityTypeId,
      $this->bundle,
      $parentEntity->uuid(),
      $this->fieldNameWithMeta,
    ));

    $response = $this->request('PATCH', $url, [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
      RequestOptions::JSON => [
        'data' => [
          [
            'id' => $targetEntity->uuid(),
            'type' => sprintf('%s--%s', $this->entityTypeId, $this->bundle),
            'meta' => [
              'data' => serialize(new TestGadget()),
            ],
          ],
        ],
      ],
    ]);

    $this->assertStringNotContainsString(
      'JSONAPI_TEST_GADGET_WAKEUP_CALLED',
      (string) $response->getBody(),
      'Object injection succeeded: TestGadget::__wakeup() was called.',
    );
  }

  /**
   * PATCH to a to-one relationship does not allow object injection.
   */
  public function testSingleValueRelationshipPatchDoesNotAllowObjectInjection(): void {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save();
    $user = $this->drupalCreateUser([
      'view test entity',
      'edit own entity_test content',
    ]);

    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);

    $parentEntity = $storage->create([
      'type' => $this->bundle,
      'user_id' => $user->id(),
    ]);
    $parentEntity->save();

    $targetEntity = $storage->create(['type' => $this->bundle]);
    $targetEntity->save();

    $url = Url::fromUri(sprintf(
      'internal:/jsonapi/%s/%s/%s/relationships/%s',
      $this->entityTypeId,
      $this->bundle,
      $parentEntity->uuid(),
      $this->fieldNameWithMetaSingle,
    ));

    $response = $this->request('PATCH', $url, [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
      RequestOptions::JSON => [
        'data' => [
          'id' => $targetEntity->uuid(),
          'type' => sprintf('%s--%s', $this->entityTypeId, $this->bundle),
          'meta' => [
            'data' => serialize(new TestGadget()),
          ],
        ],
      ],
    ]);

    $this->assertStringNotContainsString(
      'JSONAPI_TEST_GADGET_WAKEUP_CALLED',
      (string) $response->getBody(),
      'Object injection succeeded: TestGadget::__wakeup() was called.',
    );
  }

  /**
   * POST to a to-many relationship does not allow object injection.
   */
  public function testMultiValueRelationshipPostDoesNotAllowObjectInjection(): void {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save();
    $user = $this->drupalCreateUser([
      'view test entity',
      'edit own entity_test content',
    ]);

    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);

    $parentEntity = $storage->create([
      'type' => $this->bundle,
      'user_id' => $user->id(),
    ]);
    $parentEntity->save();

    $targetEntity = $storage->create(['type' => $this->bundle]);
    $targetEntity->save();

    $url = Url::fromUri(sprintf(
      'internal:/jsonapi/%s/%s/%s/relationships/%s',
      $this->entityTypeId,
      $this->bundle,
      $parentEntity->uuid(),
      $this->fieldNameWithMeta,
    ));

    $response = $this->request('POST', $url, [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
      RequestOptions::JSON => [
        'data' => [
          [
            'id' => $targetEntity->uuid(),
            'type' => sprintf('%s--%s', $this->entityTypeId, $this->bundle),
            'meta' => [
              'data' => serialize(new TestGadget()),
            ],
          ],
        ],
      ],
    ]);

    $this->assertStringNotContainsString(
      'JSONAPI_TEST_GADGET_WAKEUP_CALLED',
      (string) $response->getBody(),
      'Object injection succeeded: TestGadget::__wakeup() was called.',
    );
  }

}
