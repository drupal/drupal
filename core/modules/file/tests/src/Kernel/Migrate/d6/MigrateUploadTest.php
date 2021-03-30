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
  protected static $modules = [
    'language',
    'content_translation',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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

    $this->migrateContent(['translations']);
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
    $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->resetCache([1, 2, 12]);

    $nodes = Node::loadMultiple([1, 2, 12]);
    $node = $nodes[1];
    $this->assertEquals('en', $node->langcode->value);
    $this->assertCount(1, $node->upload);
    $this->assertSame('1', $node->upload[0]->target_id);
    $this->assertSame('file 1-1-1', $node->upload[0]->description);
    $this->assertFalse($node->upload[0]->isDisplayed());

    $node = $nodes[2];
    $this->assertEquals('en', $node->langcode->value);
    $this->assertCount(2, $node->upload);
    $this->assertSame('3', $node->upload[0]->target_id);
    $this->assertSame('file 2-3-3', $node->upload[0]->description);
    $this->assertFalse($node->upload[0]->isDisplayed());
    $this->assertSame('2', $node->upload[1]->target_id);
    $this->assertTrue($node->upload[1]->isDisplayed());
    $this->assertSame('file 2-3-2', $node->upload[1]->description);

    $node = $nodes[12];
    $this->assertEquals('zu', $node->langcode->value);
    $this->assertCount(1, $node->upload);
    $this->assertEquals('3', $node->upload[0]->target_id);
    $this->assertEquals('file 12-15-3', $node->upload[0]->description);
    $this->assertEquals(FALSE, $node->upload[0]->isDisplayed());
  }

}
