<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\FieldInstancePerViewModeTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Tests the Drupal 6 field instance per view mode source.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class FieldInstancePerViewModeTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\FieldInstancePerViewMode';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'view_mode_test',
    // Leave it empty for now.
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_field_instance_per_view_mode',
    ),
  );

  protected $expectedResults = array(
    array(
      'entity_type' => 'node',
      'view_mode' => 4,
      'type_name' => 'article',
      'field_name' => 'field_test',
      'type' => 'text',
      'module' => 'text',
      'weight' => 1,
      'label' => 'above',
      'display_settings' => array(
        'weight' => 1,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        4 => array(
          'format' => 'trimmed',
          'exclude' => 0,
        ),
      ),
      'widget_settings' => array(),
    ),
    array(
      'entity_type' => 'node',
      'view_mode' => 'teaser',
      'type_name' => 'story',
      'field_name' => 'field_test',
      'type' => 'text',
      'module' => 'text',
      'weight' => 1,
      'label' => 'above',
      'display_settings' => array(
        'weight' => 1,
        'parent' => '',
        'label' => array(
          'format' => 'above',
        ),
        'teaser' => array(
          'format' => 'trimmed',
          'exclude' => 0,
        ),
      ),
      'widget_settings' => array(),
    ),
  );


  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 per view mode source functionality',
      'description' => 'Tests D6 fields per view mode source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $k => $field_view_mode) {
      // These are stored as serialized strings.
      $field_view_mode['display_settings'] = serialize($field_view_mode['display_settings']);
      $field_view_mode['widget_settings'] = serialize($field_view_mode['widget_settings']);

      $this->databaseContents['content_node_field'][] = array(
        'field_name' => $field_view_mode['field_name'],
        'type' => $field_view_mode['type'],
        'module' => $field_view_mode['module'],
      );
      unset($field_view_mode['type']);
      unset($field_view_mode['module']);

      $this->databaseContents['content_node_field_instance'][] = $field_view_mode;

      // Update the expected display settings.
      $this->expectedResults[$k]['display_settings'] = $this->expectedResults[$k]['display_settings'][$field_view_mode['view_mode']];

    }
    parent::setUp();
  }

}

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\FieldInstancePerViewMode;

class TestFieldInstancePerViewMode extends FieldInstancePerViewMode {
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}
