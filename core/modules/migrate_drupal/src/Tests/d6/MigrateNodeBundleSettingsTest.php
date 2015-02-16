<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateNodeBundleSettingsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Test migrating node settings into the base_field_bundle_override config entity.
 *
 * @group migrate_drupal
 */
class MigrateNodeBundleSettingsTest extends MigrateDrupal6TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Setup the bundles.
    entity_create('node_type', array('type' => 'test_page'))->save();
    entity_create('node_type', array('type' => 'test_planet'))->save();
    entity_create('node_type', array('type' => 'test_story'))->save();
    entity_create('node_type', array('type' => 'test_event'))->save();
    entity_create('node_type', array('type' => 'story'))->save();
    entity_create('node_type', array('type' => 'article'))->save();
    entity_create('node_type', array('type' => 'company'))->save();
    entity_create('node_type', array('type' => 'employee'))->save();
    entity_create('node_type', array('type' => 'page'))->save();
    entity_create('node_type', array('type' => 'sponsor'))->save();
    entity_create('node_type', array('type' => 'event'))->save();
    entity_create('node_type', array('type' => 'book'))->save();

    // Create a config entity that already exists.
    entity_create('base_field_override', array('field_name' => 'promote', 'entity_type' => 'node', 'bundle' => 'page',))->save();

    $id_mappings = array(
      'd6_node_type' => array(
        array(array('test_page'), array('test_page')),
        array(array('test_planet'), array('test_planet')),
        array(array('test_story'), array('test_story')),
        array(array('test_event'), array('test_event')),
        array(array('story'), array('story')),
      ),
    );
    $this->prepareMigrations($id_mappings);

    // Setup the dumps.
    $migration = entity_load('migration', 'd6_node_setting_promote');
    $dumps = array(
      $this->getDumpDirectory() . '/NodeType.php',
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);

    // Run the migrations.
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $migration = entity_load('migration', 'd6_node_setting_status');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $migration = entity_load('migration', 'd6_node_setting_sticky');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests Drupal 6 node type settings to Drupal 8 migration.
   */
  public function testNodeBundleSettings() {

    // Test settings on test_page bundle.
    $node = entity_create('node', array('type' => 'test_page'));
    $this->assertIdentical($node->status->value, 1);
    $this->assertIdentical($node->promote->value, 1);
    $this->assertIdentical($node->sticky->value, 1);

    // Test settings for test_story bundle.
    $node = entity_create('node', array('type' => 'test_story'));
    $this->assertIdentical($node->status->value, 1);
    $this->assertIdentical($node->promote->value, 1);
    $this->assertIdentical($node->sticky->value, 0);

    // Test settings for the test_event bundle.
    $node = entity_create('node', array('type' => 'test_event'));
    $this->assertIdentical($node->status->value, 0);
    $this->assertIdentical($node->promote->value, 0);
    $this->assertIdentical($node->sticky->value, 1);
  }

}
