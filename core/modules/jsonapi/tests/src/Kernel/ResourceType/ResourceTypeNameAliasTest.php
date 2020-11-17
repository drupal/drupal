<?php

namespace Drupal\Tests\jsonapi\Kernel\ResourceType;

use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;

/**
 * Ensures that JSON:API doesn't malfunction on aliased resource type names.
 *
 * Despite JSON:API having a limited public PHP API, contrib and custom
 * modules have found ways to rename resource types (e.g. "user--user" to
 * "user") which causes JSON:API to malfunction. This results in confusion and
 * bug reports. Since aliasing resource type names is a feature that JSON:API
 * will eventually support, this test will help prevent future regressions and
 * lower the present support burden.
 *
 * @coversDefaultClass \Drupal\jsonapi\ResourceType\ResourceTypeRepository
 * @group jsonapi
 *
 * @internal
 *
 * @link https://www.drupal.org/project/drupal/issues/2996114
 *
 * @todo move this test coverage to ResourceTypeRepositoryTest::testResourceTypeNameAliasing in https://www.drupal.org/project/drupal/issues/3105318
 */
class ResourceTypeNameAliasTest extends JsonapiKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'field',
    'system',
    'serialization',
    'jsonapi_test_resource_type_aliasing',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container
      ->get('entity_type.manager')
      ->getStorage('node_type')
      ->create(['type' => 'page'])
      ->save();
  }

  /**
   * Ensures resource repository works with publicly renamed resource types.
   *
   * @covers ::get
   * @covers ::all
   * @covers ::getByTypeName
   */
  public function testRepositoryResourceTypeNameAliasing() {
    $repository = $this->container->get('jsonapi.resource_type.repository');

    static::assertInstanceOf(ResourceType::class, $repository->get('user', 'user'));
    static::assertNull($repository->getByTypeName('user--user'));
    static::assertInstanceOf(ResourceType::class, $repository->getByTypeName('user==user'));

    static::assertInstanceOf(ResourceType::class, $repository->get('node', 'page'));
    static::assertNull($repository->getByTypeName('node--page'));
    static::assertInstanceOf(ResourceType::class, $repository->getByTypeName('node==page'));

    foreach ($repository->all() as $id => $resource_type) {
      static::assertSame(
        $resource_type->getTypeName(),
        $id,
        'The key is always equal to the type name.'
      );

      static::assertNotSame(
        sprintf('%s--%s', $resource_type->getEntityTypeId(), $resource_type->getBundle()),
        $id,
        'The type name can be renamed so it differs from the internal.'
      );
    }
  }

}
