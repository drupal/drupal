<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\Update\UpdateApiEntityDefinitionUpdateTest.
 */

namespace Drupal\system\Tests\Entity\Update;

use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\simpletest\WebTestBase;
use Drupal\system\Tests\Update\DbUpdatesTrait;

/**
 * Tests performing entity updates through the Update API.
 *
 * @group Entity
 */
class UpdateApiEntityDefinitionUpdateTest extends WebTestBase {

  use DbUpdatesTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $updatesManager;


  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityManager = $this->container->get('entity.manager');
    $this->updatesManager = $this->container->get('entity.definition_update_manager');

    $admin = $this->drupalCreateUser([], FALSE, TRUE);
    $this->drupalLogin($admin);
  }

  /**
   * Tests that individual updates applied sequentially work as expected.
   */
  public function testSingleUpdates() {
    // Create a test entity.
    $user_ids = [mt_rand(), mt_rand()];
    $entity = EntityTest::create(['name' => $this->randomString(),  'user_id' => $user_ids]);
    $entity->save();

    // Check that only a single value is stored for 'user_id'.
    $entity = $this->reloadEntity($entity);
    $this->assertEqual(count($entity->user_id), 1);
    $this->assertEqual($entity->user_id->target_id, $user_ids[0]);

    // Make 'user_id' multiple by applying updates.
    $this->enableUpdates('entity_test', 'entity_definition_updates', 8001);
    $this->applyUpdates();

    // Check that data was correctly migrated.
    $entity = $this->reloadEntity($entity);
    $this->assertEqual(count($entity->user_id), 1);
    $this->assertEqual($entity->user_id->target_id, $user_ids[0]);

    // Store multiple data and check it is correctly stored.
    $entity->user_id = $user_ids;
    $entity->save();
    $entity = $this->reloadEntity($entity);
    $this->assertEqual(count($entity->user_id), 2);
    $this->assertEqual($entity->user_id[0]->target_id, $user_ids[0]);
    $this->assertEqual($entity->user_id[1]->target_id, $user_ids[1]);

    // Make 'user_id' single again by applying updates.
    $this->enableUpdates('entity_test', 'entity_definition_updates', 8002);
    $this->applyUpdates();

    // Check that data was correctly migrated/dropped.
    $entity = $this->reloadEntity($entity);
    $this->assertEqual(count($entity->user_id), 1);
    $this->assertEqual($entity->user_id->target_id, $user_ids[0]);

    // Check that only a single value is stored for 'user_id' again.
    $entity->user_id = $user_ids;
    $entity->save();
    $entity = $this->reloadEntity($entity);
    $this->assertEqual(count($entity->user_id), 1);
    $this->assertEqual($entity->user_id[0]->target_id, $user_ids[0]);
  }

  /**
   * Tests that multiple updates applied in bulk work as expected.
   */
  public function testMultipleUpdates() {
    // Create a test entity.
    $user_ids = [mt_rand(), mt_rand()];
    $entity = EntityTest::create(['name' => $this->randomString(),  'user_id' => $user_ids]);
    $entity->save();

    // Check that only a single value is stored for 'user_id'.
    $entity = $this->reloadEntity($entity);
    $this->assertEqual(count($entity->user_id), 1);
    $this->assertEqual($entity->user_id->target_id, $user_ids[0]);

    // Make 'user_id' multiple and then single again by applying updates.
    $this->enableUpdates('entity_test', 'entity_definition_updates', 8002);
    $this->applyUpdates();

    // Check that data was correctly migrated back and forth.
    $entity = $this->reloadEntity($entity);
    $this->assertEqual(count($entity->user_id), 1);
    $this->assertEqual($entity->user_id->target_id, $user_ids[0]);

    // Check that only a single value is stored for 'user_id' again.
    $entity->user_id = $user_ids;
    $entity->save();
    $entity = $this->reloadEntity($entity);
    $this->assertEqual(count($entity->user_id), 1);
    $this->assertEqual($entity->user_id[0]->target_id, $user_ids[0]);
  }

  /**
   * Tests that entity updates are correctly reported in the status report page.
   */
  function testStatusReport() {
    // Create a test entity.
    $entity = EntityTest::create(['name' => $this->randomString(),  'user_id' => mt_rand()]);
    $entity->save();

    // Check that the status report initially displays no error.
    $this->drupalGet('admin/reports/status');
    $this->assertNoRaw('Out of date');
    $this->assertNoRaw('Mismatched entity and/or field definitions');

    // Enable an entity update and check that we have a dedicated status report
    // item.
    $this->container->get('state')->set('entity_test.remove_name_field', TRUE);
    $this->drupalGet('admin/reports/status');
    $this->assertNoRaw('Out of date');
    $this->assertRaw('Mismatched entity and/or field definitions');

    // Enable a db update and check that now the entity update status report
    // item is no longer displayed. We assume an update function will fix the
    // mismatch.
    $this->enableUpdates('entity_test', 'status_report', 8001);
    $this->drupalGet('admin/reports/status');
    $this->assertRaw('Out of date');
    $this->assertRaw('Mismatched entity and/or field definitions');

    // Apply db updates and check that entity updates were not applied.
    $this->applyUpdates();
    $this->drupalGet('admin/reports/status');
    $this->assertNoRaw('Out of date');
    $this->assertRaw('Mismatched entity and/or field definitions');

    // Check that en exception would be triggered when trying to apply them with
    // existing data.
    $message = 'Entity updates cannot run if entity data exists.';
    try {
      $this->updatesManager->applyUpdates();
      $this->fail($message);
    }
    catch (FieldStorageDefinitionUpdateForbiddenException $e) {
      $this->pass($message);
    }

    // Check the status report is the same after trying to apply updates.
    $this->drupalGet('admin/reports/status');
    $this->assertNoRaw('Out of date');
    $this->assertRaw('Mismatched entity and/or field definitions');

    // Delete entity data, enable a new update, run updates again and check that
    // entity updates were not applied even when no data exists.
    $entity->delete();
    $this->enableUpdates('entity_test', 'status_report', 8002);
    $this->applyUpdates();
    $this->drupalGet('admin/reports/status');
    $this->assertNoRaw('Out of date');
    $this->assertRaw('Mismatched entity and/or field definitions');
  }

  /**
   * Reloads the specified entity.
   *
   * @param \Drupal\entity_test\Entity\EntityTest $entity
   *   An entity object.
   *
   * @return \Drupal\entity_test\Entity\EntityTest
   *   The reloaded entity object.
   */
  protected function reloadEntity(EntityTest $entity) {
    $this->entityManager->useCaches(FALSE);
    $this->entityManager->getStorage('entity_test')->resetCache([$entity->id()]);
    return EntityTest::load($entity->id());
  }

}
