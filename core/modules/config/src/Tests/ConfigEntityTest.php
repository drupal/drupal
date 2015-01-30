<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigEntityTest.
 */

namespace Drupal\config\Tests;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Config\Entity\Exception\ConfigEntityIdLengthException;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests configuration entities.
 *
 * @group config
 */
class ConfigEntityTest extends WebTestBase {

  /**
   * The maximum length for the entity storage used in this test.
   */
  const MAX_ID_LENGTH = ConfigEntityStorage::MAX_ID_LENGTH;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test');

  /**
   * Tests CRUD operations.
   */
  function testCRUD() {
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    // Verify default properties on a newly created empty entity.
    $empty = entity_create('config_test');
    $this->assertTrue($empty->uuid());
    $this->assertIdentical($empty->label, NULL);
    $this->assertIdentical($empty->style, NULL);
    $this->assertIdentical($empty->language()->getId(), $default_langcode);

    // Verify ConfigEntity properties/methods on the newly created empty entity.
    $this->assertIdentical($empty->isNew(), TRUE);
    $this->assertIdentical($empty->getOriginalId(), NULL);
    $this->assertIdentical($empty->bundle(), 'config_test');
    $this->assertIdentical($empty->id(), NULL);
    $this->assertTrue($empty->uuid());
    $this->assertIdentical($empty->label(), NULL);

    $this->assertIdentical($empty->get('id'), NULL);
    $this->assertTrue($empty->get('uuid'));
    $this->assertIdentical($empty->get('label'), NULL);
    $this->assertIdentical($empty->get('style'), NULL);
    $this->assertIdentical($empty->language()->getId(), $default_langcode);

    // Verify Entity properties/methods on the newly created empty entity.
    $this->assertIdentical($empty->getEntityTypeId(), 'config_test');
    // The URI can only be checked after saving.
    try {
      $empty->urlInfo();
      $this->fail('EntityMalformedException was thrown.');
    }
    catch (EntityMalformedException $e) {
      $this->pass('EntityMalformedException was thrown.');
    }

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
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'style' => $this->randomMachineName(),
    ));
    $this->assertTrue($config_test->uuid());
    $this->assertNotEqual($config_test->uuid(), $empty->uuid());
    $this->assertIdentical($config_test->label, $expected['label']);
    $this->assertIdentical($config_test->style, $expected['style']);
    $this->assertIdentical($config_test->language()->getId(), $default_langcode);

    // Verify methods on the newly created entity.
    $this->assertIdentical($config_test->isNew(), TRUE);
    $this->assertIdentical($config_test->getOriginalId(), $expected['id']);
    $this->assertIdentical($config_test->id(), $expected['id']);
    $this->assertTrue($config_test->uuid());
    $expected['uuid'] = $config_test->uuid();
    $this->assertIdentical($config_test->label(), $expected['label']);

    // Verify that the entity can be saved.
    try {
      $status = $config_test->save();
      $this->pass('EntityMalformedException was not thrown.');
    }
    catch (EntityMalformedException $e) {
      $this->fail('EntityMalformedException was not thrown.');
    }

    // The entity path can only be checked after saving.
    $this->assertIdentical($config_test->url(), Url::fromRoute('entity.config_test.edit_form', ['config_test' => $expected['id']])->toString());

    // Verify that the correct status is returned and properties did not change.
    $this->assertIdentical($status, SAVED_NEW);
    $this->assertIdentical($config_test->id(), $expected['id']);
    $this->assertIdentical($config_test->uuid(), $expected['uuid']);
    $this->assertIdentical($config_test->label(), $expected['label']);
    $this->assertIdentical($config_test->isNew(), FALSE);
    $this->assertIdentical($config_test->getOriginalId(), $expected['id']);

    // Save again, and verify correct status and properties again.
    $status = $config_test->save();
    $this->assertIdentical($status, SAVED_UPDATED);
    $this->assertIdentical($config_test->id(), $expected['id']);
    $this->assertIdentical($config_test->uuid(), $expected['uuid']);
    $this->assertIdentical($config_test->label(), $expected['label']);
    $this->assertIdentical($config_test->isNew(), FALSE);
    $this->assertIdentical($config_test->getOriginalId(), $expected['id']);

    // Verify that a configuration entity can be saved with an ID of the
    // maximum allowed length, but not longer.

    // Test with a short ID.
    $id_length_config_test = entity_create('config_test', array(
      'id' => $this->randomMachineName(8),
    ));
    try {
      $id_length_config_test->save();
      $this->pass(String::format("config_test entity with ID length @length was saved.", array(
        '@length' => strlen($id_length_config_test->id()))
      ));
    }
    catch (ConfigEntityIdLengthException $e) {
      $this->fail($e->getMessage());
    }

    // Test with an ID of the maximum allowed length.
    $id_length_config_test = entity_create('config_test', array(
      'id' => $this->randomMachineName(static::MAX_ID_LENGTH),
    ));
    try {
      $id_length_config_test->save();
      $this->pass(String::format("config_test entity with ID length @length was saved.", array(
        '@length' => strlen($id_length_config_test->id()),
      )));
    }
    catch (ConfigEntityIdLengthException $e) {
      $this->fail($e->getMessage());
    }

    // Test with an ID exeeding the maximum allowed length.
    $id_length_config_test = entity_create('config_test', array(
      'id' => $this->randomMachineName(static::MAX_ID_LENGTH + 1),
    ));
    try {
      $status = $id_length_config_test->save();
      $this->fail(String::format("config_test entity with ID length @length exceeding the maximum allowed length of @max saved successfully", array(
        '@length' => strlen($id_length_config_test->id()),
        '@max' => static::MAX_ID_LENGTH,
      )));
    }
    catch (ConfigEntityIdLengthException $e) {
      $this->pass(String::format("config_test entity with ID length @length exceeding the maximum allowed length of @max failed to save", array(
        '@length' => strlen($id_length_config_test->id()),
        '@max' => static::MAX_ID_LENGTH,
      )));
    }

    // Ensure that creating an entity with the same id as an existing one is not
    // possible.
    $same_id = entity_create('config_test', array(
      'id' => $config_test->id(),
    ));
    $this->assertIdentical($same_id->isNew(), TRUE);
    try {
      $same_id->save();
      $this->fail('Not possible to overwrite an entity entity.');
    } catch (EntityStorageException $e) {
      $this->pass('Not possible to overwrite an entity entity.');
    }

    // Verify that renaming the ID returns correct status and properties.
    $ids = array($expected['id'], 'second_' . $this->randomMachineName(4), 'third_' . $this->randomMachineName(4));
    for ($i = 1; $i < 3; $i++) {
      $old_id = $ids[$i - 1];
      $new_id = $ids[$i];
      // Before renaming, everything should point to the current ID.
      $this->assertIdentical($config_test->id(), $old_id);
      $this->assertIdentical($config_test->getOriginalId(), $old_id);

      // Rename.
      $config_test->set('id', $new_id);
      $this->assertIdentical($config_test->id(), $new_id);
      $status = $config_test->save();
      $this->assertIdentical($status, SAVED_UPDATED);
      $this->assertIdentical($config_test->isNew(), FALSE);

      // Verify that originalID points to new ID directly after renaming.
      $this->assertIdentical($config_test->id(), $new_id);
      $this->assertIdentical($config_test->getOriginalId(), $new_id);
    }

    // Test config entity prepopulation.
    \Drupal::state()->set('config_test.prepopulate', TRUE);
    $config_test = entity_create('config_test', array('foo' => 'bar'));
    $this->assertEqual($config_test->get('foo'), 'baz', 'Initial value correctly populated');
  }

  /**
   * Tests CRUD operations through the UI.
   */
  function testCRUDUI() {
    $id = strtolower($this->randomMachineName());
    $label1 = $this->randomMachineName();
    $label2 = $this->randomMachineName();
    $label3 = $this->randomMachineName();
    $message_insert = format_string('%label configuration has been created.', array('%label' => $label1));
    $message_update = format_string('%label configuration has been updated.', array('%label' => $label2));
    $message_delete = format_string('The test configuration %label has been deleted.', array('%label' => $label2));

    // Create a configuration entity.
    $edit = array(
      'id' => $id,
      'label' => $label1,
    );
    $this->drupalPostForm('admin/structure/config_test/add', $edit, 'Save');
    $this->assertUrl('admin/structure/config_test');
    $this->assertResponse(200);
    $this->assertRaw($message_insert);
    $this->assertNoRaw($message_update);
    $this->assertLinkByHref("admin/structure/config_test/manage/$id");

    // Update the configuration entity.
    $edit = array(
      'label' => $label2,
    );
    $this->drupalPostForm("admin/structure/config_test/manage/$id", $edit, 'Save');
    $this->assertUrl('admin/structure/config_test');
    $this->assertResponse(200);
    $this->assertNoRaw($message_insert);
    $this->assertRaw($message_update);
    $this->assertLinkByHref("admin/structure/config_test/manage/$id");
    $this->assertLinkByHref("admin/structure/config_test/manage/$id/delete");

    // Delete the configuration entity.
    $this->drupalGet("admin/structure/config_test/manage/$id");
    $this->clickLink(t('Delete'));
    $this->assertUrl("admin/structure/config_test/manage/$id/delete");
    $this->drupalPostForm(NULL, array(), 'Delete');
    $this->assertUrl('admin/structure/config_test');
    $this->assertResponse(200);
    $this->assertNoRaw($message_update);
    $this->assertRaw($message_delete);
    $this->assertNoText($label1);
    $this->assertNoLinkByHref("admin/structure/config_test/manage/$id");

    // Re-create a configuration entity.
    $edit = array(
      'id' => $id,
      'label' => $label1,
    );
    $this->drupalPostForm('admin/structure/config_test/add', $edit, 'Save');
    $this->assertUrl('admin/structure/config_test');
    $this->assertResponse(200);
    $this->assertText($label1);
    $this->assertLinkByHref("admin/structure/config_test/manage/$id");

    // Rename the configuration entity's ID/machine name.
    $edit = array(
      'id' => strtolower($this->randomMachineName()),
      'label' => $label3,
    );
    $this->drupalPostForm("admin/structure/config_test/manage/$id", $edit, 'Save');
    $this->assertUrl('admin/structure/config_test');
    $this->assertResponse(200);
    $this->assertNoText($label1);
    $this->assertNoText($label2);
    $this->assertText($label3);
    $this->assertNoLinkByHref("admin/structure/config_test/manage/$id");
    $id = $edit['id'];
    $this->assertLinkByHref("admin/structure/config_test/manage/$id");

    // Create a configuration entity with '0' machine name.
    $edit = array(
      'id' => '0',
      'label' => '0',
    );
    $this->drupalPostForm('admin/structure/config_test/add', $edit, 'Save');
    $this->assertResponse(200);
    $message_insert = format_string('%label configuration has been created.', array('%label' => $edit['label']));
    $this->assertRaw($message_insert);
    $this->assertLinkByHref('admin/structure/config_test/manage/0');
    $this->assertLinkByHref('admin/structure/config_test/manage/0/delete');
    $this->drupalPostForm('admin/structure/config_test/manage/0/delete', array(), 'Delete');
    $this->assertFalse(entity_load('config_test', '0'), 'Test entity deleted');

  }

}
