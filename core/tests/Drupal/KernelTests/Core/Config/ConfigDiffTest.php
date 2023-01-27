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
  protected static $modules = ['config_test', 'system'];

  /**
   * Tests calculating the difference between two sets of configuration.
   */
  public function testDiff() {
    $active = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');
    $config_name = 'config_test.system';
    $change_key = 'foo';
    $remove_key = '404';
    $add_key = 'biff';
    $add_data = 'bangpow';
    $change_data = 'foobar';

    // Install the default config.
    $this->installConfig(['config_test']);
    $original_data = \Drupal::config($config_name)->get();

    // Change a configuration value in sync.
    $sync_data = $original_data;
    $sync_data[$change_key] = $change_data;
    $sync_data[$add_key] = $add_data;
    $sync->write($config_name, $sync_data);

    // Verify that the diff reflects a change.
    $diff = \Drupal::service('config.manager')->diff($active, $sync, $config_name);
    $edits = $diff->getEdits();
    $this->assertYamlEdit($edits, $change_key, 'change',
      [$change_key . ': ' . $original_data[$change_key]],
      [$change_key . ': ' . $change_data]);

    // Reset data back to original, and remove a key
    $sync_data = $original_data;
    unset($sync_data[$remove_key]);
    $sync->write($config_name, $sync_data);

    // Verify that the diff reflects a removed key.
    $diff = \Drupal::service('config.manager')->diff($active, $sync, $config_name);
    $edits = $diff->getEdits();
    $this->assertYamlEdit($edits, $change_key, 'copy');
    $this->assertYamlEdit($edits, $remove_key, 'delete',
      [$remove_key . ': ' . $original_data[$remove_key]],
      FALSE
    );

    // Reset data back to original and add a key
    $sync_data = $original_data;
    $sync_data[$add_key] = $add_data;
    $sync->write($config_name, $sync_data);

    // Verify that the diff reflects an added key.
    $diff = \Drupal::service('config.manager')->diff($active, $sync, $config_name);
    $edits = $diff->getEdits();
    $this->assertYamlEdit($edits, $change_key, 'copy');
    $this->assertYamlEdit($edits, $add_key, 'add', FALSE, [$add_key . ': ' . $add_data]);

    // Test diffing a renamed config entity.
    $test_entity_id = $this->randomMachineName();
    $test_entity = \Drupal::entityTypeManager()->getStorage('config_test')->create([
      'id' => $test_entity_id,
      'label' => $this->randomMachineName(),
    ]);
    $test_entity->save();
    $data = $active->read('config_test.dynamic.' . $test_entity_id);
    $sync->write('config_test.dynamic.' . $test_entity_id, $data);
    $config_name = 'config_test.dynamic.' . $test_entity_id;
    $diff = \Drupal::service('config.manager')->diff($active, $sync, $config_name, $config_name);
    // Prove the fields match.
    $edits = $diff->getEdits();
    $this->assertEquals('copy', $edits[0]->type, 'The first item in the diff is a copy.');
    $this->assertCount(1, $edits, 'There is one item in the diff');

    // Rename the entity.
    $new_test_entity_id = $this->randomMachineName();
    $test_entity->set('id', $new_test_entity_id);
    $test_entity->save();

    $diff = \Drupal::service('config.manager')->diff($active, $sync, 'config_test.dynamic.' . $new_test_entity_id, $config_name);
    $edits = $diff->getEdits();
    $this->assertYamlEdit($edits, 'uuid', 'copy');
    $this->assertYamlEdit($edits, 'id', 'change',
      ['id: ' . $new_test_entity_id],
      ['id: ' . $test_entity_id]);
    $this->assertYamlEdit($edits, 'label', 'copy');
    $this->assertEquals('copy', $edits[2]->type, 'The third item in the diff is a copy.');
    $this->assertCount(3, $edits, 'There are three items in the diff.');
  }

  /**
   * Tests calculating the difference between two sets of config collections.
   */
  public function testCollectionDiff() {
    /** @var \Drupal\Core\Config\StorageInterface $active */
    $active = $this->container->get('config.storage');
    /** @var \Drupal\Core\Config\StorageInterface $sync */
    $sync = $this->container->get('config.storage.sync');
    $active_test_collection = $active->createCollection('test');
    $sync_test_collection = $sync->createCollection('test');

    $config_name = 'config_test.test';
    $data = ['foo' => 'bar'];

    $active->write($config_name, $data);
    $sync->write($config_name, $data);
    $active_test_collection->write($config_name, $data);
    $sync_test_collection->write($config_name, ['foo' => 'baz']);

    // Test the fields match in the default collection diff.
    $diff = \Drupal::service('config.manager')->diff($active, $sync, $config_name);
    $edits = $diff->getEdits();
    $this->assertEquals('copy', $edits[0]->type, 'The first item in the diff is a copy.');
    $this->assertCount(1, $edits, 'There is one item in the diff');

    // Test that the differences are detected when diffing the collection.
    $diff = \Drupal::service('config.manager')->diff($active, $sync, $config_name, NULL, 'test');
    $edits = $diff->getEdits();
    $this->assertYamlEdit($edits, 'foo', 'change', ['foo: bar'], ['foo: baz']);
  }

  /**
   * Helper method to test that an edit is found in the diff of two storages.
   *
   * @param array $edits
   *   A list of edits.
   * @param string $field
   *   The field key that is being asserted.
   * @param string $type
   *   The type of edit that is being asserted.
   * @param mixed $orig
   *   (optional) The original value of the edit. If not supplied, assertion
   *   is skipped.
   * @param mixed $closing
   *   (optional) The closing value of the edit. If not supplied, assertion
   *   is skipped.
   *
   * @internal
   */
  protected function assertYamlEdit(array $edits, string $field, string $type, $orig = NULL, $closing = NULL): void {
    $match = FALSE;
    foreach ($edits as $edit) {
      // Choose which section to search for the field.
      $haystack = $type == 'add' ? $edit->closing : $edit->orig;
      // Look through each line and try and find the key.
      if (is_array($haystack)) {
        foreach ($haystack as $item) {
          if (str_starts_with($item, $field . ':')) {
            $match = TRUE;
            // Assert that the edit is of the type specified.
            $this->assertEquals($type, $edit->type, "The {$field} item in the diff is a {$type}");
            // If an original value was given, assert that it matches.
            if (isset($orig)) {
              $this->assertSame($orig, $edit->orig, "The original value for key '{$field}' is correct.");
            }
            // If a closing value was given, assert that it matches.
            if (isset($closing)) {
              $this->assertSame($closing, $edit->closing, "The closing value for key '{$field}' is correct.");
            }
            // Break out of the search entirely.
            break 2;
          }
        }
      }
    }

    // If we didn't match anything, fail.
    if (!$match) {
      $this->fail("$field edit was not matched");
    }
  }

}
