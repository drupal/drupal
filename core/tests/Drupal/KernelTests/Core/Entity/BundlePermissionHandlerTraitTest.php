<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\BundlePermissionHandlerTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\BundlePermissionHandlerTrait
 *
 * @group Entity
 */
class BundlePermissionHandlerTraitTest extends KernelTestBase {
  use BundlePermissionHandlerTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * @covers ::generatePermissions
   */
  public function testGeneratePermissions(): void {
    EntityTestBundle::create([
      'id' => 'test1',
    ])->save();
    EntityTestBundle::create([
      'id' => 'test2',
    ])->save();
    $permissions = $this->generatePermissions(EntityTestBundle::loadMultiple(), [$this, 'buildPermissions']);
    $this->assertSame([
      'title' => 'Create',
      'dependencies' => ['config' => ['entity_test.entity_test_bundle.test1']],
    ], $permissions['create test1']);
    $this->assertSame([
      'title' => 'Edit',
      'dependencies' => [
        'config' => [
          'test_module.entity.test1',
          'entity_test.entity_test_bundle.test1',
        ],
        'module' => ['test_module'],
      ],
    ], $permissions['edit test1']);
    $this->assertSame([
      'title' => 'Create',
      'dependencies' => ['config' => ['entity_test.entity_test_bundle.test2']],
    ], $permissions['create test2']);
    $this->assertSame([
      'title' => 'Edit',
      'dependencies' => [
        'config' => [
          'test_module.entity.test2',
          'entity_test.entity_test_bundle.test2',
        ],
        'module' => ['test_module'],
      ],
    ], $permissions['edit test2']);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPermissions(EntityInterface $bundle): array {
    return [
      "create {$bundle->id()}" => [
        'title' => 'Create',
      ],
      "edit {$bundle->id()}" => [
        'title' => 'Edit',
        // Ensure it is possible for buildPermissions to add additional
        // dependencies.
        'dependencies' => ['config' => ["test_module.entity.{$bundle->id()}"], 'module' => ['test_module']],
      ],
    ];
  }

}
