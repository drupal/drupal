<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUploadTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;

/**
 * Test upload migration from Drupal 6 to Drupal 8.
 */
class MigrateUploadTest extends MigrateUploadBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate upload',
      'description'  => 'Migrate association data between nodes and files.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_upload');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Test upload migration from Drupal 6 to Drupal 8.
   */
  function testUpload() {
    $nodes = node_load_multiple(array(1, 2), TRUE);
    $node = $nodes[1];
    $this->assertEqual(count($node->upload), 1);
    $this->assertEqual($node->upload[0]->target_id, 1);
    $this->assertEqual($node->upload[0]->description, 'file 1-1-1');
    $this->assertEqual($node->upload[0]->isDisplayed(), FALSE);

    $node = $nodes[2];
    $this->assertEqual(count($node->upload), 2);
    $this->assertEqual($node->upload[0]->target_id, 3);
    $this->assertEqual($node->upload[0]->description, 'file 2-3-3');
    $this->assertEqual($node->upload[0]->isDisplayed(), FALSE);
    $this->assertEqual($node->upload[1]->target_id, 2);
    $this->assertEqual($node->upload[1]->isDisplayed(), TRUE);
    $this->assertEqual($node->upload[1]->description, 'file 2-3-2');
  }

}
