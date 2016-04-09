<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\KernelTests\KernelTestBase;

/**
 * Calculating the difference between two sets of configuration.
 *
 * @group config
 */
class ConfigDiffTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test', 'system');

  /**
   * Tests calculating the difference between two sets of configuration.
   */
  function testDiff() {
    $active = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');
    $config_name = 'config_test.system';
    $change_key = 'foo';
    $remove_key = '404';
    $add_key = 'biff';
    $add_data = 'bangpow';
    $change_data = 'foobar';

    // Install the default config.
    $this->installConfig(array('config_test'));
    $original_data = \Drupal::config($config_name)->get();

    // Change a configuration value in sync.
    $sync_data = $original_data;
    $sync_data[$change_key] = $change_data;
    $sync_data[$add_key] = $add_data;
    $sync->write($config_name, $sync_data);

    // Verify that the diff reflects a change.
    $diff = \Drupal::service('config.manager')->diff($active, $sync, $config_name);
    $edits = $diff->getEdits();
    $this->assertEqual($edits[0]->type, 'change', 'The first item in the diff is a change.');
    $this->assertEqual($edits[0]->orig[0], $change_key . ': ' . $original_data[$change_key], format_string("The active value for key '%change_key' is '%original_data'.", array('%change_key' => $change_key, '%original_data' => $original_data[$change_key])));
    $this->assertEqual($edits[0]->closing[0], $change_key . ': ' . $change_data, format_string("The sync value for key '%change_key' is '%change_data'.", array('%change_key' => $change_key, '%change_data' => $change_data)));

    // Reset data back to original, and remove a key
    $sync_data = $original_data;
    unset($sync_data[$remove_key]);
    $sync->write($config_name, $sync_data);

    // Verify that the diff reflects a removed key.
    $diff = \Drupal::service('config.manager')->diff($active, $sync, $config_name);
    $edits = $diff->getEdits();
    $this->assertEqual($edits[0]->type, 'copy', 'The first item in the diff is a copy.');
    $this->assertEqual($edits[1]->type, 'delete', 'The second item in the diff is a delete.');
    $this->assertEqual($edits[1]->orig[0], $remove_key . ': ' . $original_data[$remove_key], format_string("The active value for key '%remove_key' is '%original_data'.", array('%remove_key' => $remove_key, '%original_data' => $original_data[$remove_key])));
    $this->assertFalse($edits[1]->closing, format_string("The key '%remove_key' does not exist in sync.", array('%remove_key' => $remove_key)));

    // Reset data back to original and add a key
    $sync_data = $original_data;
    $sync_data[$add_key] = $add_data;
    $sync->write($config_name, $sync_data);

    // Verify that the diff reflects an added key.
    $diff = \Drupal::service('config.manager')->diff($active, $sync, $config_name);
    $edits = $diff->getEdits();
    $this->assertEqual($edits[0]->type, 'copy', 'The first item in the diff is a copy.');
    $this->assertEqual($edits[1]->type, 'add', 'The second item in the diff is an add.');
    $this->assertFalse($edits[1]->orig, format_string("The key '%add_key' does not exist in active.", array('%add_key' => $add_key)));
    $this->assertEqual($edits[1]->closing[0], $add_key . ': ' . $add_data, format_string("The sync value for key '%add_key' is '%add_data'.", array('%add_key' => $add_key, '%add_data' => $add_data)));

    // Test diffing a renamed config entity.
    $test_entity_id = $this->randomMachineName();
    $test_entity = entity_create('config_test', array(
      'id' => $test_entity_id,
      'label' => $this->randomMachineName(),
    ));
    $test_entity->save();
    $data = $active->read('config_test.dynamic.' . $test_entity_id);
    $sync->write('config_test.dynamic.' . $test_entity_id, $data);
    $config_name = 'config_test.dynamic.' . $test_entity_id;
    $diff = \Drupal::service('config.manager')->diff($active, $sync, $config_name, $config_name);
    // Prove the fields match.
    $edits = $diff->getEdits();
    $this->assertEqual($edits[0]->type, 'copy', 'The first item in the diff is a copy.');
    $this->assertEqual(count($edits), 1, 'There is one item in the diff');

    // Rename the entity.
    $new_test_entity_id = $this->randomMachineName();
    $test_entity->set('id', $new_test_entity_id);
    $test_entity->save();

    $diff = \Drupal::service('config.manager')->diff($active, $sync, 'config_test.dynamic.' . $new_test_entity_id, $config_name);
    $edits = $diff->getEdits();
    $this->assertEqual($edits[0]->type, 'copy', 'The first item in the diff is a copy.');
    $this->assertEqual($edits[1]->type, 'change', 'The second item in the diff is a change.');
    $this->assertEqual($edits[1]->orig, array('id: ' . $new_test_entity_id));
    $this->assertEqual($edits[1]->closing, array('id: ' . $test_entity_id));
    $this->assertEqual($edits[2]->type, 'copy', 'The third item in the diff is a copy.');
    $this->assertEqual(count($edits), 3, 'There are three items in the diff.');
  }

  /**
   * Tests calculating the difference between two sets of config collections.
   */
  function testCollectionDiff() {
    /** @var \Drupal\Core\Config\StorageInterface $active */
    $active = $this->container->get('config.storage');
    /** @var \Drupal\Core\Config\StorageInterface $sync */
    $sync = $this->container->get('config.storage.sync');
    $active_test_collection = $active->createCollection('test');
    $sync_test_collection = $sync->createCollection('test');

    $config_name = 'config_test.test';
    $data = array('foo' => 'bar');

    $active->write($config_name, $data);
    $sync->write($config_name, $data);
    $active_test_collection->write($config_name, $data);
    $sync_test_collection->write($config_name, array('foo' => 'baz'));

    // Test the fields match in the default collection diff.
    $diff = \Drupal::service('config.manager')->diff($active, $sync, $config_name);
    $edits = $diff->getEdits();
    $this->assertEqual($edits[0]->type, 'copy', 'The first item in the diff is a copy.');
    $this->assertEqual(count($edits), 1, 'There is one item in the diff');

    // Test that the differences are detected when diffing the collection.
    $diff = \Drupal::service('config.manager')->diff($active, $sync, $config_name, NULL, 'test');
    $edits = $diff->getEdits();
    $this->assertEqual($edits[0]->type, 'change', 'The second item in the diff is a copy.');
    $this->assertEqual($edits[0]->orig, array('foo: bar'));
    $this->assertEqual($edits[0]->closing, array('foo: baz'));
    $this->assertEqual($edits[1]->type, 'copy', 'The second item in the diff is a copy.');
  }

}
