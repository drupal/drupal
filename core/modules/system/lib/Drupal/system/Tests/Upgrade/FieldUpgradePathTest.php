<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Upgrade\FieldUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\field\Entity\Field;

/**
 * Tests upgrade of system variables.
 */
class FieldUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Field upgrade test',
      'description' => 'Tests upgrade of Field API.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.field.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests upgrade of entity displays.
   */
  public function testEntityDisplayUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Check that the configuration entries were created.
    $displays = array(
      'default' => \Drupal::config('entity.display.node.article.default')->get(),
      'teaser' => \Drupal::config('entity.display.node.article.teaser')->get(),
    );
    $this->assertTrue(!empty($displays['default']));
    $this->assertTrue(!empty($displays['teaser']));

    // Check that the 'body' field is configured as expected.
    $expected = array(
      'default' => array(
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
        'settings' => array(),
      ),
      'teaser' => array(
        'label' => 'hidden',
        'type' => 'text_summary_or_trimmed',
        'weight' => 0,
        'settings' => array(
          'trim_length' => 600,
        ),
      ),
    );
    $this->assertEqual($displays['default']['content']['body'], $expected['default']);
    $this->assertEqual($displays['teaser']['content']['body'], $expected['teaser']);

    // Check that the display key in the instance data was removed.
    $body_instance = field_info_instance('node', 'body', 'article');
    $this->assertTrue(!isset($body_instance['display']));

    // Check that the 'language' extra field is configured as expected.
    $expected = array(
      'default' => array(
        'weight' => -1,
        'visible' => 1,
      ),
      'teaser' => array(
        'visible' => 0,
      ),
    );
    $this->assertEqual($displays['default']['content']['language'], $expected['default']);
    $this->assertEqual($displays['teaser']['content']['language'], $expected['teaser']);
  }

  /**
   * Tests upgrade of entity form displays.
   */
  public function testEntityFormDisplayUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Check that the configuration entries were created.
    $form_display = \Drupal::config('entity.form_display.node.article.default')->get();
    $this->assertTrue(!empty($form_display));

    // Check that the 'body' field is configured as expected.
    $expected = array(
      'type' => 'text_textarea_with_summary',
      'weight' => -4,
      'settings' => array(
        'rows' => '20',
        'summary_rows' => '5',
      ),
    );
    $this->assertEqual($form_display['content']['body'], $expected);

    // Check that the display key in the instance data was removed.
    $body_instance = field_info_instance('node', 'body', 'article');
    $this->assertTrue(!isset($body_instance['widget']));

    // Check that the 'title' extra field is configured as expected.
    $expected = array(
      'weight' => -5,
      'visible' => 1,
    );
    $this->assertEqual($form_display['content']['title'], $expected);
  }

  /**
   * Tests migration of field and instance definitions to config.
   */
  function testFieldUpgradeToConfig() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Check that the configuration for the 'body' field is correct.
    $config = \Drupal::config('field.field.node.body')->get();
    // We cannot predict the value of the UUID, we just check it's present.
    $this->assertFalse(empty($config['uuid']));
    $field_uuid = $config['uuid'];
    unset($config['uuid']);
    $this->assertEqual($config, array(
      'id' => 'node.body',
      'name' => 'body',
      'type' => 'text_with_summary',
      'module' => 'text',
      'active' => '1',
      'entity_type' => 'node',
      'settings' => array(),
      'locked' => 0,
      'cardinality' => 1,
      'translatable' => 0,
      'indexes' => array(
        'format' => array('format')
      ),
      'status' => 1,
      'langcode' => 'und',
    ));

    // Check that the configuration for the instance on article and page nodes
    // is correct.
    foreach (array('article', 'page') as $node_type) {
      $config = \Drupal::config("field.instance.node.$node_type.body")->get();
      // We cannot predict the value of the UUID, we just check it's present.
      $this->assertFalse(empty($config['uuid']));
      unset($config['uuid']);
      $this->assertEqual($config, array(
        'id' => "node.$node_type.body",
        'field_uuid' => $field_uuid,
        'field_type' => 'text_with_summary',
        'entity_type' => 'node',
        'bundle' => $node_type,
        'label' => 'Body',
        'description' => '',
        'required' => FALSE,
        'default_value' => array(),
        'default_value_function' => '',
        'settings' => array(
          'display_summary' => TRUE,
          'text_processing' => 1,
          // This setting has been removed in field_update_8005(). We keep it
          // here, commented out, to prove that the upgrade path is working.
          //'user_register_form' => FALSE,
        ),
        'status' => 1,
        'langcode' => 'und',
      ));
    }

    // Check that the field that was shared in two entity types got split into
    // two separate config entities.
    $config = \Drupal::config('field.field.node.test_shared_field')->get();
    // We cannot predict the value of the UUID, we just check it's present.
    $this->assertFalse(empty($config['uuid']));
    $field_uuid_node = $config['uuid'];
    unset($config['uuid']);
    $this->assertEqual($config, array(
      'id' => 'node.test_shared_field',
      'name' => 'test_shared_field',
      'type' => 'text',
      'module' => 'text',
      'active' => '1',
      'entity_type' => 'node',
      'settings' => array(
        'max_length' => '255',
      ),
      'locked' => 0,
      'cardinality' => 1,
      'translatable' => 0,
      'indexes' => array(
        'format' => array('format')
      ),
      'status' => 1,
      'langcode' => 'und',
    ));
    $config = \Drupal::config('field.field.user.test_shared_field')->get();
    // We cannot predict the value of the UUID, we just check it's present.
    $this->assertFalse(empty($config['uuid']));
    $field_uuid_user = $config['uuid'];
    unset($config['uuid']);
    $this->assertEqual($config, array(
      'id' => 'user.test_shared_field',
      'name' => 'test_shared_field',
      'type' => 'text',
      'module' => 'text',
      'active' => '1',
      'entity_type' => 'user',
      'settings' => array(
        'max_length' => '255',
      ),
      'locked' => 0,
      'cardinality' => 1,
      'translatable' => 0,
      'indexes' => array(
        'format' => array('format')
      ),
      'status' => 1,
      'langcode' => 'und',
    ));

    // Check that the corresponding instances point to the correct field UUIDs.
    $config = \Drupal::config('field.instance.node.article.test_shared_field')->get();
    $this->assertEqual($config['field_uuid'], $field_uuid_node);
    $config = \Drupal::config('field.instance.user.user.test_shared_field')->get();
    $this->assertEqual($config['field_uuid'], $field_uuid_user);

    // Check that field values in the pre-existing node are read correctly.
    $node = node_load(1);
    $this->assertEqual($node->body->value, 'Some value');
    $this->assertEqual($node->body->summary, 'Some summary');
    $this->assertEqual($node->body->format, 'filtered_html');
    $this->assertEqual($node->test_shared_field->value, 'Shared field: value for node 1');
    $this->assertEqual($node->test_shared_field->format, 'filtered_html');
    // Check that field values in the pre-existing user are read correctly.
    $account = user_load(1);
    $this->assertEqual($account->test_shared_field->value, 'Shared field: value for user 1');
    $this->assertEqual($account->test_shared_field->format, 'filtered_html');

    // Check that the definition of a deleted field is stored in state rather
    // than config.
    $this->assertFalse(\Drupal::config('field.field.test_deleted_field')->get());
    // The array is keyed by UUID. We cannot predict the UUID of the
    // 'test_deleted_field' field, but assume there was only one deleted field
    // in the test database.
    $deleted_fields = \Drupal::state()->get('field.field.deleted');
    $uuid_key = key($deleted_fields);
    $deleted_field = $deleted_fields[$uuid_key];
    $this->assertEqual($deleted_field['uuid'], $uuid_key);
    $this->assertEqual($deleted_field['id'], 'node.test_deleted_field');

    // Check that the definition of a deleted instance is stored in state rather
    // than config.
    $this->assertFalse(\Drupal::config('field.instance.node.article.test_deleted_field')->get());
    $deleted_instances = \Drupal::state()->get('field.instance.deleted');
    // Assume there was only one deleted instance in the test database.
    $uuid_key = key($deleted_instances);
    $deleted_instance = $deleted_instances[$uuid_key];
    $this->assertEqual($deleted_instance['uuid'], $uuid_key);
    $this->assertEqual($deleted_instance['id'], 'node.article.test_deleted_field');
    // The deleted field uuid and deleted instance field_uuid must match.
    $this->assertEqual($deleted_field['uuid'], $deleted_instance['field_uuid']);

    // Check that pre-existing deleted field table is renamed correctly.
    $field_entity = new Field($deleted_field);
    $table_name = DatabaseStorageController::_fieldTableName($field_entity);
    $this->assertEqual("field_deleted_data_" . substr(hash('sha256', $deleted_field['uuid']), 0, 10), $table_name);
    $this->assertTrue(db_table_exists($table_name));

    // Check that creation of a new node works as expected.
    $value = $this->randomName();
    $edit = array(
      'title' => 'Node after CMI conversion',
      'body[und][0][value]' => $value,
    );
    $this->drupalPostForm('node/add/article', $edit, 'Save and publish');
    $this->assertText($value);
  }
}
