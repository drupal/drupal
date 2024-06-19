<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config\Entity;

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests \Drupal\Core\Config\Entity\ConfigEntityUpdater.
 *
 * @coversDefaultClass \Drupal\Core\Config\Entity\ConfigEntityUpdater
 * @group config
 */
class ConfigEntityUpdaterTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['config_test', 'system'];

  /**
   * @covers ::update
   */
  public function testUpdate(): void {
    // Create some entities to update.
    $storage = $this->container->get('entity_type.manager')->getStorage('config_test');
    for ($i = 0; $i < 15; $i++) {
      $entity_id = 'config_test_' . $i;
      $storage->create(['id' => $entity_id, 'label' => $entity_id])->save();
    }

    // Set up the updater.
    $sandbox = [];
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['entity_update_batch_size'] = 10;
    new Settings($settings);
    $updater = $this->container->get('class_resolver')->getInstanceFromDefinition(ConfigEntityUpdater::class);

    $callback = function ($config_entity) {
      /** @var \Drupal\config_test\Entity\ConfigTest $config_entity */
      $number = (int) str_replace('config_test_', '', $config_entity->id());
      // Only update even numbered entities.
      if ($number % 2 == 0) {
        $config_entity->set('label', $config_entity->label . ' (updated)');
        return TRUE;
      }
      return FALSE;
    };

    // This should run against the first 10 entities. The even numbered labels
    // will have been updated.
    $updater->update($sandbox, 'config_test', $callback);
    $entities = $storage->loadMultiple();
    $this->assertEquals('config_test_8 (updated)', $entities['config_test_8']->label());
    $this->assertEquals('config_test_9', $entities['config_test_9']->label());
    $this->assertEquals('config_test_10', $entities['config_test_10']->label());
    $this->assertEquals('config_test_14', $entities['config_test_14']->label());
    $this->assertEquals(15, $sandbox['config_entity_updater']['count']);
    $this->assertEquals('config_test', $sandbox['config_entity_updater']['entity_type']);
    $this->assertCount(5, $sandbox['config_entity_updater']['entities']);
    $this->assertEquals(10 / 15, $sandbox['#finished']);

    // Update the rest.
    $updater->update($sandbox, 'config_test', $callback);
    $entities = $storage->loadMultiple();
    $this->assertEquals('config_test_8 (updated)', $entities['config_test_8']->label());
    $this->assertEquals('config_test_9', $entities['config_test_9']->label());
    $this->assertEquals('config_test_10 (updated)', $entities['config_test_10']->label());
    $this->assertEquals('config_test_14 (updated)', $entities['config_test_14']->label());
    $this->assertEquals(1, $sandbox['#finished']);
    $this->assertCount(0, $sandbox['config_entity_updater']['entities']);
  }

  /**
   * @covers ::update
   */
  public function testUpdateDefaultCallback(): void {
    // Create some entities to update.
    $storage = $this->container->get('entity_type.manager')->getStorage('config_test');
    for ($i = 0; $i < 15; $i++) {
      $entity_id = 'config_test_' . $i;
      $storage->create(['id' => $entity_id, 'label' => $entity_id])->save();
    }

    // Set up the updater.
    $sandbox = [];
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['entity_update_batch_size'] = 9;
    new Settings($settings);
    $updater = $this->container->get('class_resolver')->getInstanceFromDefinition(ConfigEntityUpdater::class);
    // Cause a dependency to be added during an update.
    \Drupal::state()->set('config_test_new_dependency', 'system');

    // This should run against the first 10 entities.
    $updater->update($sandbox, 'config_test');
    $entities = $storage->loadMultiple();
    $this->assertEquals(['system'], $entities['config_test_7']->getDependencies()['module']);
    $this->assertEquals(['system'], $entities['config_test_8']->getDependencies()['module']);
    $this->assertEquals([], $entities['config_test_9']->getDependencies());
    $this->assertEquals([], $entities['config_test_14']->getDependencies());
    $this->assertEquals(15, $sandbox['config_entity_updater']['count']);
    $this->assertCount(6, $sandbox['config_entity_updater']['entities']);
    $this->assertEquals(9 / 15, $sandbox['#finished']);

    // Update the rest.
    $updater->update($sandbox, 'config_test');
    $entities = $storage->loadMultiple();
    $this->assertEquals(['system'], $entities['config_test_9']->getDependencies()['module']);
    $this->assertEquals(['system'], $entities['config_test_14']->getDependencies()['module']);
    $this->assertEquals(1, $sandbox['#finished']);
    $this->assertCount(0, $sandbox['config_entity_updater']['entities']);
  }

  /**
   * @covers ::update
   */
  public function testUpdateException(): void {
    $this->enableModules(['entity_test']);
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The provided entity type ID \'entity_test_mul_changed\' is not a configuration entity type');
    $updater = $this->container->get('class_resolver')->getInstanceFromDefinition(ConfigEntityUpdater::class);
    $sandbox = [];
    $updater->update($sandbox, 'entity_test_mul_changed');
  }

  /**
   * @covers ::update
   */
  public function testUpdateOncePerUpdateException(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Updating multiple entity types in the same update function is not supported');
    $updater = $this->container->get('class_resolver')->getInstanceFromDefinition(ConfigEntityUpdater::class);
    $sandbox = [];
    $updater->update($sandbox, 'config_test');
    $updater->update($sandbox, 'config_query_test');
  }

}
