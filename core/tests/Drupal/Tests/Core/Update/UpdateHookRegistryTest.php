<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Update;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Update\UpdateHookRegistry;
use Drupal\Tests\UnitTestCase;

/**
 * Simulates a hook_update_N function.
 */
function under_test_update_3000() {

}

/**
 * Simulates a hook_update_N function.
 *
 * When filtered this will be rejected.
 */
function bad_3() {

}

/**
 * Simulates a hook_update_N function.
 */
function under_test_update_1() {

}

/**
 * Simulates a hook_update_N functions.
 *
 * When filtered this will be rejected.
 */
function failed_22_update() {

}

/**
 * Simulates a hook_update_N function.
 */
function under_test_update_20() {

}

/**
 * Simulates a hook_update_N function.
 *
 * When filtered this will be rejected.
 */
function under_test_update_1234_failed() {

}

/**
 * @coversDefaultClass \Drupal\Core\Update\UpdateHookRegistry
 * @group Update
 */
class UpdateHookRegistryTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $keyValueStore;

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $keyValueFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->keyValueFactory = $this->createMock(KeyValueFactoryInterface::class);
    $this->keyValueStore = $this->createMock(KeyValueStoreInterface::class);

    $this->keyValueFactory
      ->method('get')
      ->with('system.schema')
      ->willReturn($this->keyValueStore);
  }

  /**
   * @covers ::getAvailableUpdates
   */
  public function testGetVersions() {
    $module_name = 'drupal\tests\core\update\under_test';

    $update_registry = new UpdateHookRegistry([], $this->keyValueStore);

    // Only under_test_update_X - passes through the filter.
    $expected = [1, 20, 3000];
    $actual = $update_registry->getAvailableUpdates($module_name);

    $this->assertSame($expected, $actual);
  }

  /**
   * @covers ::getInstalledVersion
   * @covers ::getAllInstalledVersions
   * @covers ::setInstalledVersion
   * @covers ::deleteInstalledVersion
   */
  public function testGetInstalledVersion() {
    $versions = [
      'module1' => 1,
      'module2' => 20,
      'module3' => 3000,
    ];

    $this->keyValueStore
      ->method('getAll')
      ->willReturnCallback(static function () use (&$versions) {
        return $versions;
      });
    $this->keyValueStore
      ->method('get')
      ->willReturnCallback(static function ($key) use (&$versions) {
        return $versions[$key];
      });
    $this->keyValueStore
      ->method('delete')
      ->willReturnCallback(static function ($key) use (&$versions) {
        $versions[$key] = UpdateHookRegistry::SCHEMA_UNINSTALLED;
      });
    $this->keyValueStore
      ->method('set')
      ->willReturnCallback(static function ($key, $value) use (&$versions) {
        $versions[$key] = $value;
      });

    $update_registry = new UpdateHookRegistry([], $this->keyValueStore);

    $this->assertSame(3000, $update_registry->getInstalledVersion('module3'));
    $update_registry->setInstalledVersion('module3', 3001);
    $this->assertSame(3001, $update_registry->getInstalledVersion('module3'));
    $this->assertSame($versions, $update_registry->getAllInstalledVersions());
    $update_registry->deleteInstalledVersion('module3');
    $this->assertSame(UpdateHookRegistry::SCHEMA_UNINSTALLED, $update_registry->getInstalledVersion('module3'));
  }

}
