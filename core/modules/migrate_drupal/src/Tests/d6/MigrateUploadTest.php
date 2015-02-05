<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUploadTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\node\Entity\Node;

/**
 * Migrate association data between nodes and files.
 *
 * @group migrate_drupal
 */
class MigrateUploadTest extends MigrateUploadBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $node_storage->resetCache(array(1, 2));
    $nodes = Node::loadMultiple(array(1, 2));
    $node = $nodes[1];
    $this->assertIdentical(count($node->upload), 1);
    $this->assertIdentical($node->upload[0]->target_id, '1');
    $this->assertIdentical($node->upload[0]->description, 'file 1-1-1');
    $this->assertIdentical($node->upload[0]->isDisplayed(), FALSE);

    $node = $nodes[2];
    $this->assertIdentical(count($node->upload), 2);
    $this->assertIdentical($node->upload[0]->target_id, '3');
    $this->assertIdentical($node->upload[0]->description, 'file 2-3-3');
    $this->assertIdentical($node->upload[0]->isDisplayed(), FALSE);
    $this->assertIdentical($node->upload[1]->target_id, '2');
    $this->assertIdentical($node->upload[1]->isDisplayed(), TRUE);
    $this->assertIdentical($node->upload[1]->description, 'file 2-3-2');
  }

}
