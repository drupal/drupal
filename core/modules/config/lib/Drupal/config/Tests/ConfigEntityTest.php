<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigEntityTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\simpletest\WebTestBase;

/**
 * Tests configuration entities.
 */
class ConfigEntityTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test');

  public static function getInfo() {
    return array(
      'name' => 'Configuration entities',
      'description' => 'Tests configuration entities.',
      'group' => 'Configuration',
    );
  }

  /**
   * Tests CRUD operations.
   */
  function testCRUD() {
    // Verify default properties on a newly created empty entity.
    $empty = entity_create('config_test', array());
    $this->assertIdentical($empty->id, NULL);
    $this->assertTrue($empty->uuid);
    $this->assertIdentical($empty->label, NULL);
    $this->assertIdentical($empty->style, NULL);
    $this->assertIdentical($empty->langcode, LANGUAGE_NOT_SPECIFIED);

    // Verify ConfigEntity properties/methods on the newly created empty entity.
    $this->assertIdentical($empty->isNew(), TRUE);
    $this->assertIdentical($empty->getOriginalID(), NULL);
    $this->assertIdentical($empty->bundle(), 'config_test');
    $this->assertIdentical($empty->id(), NULL);
    $this->assertTrue($empty->uuid());
    $this->assertIdentical($empty->label(), NULL);

    $this->assertIdentical($empty->get('id'), NULL);
    $this->assertTrue($empty->get('uuid'));
    $this->assertIdentical($empty->get('label'), NULL);
    $this->assertIdentical($empty->get('style'), NULL);
    $this->assertIdentical($empty->get('langcode'), LANGUAGE_NOT_SPECIFIED);

    // Verify Entity properties/methods on the newly created empty entity.
    $this->assertIdentical($empty->isNewRevision(), FALSE);
    $this->assertIdentical($empty->entityType(), 'config_test');
    $uri = $empty->uri();
    $this->assertIdentical($uri['path'], 'admin/structure/config_test/manage/');
    $this->assertIdentical($empty->isDefaultRevision(), TRUE);

    // Verify that an empty entity cannot be saved.
    try {
      $empty->save();
      $this->fail('EntityMalformedException was thrown.');
    }
    catch (EntityMalformedException $e) {
      $this->pass('EntityMalformedException was thrown.');
    }

    // Verify that an entity with an empty ID string is considered empty, too.
    $empty_id = entity_create('config_test', array(
      'id' => '',
    ));
    $this->assertIdentical($empty_id->isNew(), TRUE);
    try {
      $empty_id->save();
      $this->fail('EntityMalformedException was thrown.');
    }
    catch (EntityMalformedException $e) {
      $this->pass('EntityMalformedException was thrown.');
    }

    // Verify properties on a newly created entity.
    $config_test = entity_create('config_test', $expected = array(
      'id' => $this->randomName(),
      'label' => $this->randomString(),
      'style' => $this->randomName(),
    ));
    $this->assertIdentical($config_test->id, $expected['id']);
    $this->assertTrue($config_test->uuid);
    $this->assertNotEqual($config_test->uuid, $empty->uuid);
    $this->assertIdentical($config_test->label, $expected['label']);
    $this->assertIdentical($config_test->style, $expected['style']);
    $this->assertIdentical($config_test->langcode, LANGUAGE_NOT_SPECIFIED);

    // Verify methods on the newly created entity.
    $this->assertIdentical($config_test->isNew(), TRUE);
    $this->assertIdentical($config_test->getOriginalID(), $expected['id']);
    $this->assertIdentical($config_test->id(), $expected['id']);
    $this->assertTrue($config_test->uuid());
    $expected['uuid'] = $config_test->uuid();
    $this->assertIdentical($config_test->label(), $expected['label']);

    $this->assertIdentical($config_test->isNewRevision(), FALSE);
    $uri = $config_test->uri();
    $this->assertIdentical($uri['path'], 'admin/structure/config_test/manage/' . $expected['id']);
    $this->assertIdentical($config_test->isDefaultRevision(), TRUE);

    // Verify that the entity can be saved.
    try {
      $status = $config_test->save();
      $this->pass('EntityMalformedException was not thrown.');
    }
    catch (EntityMalformedException $e) {
      $this->fail('EntityMalformedException was not thrown.');
    }

    // Verify that the correct status is returned and properties did not change.
    $this->assertIdentical($status, SAVED_NEW);
    $this->assertIdentical($config_test->id(), $expected['id']);
    $this->assertIdentical($config_test->uuid(), $expected['uuid']);
    $this->assertIdentical($config_test->label(), $expected['label']);
    $this->assertIdentical($config_test->isNew(), FALSE);
    $this->assertIdentical($config_test->getOriginalID(), $expected['id']);

    // Save again, and verify correct status and properties again.
    $status = $config_test->save();
    $this->assertIdentical($status, SAVED_UPDATED);
    $this->assertIdentical($config_test->id(), $expected['id']);
    $this->assertIdentical($config_test->uuid(), $expected['uuid']);
    $this->assertIdentical($config_test->label(), $expected['label']);
    $this->assertIdentical($config_test->isNew(), FALSE);
    $this->assertIdentical($config_test->getOriginalID(), $expected['id']);

    // Re-create the entity with the same ID and verify updated status.
    $same_id = entity_create('config_test', array(
      'id' => $config_test->id(),
    ));
    $this->assertIdentical($same_id->isNew(), TRUE);
    $status = $same_id->save();
    $this->assertIdentical($status, SAVED_UPDATED);

    // Verify that the entity was overwritten.
    $same_id = entity_load('config_test', $config_test->id());
    $this->assertIdentical($same_id->id(), $config_test->id());
    // Note: Reloading loads from FileStorage, and FileStorage enforces strings.
    $this->assertIdentical($same_id->label(), '');
    $this->assertNotEqual($same_id->uuid(), $config_test->uuid());

    // Revert to previous state.
    $config_test->save();

    // Verify that renaming the ID returns correct status and properties.
    $ids = array($expected['id'], 'second_' . $this->randomName(4), 'third_' . $this->randomName(4));
    for ($i = 1; $i < 3; $i++) {
      $old_id = $ids[$i - 1];
      $new_id = $ids[$i];
      // Before renaming, everything should point to the current ID.
      $this->assertIdentical($config_test->id(), $old_id);
      $this->assertIdentical($config_test->getOriginalID(), $old_id);

      // Rename.
      $config_test->id = $new_id;
      $this->assertIdentical($config_test->id(), $new_id);
      $status = $config_test->save();
      $this->assertIdentical($status, SAVED_UPDATED);
      $this->assertIdentical($config_test->isNew(), FALSE);

      // Verify that originalID points to new ID directly after renaming.
      $this->assertIdentical($config_test->id(), $new_id);
      $this->assertIdentical($config_test->getOriginalID(), $new_id);
    }

    // Test config entity prepopulation.
    state()->set('config_test.prepopulate', TRUE);
    $config_test = entity_create('config_test', array('foo' => 'bar'));
    $this->assertEqual($config_test->get('foo'), 'baz', 'Initial value correctly populated');
  }

  /**
   * Tests CRUD operations through the UI.
   */
  function testCRUDUI() {
    $id = strtolower($this->randomName());
    $label1 = $this->randomName();
    $label2 = $this->randomName();
    $label3 = $this->randomName();
    $message_insert = format_string('%label configuration has been created.', array('%label' => $label1));
    $message_update = format_string('%label configuration has been updated.', array('%label' => $label2));
    $message_delete = format_string('%label configuration has been deleted.', array('%label' => $label2));

    // Create a configuration entity.
    $edit = array(
      'id' => $id,
      'label' => $label1,
    );
    $this->drupalPost('admin/structure/config_test/add', $edit, 'Save');
    $this->assertUrl('admin/structure/config_test');
    $this->assertResponse(200);
    $this->assertRaw($message_insert);
    $this->assertNoRaw($message_update);
    $this->assertLinkByHref("admin/structure/config_test/manage/$id/edit");

    // Update the configuration entity.
    $edit = array(
      'label' => $label2,
    );
    $this->drupalPost("admin/structure/config_test/manage/$id", $edit, 'Save');
    $this->assertUrl('admin/structure/config_test');
    $this->assertResponse(200);
    $this->assertNoRaw($message_insert);
    $this->assertRaw($message_update);
    $this->assertLinkByHref("admin/structure/config_test/manage/$id/edit");
    $this->assertLinkByHref("admin/structure/config_test/manage/$id/delete");

    // Delete the configuration entity.
    $this->drupalGet("admin/structure/config_test/manage/$id/edit");
    $this->drupalPost(NULL, array(), 'Delete');
    $this->assertUrl("admin/structure/config_test/manage/$id/delete");
    $this->drupalPost(NULL, array(), 'Delete');
    $this->assertUrl('admin/structure/config_test');
    $this->assertResponse(200);
    $this->assertNoRaw($message_update);
    $this->assertRaw($message_delete);
    $this->assertNoText($label1);
    $this->assertNoLinkByHref("admin/structure/config_test/manage/$id");
    $this->assertNoLinkByHref("admin/structure/config_test/manage/$id/edit");

    // Re-create a configuration entity.
    $edit = array(
      'id' => $id,
      'label' => $label1,
    );
    $this->drupalPost('admin/structure/config_test/add', $edit, 'Save');
    $this->assertUrl('admin/structure/config_test');
    $this->assertResponse(200);
    $this->assertText($label1);
    $this->assertLinkByHref("admin/structure/config_test/manage/$id/edit");

    // Rename the configuration entity's ID/machine name.
    $edit = array(
      'id' => strtolower($this->randomName()),
      'label' => $label3,
    );
    $this->drupalPost("admin/structure/config_test/manage/$id", $edit, 'Save');
    $this->assertUrl('admin/structure/config_test');
    $this->assertResponse(200);
    $this->assertNoText($label1);
    $this->assertNoText($label2);
    $this->assertText($label3);
    $this->assertNoLinkByHref("admin/structure/config_test/manage/$id");
    $this->assertNoLinkByHref("admin/structure/config_test/manage/$id/edit");
    $id = $edit['id'];
    $this->assertLinkByHref("admin/structure/config_test/manage/$id/edit");

    // Create a configuration entity with '0' machine name.
    $edit = array(
      'id' => '0',
      'label' => '0',
    );
    $this->drupalPost('admin/structure/config_test/add', $edit, 'Save');
    $this->assertResponse(200);
    $message_insert = format_string('%label configuration has been created.', array('%label' => $edit['label']));
    $this->assertRaw($message_insert);
    $this->assertLinkByHref('admin/structure/config_test/manage/0/edit');
    $this->assertLinkByHref('admin/structure/config_test/manage/0/delete');
    $this->drupalPost('admin/structure/config_test/manage/0/delete', array(), 'Delete');
    $this->assertFalse(entity_load('config_test', '0'), 'Test entity deleted');

  }

}
