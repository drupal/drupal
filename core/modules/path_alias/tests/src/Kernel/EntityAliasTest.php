<?php

declare(strict_types=1);

namespace Drupal\Tests\path_alias\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Tests path alias on entities.
 *
 * @group path_alias
 */
class EntityAliasTest extends KernelTestBase {

  use PathAliasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path_alias',
    'entity_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('user');
  }

  /**
   * Tests transform.
   */
  public function testEntityAlias(): void {
    EntityTest::create(['id' => 1])->save();
    $this->createPathAlias('/entity_test/1', '/entity-alias');
    $entity = EntityTest::load(1);
    $this->assertSame('/entity-alias', $entity->toUrl()->toString());
  }

}
