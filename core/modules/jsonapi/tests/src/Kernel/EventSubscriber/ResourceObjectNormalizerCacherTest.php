<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Kernel\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\entity_test\Entity\EntityTestComputedField;
use Drupal\jsonapi\EventSubscriber\ResourceObjectNormalizationCacher;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\jsonapi\EventSubscriber\ResourceObjectNormalizationCacher
 * @group jsonapi
 *
 * @internal
 */
class ResourceObjectNormalizerCacherTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'file',
    'system',
    'serialization',
    'text',
    'jsonapi',
    'user',
  ];

  /**
   * The JSON:API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The JSON:API serializer.
   *
   * @var \Drupal\jsonapi\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The object under test.
   *
   * @var \Drupal\jsonapi\EventSubscriber\ResourceObjectNormalizationCacher
   */
  protected $cacher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add the entity schemas.
    $this->installEntitySchema('user');
    // Add the additional table schemas.
    $this->installSchema('user', ['users_data']);
    $this->resourceTypeRepository = $this->container->get('jsonapi.resource_type.repository');
    $this->serializer = $this->container->get('jsonapi.serializer');
    $this->cacher = $this->container->get('jsonapi.normalization_cacher');
  }

  /**
   * Tests that link normalization cache information is not lost.
   *
   * @see https://www.drupal.org/project/drupal/issues/3077287
   */
  public function testLinkNormalizationCacheability(): void {
    $user = User::create([
      'name' => $this->randomMachineName(),
      'pass' => $this->randomString(),
    ]);
    $user->save();
    $resource_type = $this->resourceTypeRepository->get($user->getEntityTypeId(), $user->bundle());
    $resource_object = ResourceObject::createFromEntity($resource_type, $user);
    $cache_tag_to_invalidate = 'link_normalization';
    $normalized_links = $this->serializer
      ->normalize($resource_object->getLinks(), 'api_json')
      ->withCacheableDependency((new CacheableMetadata())->addCacheTags([$cache_tag_to_invalidate]));
    assert($normalized_links instanceof CacheableNormalization);
    $normalization_parts = [
      ResourceObjectNormalizationCacher::RESOURCE_CACHE_SUBSET_BASE => [
        'type' => CacheableNormalization::permanent($resource_object->getTypeName()),
        'id' => CacheableNormalization::permanent($resource_object->getId()),
        'links' => $normalized_links,
      ],
      ResourceObjectNormalizationCacher::RESOURCE_CACHE_SUBSET_FIELDS => [],
    ];
    $this->cacher->saveOnTerminate($resource_object, $normalization_parts);

    $http_kernel = $this->prophesize(HttpKernelInterface::class);
    $request = $this->prophesize(Request::class);
    $response = $this->prophesize(Response::class);
    $event = new TerminateEvent($http_kernel->reveal(), $request->reveal(), $response->reveal());
    $this->cacher->onTerminate($event);
    $this->assertNotFalse((bool) $this->cacher->get($resource_object));
    Cache::invalidateTags([$cache_tag_to_invalidate]);
    $this->assertFalse((bool) $this->cacher->get($resource_object));
  }

  /**
   * Tests that normalization max-age is correct.
   *
   * When max-age for a cached record is set the expiry is set accordingly. But
   * if the cached normalization is partially used in a later normalization the
   * max-age should be adjusted to a new timestamp.
   *
   * If we don't do this the expires of the cache record will be reset based on
   * the original max age. This leads to a drift in the expiry time of the
   * record.
   *
   * If a field tells the cache it should expire in exactly 1 hour, then if the
   * cached data is used 10 minutes later in another resource, that cache should
   * expire in 50 minutes and not reset to 60 minutes.
   */
  public function testMaxAgeCorrection(): void {
    $this->installEntitySchema('entity_test_computed_field');

    // Use EntityTestComputedField since ComputedTestCacheableStringItemList has a max age of 800
    $baseMaxAge = 800;
    $entity = EntityTestComputedField::create([]);
    $entity->save();
    $resource_type = $this->resourceTypeRepository->get($entity->getEntityTypeId(), $entity->bundle());
    $resource_object = ResourceObject::createFromEntity($resource_type, $entity);

    $resource_normalization = $this->serializer
      ->normalize($resource_object, 'api_json', ['account' => NULL]);
    $this->assertEquals($baseMaxAge, $resource_normalization->getCacheMaxAge());

    // Save the normalization to cache, this is done at TerminateEvent.
    $http_kernel = $this->prophesize(HttpKernelInterface::class);
    $request = $this->prophesize(Request::class);
    $response = $this->prophesize(Response::class);
    $event = new TerminateEvent($http_kernel->reveal(), $request->reveal(), $response->reveal());
    $this->cacher->onTerminate($event);

    // Change request time to 500 seconds later
    $current_request = \Drupal::requestStack()->getCurrentRequest();
    $current_request->server->set('REQUEST_TIME', $current_request->server->get('REQUEST_TIME') + 500);
    $resource_normalization = $this->serializer
      ->normalize($resource_object, 'api_json', ['account' => NULL]);
    $this->assertEquals($baseMaxAge - 500, $resource_normalization->getCacheMaxAge(), 'Max age should be 300 since 500 seconds has passed');

    // Change request time to 800 seconds later, this is the last second the
    // cache backend would return cached data. The max-age at that time should
    // be 0 which is the same as the expire time of the cache entry.
    $current_request->server->set('REQUEST_TIME', $current_request->server->get('REQUEST_TIME') + 800);
    $resource_normalization = $this->serializer
      ->normalize($resource_object, 'api_json', ['account' => NULL]);
    $this->assertEquals(0, $resource_normalization->getCacheMaxAge(), 'Max age should be 0 since max-age has passed');

    // Change request time to 801 seconds later. This validates that max-age
    // never becomes negative. This should never happen as the cache entry
    // is expired at this time and the cache backend would not return data.
    $current_request->server->set('REQUEST_TIME', $current_request->server->get('REQUEST_TIME') + 801);
    $resource_normalization = $this->serializer
      ->normalize($resource_object, 'api_json', ['account' => NULL]);
    $this->assertEquals(0, $resource_normalization->getCacheMaxAge(), 'Max age should be 0 since max-age has passed a second ago');

  }

}
