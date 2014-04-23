<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\FieldInstanceTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Tests the Drupal 6 field instance source.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class FieldInstanceTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\FieldInstance';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The id of the entity, can be any string.
    'id' => 'test_fieldinstance',
    // Leave it empty for now.
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_fieldinstance',
    ),
  );

  // We need to set up the database contents; it's easier to do that below.
  // These are sample result queries.
  protected $expectedResults = array(
    array(
      'field_name' => 'field_body',
      'type_name' => 'page',
      'weight' => 1,
      'label' => 'body',
      'widget_type' => 'text_textarea',
      'widget_settings' => '',
      'display_settings' => '',
      'description' => '',
      'widget_module' => 'text',
      'widget_active' => 1,
      'required' => 1,
      'active' => 1,
      'global_settings' => array(),
    ),
  );

  /**
   * Prepopulate contents with results.
   */
  protected function setUp() {
    $this->expectedResults[0]['widget_settings'] = array(
      'rows' => 5,
      'size' => 60,
      'default_value' => array(
        array(
          'value' => '',
          '_error_element' => 'default_value_widget][field_body][0][value',
          'default_value_php' => '',
        ),
      ),
    );
    $this->expectedResults[0]['display_settings'] = array(
      'label' => array(
        'format' => 'above',
        'exclude' => 0,
      ),
      'teaser' => array(
        'format' => 'default',
        'exclude' => 0,
      ),
      'full' => array(
        'format' => 'default',
        'exclude' => 0,
      ),
    );
    $this->databaseContents['content_node_field_instance'] = $this->expectedResults;
    $this->databaseContents['content_node_field_instance'][0]['widget_settings'] = serialize($this->expectedResults[0]['widget_settings']);
    $this->databaseContents['content_node_field_instance'][0]['display_settings'] = serialize($this->expectedResults[0]['display_settings']);

    $this->databaseContents['content_node_field'][0] = array(
      'field_name' => 'field_body',
      'required' => 1,
      'type' => 'text',
      'active' => 1,
      'global_settings' => serialize(array()),
    );
    parent::setUp();
  }

  /**
   * Provide meta information about this battery of tests.
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 field instance source functionality',
      'description' => 'Tests D6 field instance source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function testRetrieval() {
    // FakeSelect does not support multiple source identifiers, can not test.
  }

}
namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\FieldInstance;

class TestFieldInstance extends FieldInstance {
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}
