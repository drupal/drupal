<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Traits;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\jsonapi\Functional\ResourceTestBase;
use GuzzleHttp\RequestOptions;

/**
 * Provides common filter access control tests.
 */
trait CommonCollectionFilterAccessTestPatternsTrait {

  use EntityReferenceFieldCreationTrait;

  /**
   * Implements ::testCollectionFilterAccess() for pure permission-based access.
   *
   * @param string $label_field_name
   *   The entity type's label field name.
   * @param string $view_permission
   *   The entity type's permission that grants 'view' access.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The referencing entity.
   */
  public function doTestCollectionFilterAccessBasedOnPermissions($label_field_name, $view_permission) {
    assert($this instanceof ResourceTestBase);

    // Set up data model.
    $this->assertTrue($this->container->get('module_installer')->install(['entity_test'], TRUE), 'Installed modules.');
    entity_test_create_bundle('bar', NULL, 'entity_test');
    $this->createEntityReferenceField(
      'entity_test',
      'bar',
      'spotlight',
      NULL,
      static::$entityTypeId,
      'default',
      [
        'target_bundles' => [
          $this->entity->bundle() => $this->entity->bundle(),
        ],
      ]
    );
    $this->rebuildAll();
    $this->grantPermissionsToTestedRole(['view test entity']);

    // Create data.
    $referencing_entity = EntityTest::create([
      'name' => 'Camelids',
      'type' => 'bar',
      'spotlight' => [
        'target_id' => $this->entity->id(),
      ],
    ]);
    $referencing_entity->save();

    // Test.
    $collection_url = Url::fromRoute('jsonapi.entity_test--bar.collection');
    // Specifying a delta exercises TemporaryQueryGuard more thoroughly.
    $filter_path = "spotlight.0.$label_field_name";
    $collection_filter_url = $collection_url->setOption('query', ["filter[$filter_path]" => $this->entity->label()]);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());
    if ($view_permission !== NULL) {
      // ?filter[spotlight.LABEL]: 0 results.
      $response = $this->request('GET', $collection_filter_url, $request_options);
      $doc = Json::decode((string) $response->getBody());
      $this->assertCount(0, $doc['data']);
      // Grant "view" permission.
      $this->grantPermissionsToTestedRole([$view_permission]);
    }
    // ?filter[spotlight.LABEL]: 1 result.
    $response = $this->request('GET', $collection_filter_url, $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(1, $doc['data']);
    $this->assertSame($referencing_entity->uuid(), $doc['data'][0]['id']);

    // ?filter[spotlight.LABEL]: 1 result.
    $response = $this->request('GET', $collection_filter_url, $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(1, $doc['data']);
    $this->assertSame($referencing_entity->uuid(), $doc['data'][0]['id']);

    // Install the jsonapi_test_field_filter_access module, which contains a
    // hook_jsonapi_entity_field_filter_access() implementation that forbids
    // access to the spotlight field if the 'filter by spotlight field'
    // permission is not granted.
    $this->assertTrue($this->container->get('module_installer')->install(['jsonapi_test_field_filter_access'], TRUE), 'Installed modules.');
    $this->rebuildAll();

    // Ensure that a 403 response is generated for attempting to filter by a
    // field that is forbidden by an implementation of
    // hook_jsonapi_entity_field_filter_access() .
    $response = $this->request('GET', $collection_filter_url, $request_options);
    $message = "The current user is not authorized to filter by the `spotlight` field, given in the path `spotlight`.";
    $expected_cache_tags = ['4xx-response', 'http_response'];
    $expected_cache_contexts = [
      'url.query_args',
      'url.site',
      'user.permissions',
    ];
    $this->assertResourceErrorResponse(403, $message, $collection_filter_url, $response, FALSE, $expected_cache_tags, $expected_cache_contexts, FALSE, 'MISS');
    // And ensure the it is allowed when the proper permission is granted.
    $this->grantPermissionsToTestedRole(['filter by spotlight field']);
    $response = $this->request('GET', $collection_filter_url, $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(1, $doc['data']);
    $this->assertSame($referencing_entity->uuid(), $doc['data'][0]['id']);
    $this->revokePermissionsFromTestedRole(['filter by spotlight field']);

    $this->assertTrue($this->container->get('module_installer')->uninstall(['jsonapi_test_field_filter_access'], TRUE), 'Uninstalled modules.');

    return $referencing_entity;
  }

  /**
   * Implements ::testCollectionFilterAccess() for permission + status access.
   *
   * @param string $label_field_name
   *   The entity type's label field name.
   * @param string $view_permission
   *   The entity type's permission that grants 'view' access (for published
   *   entities of this type).
   * @param string $admin_permission
   *   The entity type's permission that grants 'view' access (for unpublished
   *   entities of this type).
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The referencing entity.
   */
  public function doTestCollectionFilterAccessForPublishableEntities($label_field_name, $view_permission, $admin_permission) {
    assert($this->entity instanceof EntityPublishedInterface);
    $this->assertTrue($this->entity->isPublished());

    $referencing_entity = $this->doTestCollectionFilterAccessBasedOnPermissions($label_field_name, $view_permission);

    $collection_url = Url::fromRoute('jsonapi.entity_test--bar.collection');
    $collection_filter_url = $collection_url->setOption('query', ["filter[spotlight.$label_field_name]" => $this->entity->label()]);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // Unpublish.
    $this->entity->setUnpublished()->save();
    // ?filter[spotlight.LABEL]: no result because the test entity is
    // unpublished. This proves that appropriate cache tags are bubbled.
    $response = $this->request('GET', $collection_filter_url, $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(0, $doc['data']);
    // Grant admin permission.
    $this->grantPermissionsToTestedRole([$admin_permission]);
    // ?filter[spotlight.LABEL]: 1 result despite the test entity being
    // unpublished, thanks to the admin permission. This proves that the
    // appropriate cache contexts are bubbled.
    $response = $this->request('GET', $collection_filter_url, $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(1, $doc['data']);
    $this->assertSame($referencing_entity->uuid(), $doc['data'][0]['id']);

    return $referencing_entity;
  }

}
