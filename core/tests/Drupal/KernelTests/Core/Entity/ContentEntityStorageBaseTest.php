<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\ContentEntityStorageBase
 *
 * @group Entity
 */
class ContentEntityStorageBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
  }

  /**
   * @covers ::create
   *
   * @dataProvider providerTestCreate
   */
  public function testCreate(string|array $bundle): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test');

    $entity = $storage->create(['type' => $bundle]);
    $this->assertEquals('test_bundle', $entity->bundle());
  }

  /**
   * Provides test data for testCreate().
   */
  public static function providerTestCreate(): \Generator {
    yield 'scalar' => ['bundle' => 'test_bundle'];
    yield 'array keyed by delta' => ['bundle' => [0 => ['value' => 'test_bundle']]];
    yield 'array keyed by main property name' => ['bundle' => ['value' => 'test_bundle']];
  }

  /**
   * @covers ::create
   */
  public function testReCreate(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test');

    $values = $storage->create(['type' => 'test_bundle'])->toArray();
    $entity = $storage->create($values);
    $this->assertEquals('test_bundle', $entity->bundle());
  }

}
