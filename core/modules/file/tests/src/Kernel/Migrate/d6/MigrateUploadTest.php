<?php

namespace Drupal\Tests\file\Kernel\Migrate\d6;

use Drupal\file\Entity\File;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\node\Entity\Node;

/**
 * Migrate association data between nodes and files.
 *
 * @group migrate_drupal_6
 */
class MigrateUploadTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('node', ['node_access']);

    $id_mappings = ['d6_file' => []];
    // Create new file entities.
    for ($i = 1; $i <= 3; $i++) {
      $file = File::create([
        'fid' => $i,
        'uid' => 1,
        'filename' => 'druplicon.txt',
        'uri' => "public://druplicon-$i.txt",
        'filemime' => 'text/plain',
        'created' => 1,
        'changed' => 1,
        'status' => FILE_STATUS_PERMANENT,
      ]);
      $file->enforceIsNew();
      file_put_contents($file->getFileUri(), 'hello world');

      // Save it, inserting a new record.
      $file->save();
      $id_mappings['d6_file'][] = [[$i], [$i]];
    }
    $this->prepareMigrations($id_mappings);

    $this->migrateContent();
    // Since we are only testing a subset of the file migration, do not check
    // that the full file migration has been run.
    $migration = $this->getMigration('d6_upload');
    $migration->set('requirements', []);
    $this->executeMigration($migration);
  }

  /**
   * Test upload migration from Drupal 6 to Drupal 8.
   */
  public function testUpload() {
    $this->container->get('entity.manager')
      ->getStorage('node')
      ->resetCache([1, 2]);

    $nodes = Node::loadMultiple([1, 2]);
    $node = $nodes[1];
    $this->assertIdentical(1, count($node->upload));
    $this->assertIdentical('1', $node->upload[0]->target_id);
    $this->assertIdentical('file 1-1-1', $node->upload[0]->description);
    $this->assertIdentical(FALSE, $node->upload[0]->isDisplayed());

    $node = $nodes[2];
    $this->assertIdentical(2, count($node->upload));
    $this->assertIdentical('3', $node->upload[0]->target_id);
    $this->assertIdentical('file 2-3-3', $node->upload[0]->description);
    $this->assertIdentical(FALSE, $node->upload[0]->isDisplayed());
    $this->assertIdentical('2', $node->upload[1]->target_id);
    $this->assertIdentical(TRUE, $node->upload[1]->isDisplayed());
    $this->assertIdentical('file 2-3-2', $node->upload[1]->description);
  }

}
