<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestNoBundleWithLabel;
use Drupal\entity_test\Entity\EntityTestWithBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the getBundleEntity() method.
 */
#[CoversClass(ContentEntityBase::class)]
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
class EntityBundleEntityTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_with_bundle');
    $this->installEntitySchema('entity_test_no_bundle_with_label');
  }

  /**
   * Tests an entity type with config entities for bundles.
   *
   * @legacy-covers ::getBundleEntity
   */
  public function testWithConfigBundleEntity(): void {
    $bundleEntity = EntityTestBundle::create([
      'id' => 'bundle_alpha',
      'label' => 'Alpha',
    ]);
    $bundleEntity->save();

    $entity = EntityTestWithBundle::create([
      'type' => 'bundle_alpha',
      'name' => 'foo',
    ]);
    $entity->save();
    $this->assertEquals($bundleEntity->id(), $entity->getBundleEntity()->id());
  }

  /**
   * Tests an entity type without config entities for bundles.
   *
   * EntityTest doesn't have bundles, but does have the bundle entity key.
   *
   * @legacy-covers ::getBundleEntity
   */
  public function testWithoutBundleEntity(): void {
    $entity = EntityTest::create([
      'name' => 'foo',
    ]);
    $entity->save();
    $this->assertNull($entity->getBundleEntity());
  }

  /**
   * Tests an entity type without the bundle entity key.
   *
   * @legacy-covers ::getBundleEntity
   */
  public function testWithBundleKeyEntity(): void {
    $entity = EntityTestNoBundleWithLabel::create([
      'name' => 'foo',
    ]);
    $entity->save();
    $this->assertNull($entity->getBundleEntity());
  }

}
