<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUploadBase.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Base class for file/upload migration tests.
 */
abstract class MigrateUploadBase extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  static $modules = array('file', 'node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create new file entities.
    for ($i = 1; $i <= 3; $i++) {
      $file = entity_create('file', array(
        'fid' => $i,
        'uid' => 1,
        'filename' => 'druplicon.txt',
        'uri' => "public://druplicon-$i.txt",
        'filemime' => 'text/plain',
        'created' => 1,
        'changed' => 1,
        'status' => FILE_STATUS_PERMANENT,
      ));
      $file->enforceIsNew();
      file_put_contents($file->getFileUri(), 'hello world');

      // Save it, inserting a new record.
      $file->save();
      $id_mappings['d6_file'][] = array(array($i), array($i));
    }

    // Add a node type.
    $node_type = entity_create('node_type', array('type' => 'story'));
    $node_type->save();

    // Add a file field.
    entity_create('field_storage_config', array(
      'field_name' => 'upload',
      'entity_type' => 'node',
      'type' => 'file',
      'cardinality' => -1,
      'settings' => array(
        'display_field' => TRUE,
      ),
    ))->save();
    entity_create('field_config', array(
      'field_name' => 'upload',
      'entity_type' => 'node',
      'bundle' => 'story',
    ))->save();
    $id_mappings['d6_node'] = array(
      array(array(1), array(1)),
      array(array(2), array(2)),
    );
    $this->prepareMigrations($id_mappings);
    $vids = array(1, 2, 3);
    for ($i = 1; $i <= 2; $i++) {
      $node = entity_create('node', array(
        'type' => 'story',
        'nid' => $i,
        'vid' => array_shift($vids),
      ));
      $node->enforceIsNew();
      $node->save();
      if ($i == 1) {
        $node->vid->value = array_shift($vids);
        $node->enforceIsNew(FALSE);
        $node->isDefaultRevision(FALSE);
        $node->save();
      }
    }
    $dumps = array(
      $this->getDumpDirectory() . '/Node.php',
      $this->getDumpDirectory() . '/NodeRevisions.php',
      $this->getDumpDirectory() . '/ContentTypeStory.php',
      $this->getDumpDirectory() . '/ContentTypeTestPlanet.php',
      $this->getDumpDirectory() . '/Upload.php',
    );
    $this->loadDumps($dumps);
  }

}
