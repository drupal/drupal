<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\NodeRevisionTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Tests D6 node revision source plugin.
 *
 * @group migrate_drupal
 */
class NodeRevisionTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\NodeRevision';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    'id' => 'test',
    // Leave it empty for now.
    'idlist' => array(),
    // The fake configuration for the source.
    'source' => array(
      'node_type' => 'page',
      'plugin' => 'd6_node_revision',
      'bundle' => 'page',
    ),
    'sourceIds' => array(
      'vid' => array(
        'alias' => 'v',
      ),
    ),
    'destinationIds' => array(
      'vid' => array(
        // This is where the field schema would go.
      ),
    ),
  );

  protected $expectedResults = array(
    array(
      // Node fields.
      'nid' => 1,
      'type' => 'page',
      'language' => 'en',
      'status' => 1,
      'created' => 1279051598,
      'changed' => 1279051598,
      'comment' => 2,
      'promote' => 1,
      'moderate' => 0,
      'sticky' => 0,
      'tnid' => 0,
      'translate' => 0,
      // Node revision fields.
      'vid' => 1,
      'uid' => 1,
      'title' => 'title for revision 1 (node 1)',
      'body' => 'body for revision 1 (node 1)',
      'teaser' => 'teaser for revision 1 (node 1)',
      'log' => 'log for revision 1 (node 1)',
      'format' => 1,
      'field_test_one_value' => 'text for node 1',
      'field_test_two' => array(
        'test field node 1, value 1',
        'test field node 1, value 2',
      ),

      // This is just to help with databaseContents and gets unset later.
      'fields' => array(
        'field_test_one' => 'text for node 1',
      ),
    ),
    array(
      // Node fields.
      'nid' => 1,
      'type' => 'page',
      'language' => 'en',
      'status' => 1,
      'created' => 1279051598,
      'changed' => 1279051598,
      'comment' => 2,
      'promote' => 1,
      'moderate' => 0,
      'sticky' => 0,
      'tnid' => 0,
      'translate' => 0,
      // Node revision fields.
      'vid' => 3,
      'uid' => 1,
      'title' => 'title for revision 3 (node 1)',
      'body' => 'body for revision 3 (node 1)',
      'teaser' => 'teaser for revision 3 (node 1)',
      'log' => 'log for revision 3 (node 1)',
      'format' => 1,
      'field_test_one_value' => 'text for node 1',
      'field_test_two' => array(
        'test field node 1, value 1',
        'test field node 1, value 2',
      ),

      // This is just to help with databaseContents and gets unset later.
      'fields' => array(
        'field_test_one' => 'text for node 1',
      ),
    ),
    array(
      // Node fields.
      'nid' => 1,
      'type' => 'page',
      'language' => 'en',
      'status' => 1,
      'created' => 1279051598,
      'changed' => 1279051598,
      'comment' => 2,
      'promote' => 1,
      'moderate' => 0,
      'sticky' => 0,
      'tnid' => 0,
      'translate' => 0,
      // Node revision fields.
      'vid' => 4,
      'uid' => 1,
      'title' => 'title for revision 4 (node 1)',
      'body' => 'body for revision 4 (node 1)',
      'teaser' => 'teaser for revision 4 (node 1)',
      'log' => 'log for revision 4 (node 1)',
      'format' => 1,
      'field_test_one_value' => 'text for node 1',
      'field_test_two' => array(
        'test field node 1, value 1',
        'test field node 1, value 2',
      ),
      // This is just to help with databaseContents and gets unset later.
      'fields' => array(
        'field_test_one' => 'text for node 1',
      ),
    ),
    array(
      // Node fields.
      'nid' => 2,
      'type' => 'page',
      'language' => 'en',
      'status' => 1,
      'created' => 1279290908,
      'changed' => 1279308993,
      'comment' => 0,
      'promote' => 1,
      'moderate' => 0,
      'sticky' => 0,
      'tnid' => 0,
      'translate' => 0,
      // Node revision fields.
      'vid' => 2,
      'uid' => 1,
      'title' => 'title for revision 2 (node 2)',
      'body' => 'body for revision 2 (node 2)',
      'teaser' => 'teaser for revision 2 (node 2)',
      'log' => 'log for revision 2 (node 2)',
      'format' => 1,
      'field_test_two' => array(
        'test field node 2',
      ),

      // This is just to help with databaseContents and gets unset later.
      'fields' => array(
        'field_test_one' => 'text for node 2',
      ),
    ),
  );

  protected $fields = array(
    'field_test_one' => array(
      'content_node_field_instance' => array(
        'field_name' => 'field_test_one',
        'type_name' => 'page',
        'weight' => 1,
        'label' => 'Field Label One',
        'widget_type' => 'text_textfield',
        'widget_settings' => 'a:4:{s:4:"rows";i:5;s:4:"size";s:2:"60";s:13:"default_value";a:1:{i:0;a:2:{s:5:"value";s:0:"";s:14:"_error_element";s:42:"default_value_widget][field_test][0][value";}}s:17:"default_value_php";N;}',
        'display_settings' => 'a:6:{s:6:"weight";s:2:"31";s:6:"parent";s:0:"";s:5:"label";a:1:{s:6:"format";s:5:"above";}s:6:"teaser";a:2:{s:6:"format";s:7:"default";s:7:"exclude";i:0;}s:4:"full";a:2:{s:6:"format";s:7:"default";s:7:"exclude";i:0;}i:4;a:2:{s:6:"format";s:7:"default";s:7:"exclude";i:0;}}',
        'description' => '',
        'widget_module' => 'text',
        'widget_active' => 1,
      ),
      'content_node_field' => array(
        'field_name' => 'field_test_one',
        'type' => 'text',
        'global_settings' => 'a:4:{s:15:"text_processing";s:1:"0";s:10:"max_length";s:0:"";s:14:"allowed_values";s:0:"";s:18:"allowed_values_php";s:0:"";}',
        'required' => 0,
        'multiple' => 0,
        'db_storage' => 1,
        'module' => 'text',
        'db_columns' => 'a:1:{s:5:"value";a:5:{s:4:"type";s:4:"text";s:4:"size";s:3:"big";s:8:"not null";b:0;s:8:"sortable";b:1;s:5:"views";b:1;}}',
        'active' => 1,
        'locked' => 0,
      ),
    ),
    'field_test_two' => array(
      'content_node_field_instance' => array(
        'field_name' => 'field_test_two',
        'type_name' => 'page',
        'weight' => 1,
        'label' => 'Field Label One',
        'widget_type' => 'text_textfield',
        'widget_settings' => 'a:4:{s:4:"rows";i:5;s:4:"size";s:2:"60";s:13:"default_value";a:1:{i:0;a:2:{s:5:"value";s:0:"";s:14:"_error_element";s:42:"default_value_widget][field_test][0][value";}}s:17:"default_value_php";N;}',
        'display_settings' => 'a:6:{s:6:"weight";s:2:"31";s:6:"parent";s:0:"";s:5:"label";a:1:{s:6:"format";s:5:"above";}s:6:"teaser";a:2:{s:6:"format";s:7:"default";s:7:"exclude";i:0;}s:4:"full";a:2:{s:6:"format";s:7:"default";s:7:"exclude";i:0;}i:4;a:2:{s:6:"format";s:7:"default";s:7:"exclude";i:0;}}',
        'description' => '',
        'widget_module' => 'text',
        'widget_active' => 1,
      ),
      'content_node_field' => array(
        'field_name' => 'field_test_two',
        'type' => 'text',
        'global_settings' => 'a:4:{s:15:"text_processing";s:1:"0";s:10:"max_length";s:0:"";s:14:"allowed_values";s:0:"";s:18:"allowed_values_php";s:0:"";}',
        'required' => 0,
        'multiple' => 1,
        'db_storage' => 0,
        'module' => 'text',
        'db_columns' => 'a:1:{s:5:"value";a:5:{s:4:"type";s:4:"text";s:4:"size";s:3:"big";s:8:"not null";b:0;s:8:"sortable";b:1;s:5:"views";b:1;}}',
        'active' => 1,
        'locked' => 0,
      ),

      // Multi field values.
      'values' => array(
        array(
          'vid' => 1,
          'nid' => 1,
          'field_test_two_value' => 'test field node 1, value 1',
          'delta' => 0,
        ),
        array(
          'vid' => 1,
          'nid' => 1,
          'field_test_two_value' => 'test field node 1, value 2',
          'delta' => 1,
        ),
        array(
          'vid' => 3,
          'nid' => 1,
          'field_test_two_value' => 'test field node 1, value 1',
          'delta' => 0,
        ),
        array(
          'vid' => 3,
          'nid' => 1,
          'field_test_two_value' => 'test field node 1, value 2',
          'delta' => 1,
        ),
        array(
          'vid' => 4,
          'nid' => 1,
          'field_test_two_value' => 'test field node 1, value 1',
          'delta' => 0,
        ),
        array(
          'vid' => 4,
          'nid' => 1,
          'field_test_two_value' => 'test field node 1, value 2',
          'delta' => 1,
        ),
        array(
          'vid' => 2,
          'nid' => 2,
          'field_test_two_value' => 'test field node 2',
          'delta' => 0,
        ),
      ),
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $k => $row) {
      $this->databaseContents['node_revisions'][$k]['nid'] = $row['nid'];
      $this->databaseContents['node_revisions'][$k]['vid'] = $row['vid'];
      $this->databaseContents['node_revisions'][$k]['uid'] = $row['uid'];
      $this->databaseContents['node_revisions'][$k]['title'] = $row['title'];
      $this->databaseContents['node_revisions'][$k]['body'] = $row['body'];
      $this->databaseContents['node_revisions'][$k]['teaser'] = $row['teaser'];
      $this->databaseContents['node_revisions'][$k]['format'] = $row['format'];
      $this->databaseContents['node_revisions'][$k]['log'] = $row['log'];

      unset($row['body']);
      unset($row['teaser']);
      unset($row['format']);
      unset($row['log']);

      //$this->databaseContents['node'][$k] = $row;
      $this->databaseContents['node'][$row['nid']] = $row;

      // Add the column field storage data.
      $table = 'content_type_' . $row['type'];
      foreach ($row['fields'] as $field_name => $value) {
        $this->databaseContents[$table][$k][$field_name . "_value"] = $value;
        $this->databaseContents[$table][$k]['vid'] = $row['vid'];
        $this->databaseContents[$table][$k]['nid'] = $row['nid'];
      }
      // Unset from results.
      unset($row['fields']);
      unset($this->expectedResults[$k]['fields']);
    }

    // Setup field tables.
    foreach ($this->fields as $field) {
      $cnf = $field['content_node_field'];
      $this->databaseContents['content_node_field'][] = $cnf;
      $this->databaseContents['content_node_field_instance'][] = $field['content_node_field_instance'];

      // If it's a multi-field then setup a new table.
      if ($cnf['multiple']) {
        foreach ($field['values'] as $value) {
          $this->databaseContents['content_' . $cnf['field_name']][] = $value;
        }
      }
    }

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public function testRetrieval() {
    // @todo: Fix this as per https://www.drupal.org/node/2299795
    $this->markTestIncomplete('FakeSelect does not support multiple source identifiers, can not test.');
  }

}

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\NodeRevision;

class TestNodeRevision extends NodeRevision {
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}
