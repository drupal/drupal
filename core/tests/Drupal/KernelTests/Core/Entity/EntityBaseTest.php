<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\Plugin\DataType\ConfigEntityAdapter;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestWithBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the functionality provided by \Drupal\Core\Entity\EntityBase.
 */
#[CoversClass(EntityBase::class)]
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
class EntityBaseTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_with_bundle');
  }

  /**
   * Tests that the correct entity adapter is returned.
   */
  public function testGetTypedData(): void {
    $bundle = EntityTestBundle::create([
      'id' => $this->randomMachineName(),
    ]);
    $bundle->save();

    $entity = EntityTestWithBundle::create([
      'type' => $bundle->id(),
      'name' => $this->randomString(),
    ]);
    $entity->save();

    $this->assertInstanceOf(ConfigEntityAdapter::class, $bundle->getTypedData());
    $this->assertInstanceOf(EntityAdapter::class, $entity->getTypedData());
  }

}
