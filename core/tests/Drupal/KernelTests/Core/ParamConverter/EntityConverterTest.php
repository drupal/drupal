<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\ParamConverter;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the entity param converter.
 *
 * @group ParamConverter
 * @coversDefaultClass \Drupal\Core\ParamConverter\EntityConverter
 */
class EntityConverterTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpCurrentUser();
    $this->installEntitySchema('entity_test');

    // Create some testing bundles for 'entity_test' entity type.
    entity_test_create_bundle('foo', 'Foo');
    entity_test_create_bundle('bar', 'Bar');
    entity_test_create_bundle('baz', 'Baz');
  }

  /**
   * Tests an entity route parameter having 'bundle' definition property.
   *
   * @covers ::convert
   */
  public function testRouteParamWithBundleDefinition(): void {
    $converter = $this->container->get('paramconverter.entity');

    $entity1 = EntityTest::create([
      'name' => $this->randomString(),
      'type' => 'foo',
    ]);
    $entity1->save();
    $entity2 = EntityTest::create([
      'name' => $this->randomString(),
      'type' => 'bar',
    ]);
    $entity2->save();
    $entity3 = EntityTest::create([
      'name' => $this->randomString(),
      'type' => 'baz',
    ]);
    $entity3->save();

    $definition = [
      'type' => 'entity:entity_test',
      'bundle' => [
        'foo',
        'bar',
      ],
    ];

    // An entity whose bundle is in the definition list is converted.
    $converted = $converter->convert($entity1->id(), $definition, 'qux', []);
    $this->assertSame($entity1->id(), $converted->id());

    // An entity whose bundle is in the definition list is converted.
    $converted = $converter->convert($entity2->id(), $definition, 'qux', []);
    $this->assertSame($entity2->id(), $converted->id());

    // An entity whose bundle is missed from definition is not converted.
    $converted = $converter->convert($entity3->id(), $definition, 'qux', []);
    $this->assertNull($converted);

    // A non-existing entity returns NULL.
    $converted = $converter->convert('some-non-existing-entity-id', $definition, 'qux', []);
    $this->assertNull($converted);

    $definition = [
      'type' => 'entity:entity_test',
    ];

    // Check that all entities are returned when 'bundle' is not defined.
    $converted = $converter->convert($entity1->id(), $definition, 'qux', []);
    $this->assertSame($entity1->id(), $converted->id());
    $converted = $converter->convert($entity2->id(), $definition, 'qux', []);
    $this->assertSame($entity2->id(), $converted->id());
    $converted = $converter->convert($entity3->id(), $definition, 'qux', []);
    $this->assertSame($entity3->id(), $converted->id());
    $converted = $converter->convert('some-non-existing-entity-id', $definition, 'qux', []);
    $this->assertNull($converted);
  }

}
