<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests file on node relationship handler.
 *
 * @group file
 */
class RelationshipNodeFileDataTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'file', 'file_test_views', 'node', 'text'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_file_to_node', 'test_node_to_file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installSchema('file', 'file_usage');
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installConfig(['node', 'field', 'file_test_views']);

    // Create the node file field and instance.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'node_file',
      'type' => 'file',
      'translatable' => '0',
    ])->save();
    FieldConfig::create([
      'label' => 'Node File',
      'description' => '',
      'field_name' => 'node_file',
      'entity_type' => 'node',
      'bundle' => 'file_test',
      'required' => 0,
    ])->save();

    ViewTestData::createTestViews(static::class, ['file_test_views']);
  }

  /**
   * Tests using the views file_to_node relationship.
   */
  public function testViewsHandlerRelationshipFileToNode(): void {
    $file1 = File::create([
      'filename' => 'image-test.jpg',
      'uri' => "public://image-test.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file1->enforceIsNew();
    file_put_contents($file1->getFileUri(), file_get_contents('core/tests/fixtures/files/image-1.png'));
    $file1->save();

    $file2 = File::create([
      'filename' => 'image-test-2.jpg',
      'uri' => "public://image-test-2.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file2->enforceIsNew();
    file_put_contents($file2->getFileUri(), file_get_contents('core/tests/fixtures/files/image-1.png'));
    $file2->save();

    $node1 = Node::create([
      'type' => 'file_test',
      'title' => $this->randomMachineName(8),
      'created' => 1,
      'changed' => 1,
      'status' => NodeInterface::PUBLISHED,
    ]);
    $node1->save();

    $node2 = Node::create([
      'type' => 'file_test',
      'title' => $this->randomMachineName(8),
      'created' => 1,
      'changed' => 1,
      'status' => NodeInterface::PUBLISHED,
      'node_file' => ['target_id' => $file2->id()],
    ]);
    $node2->save();

    $view = Views::getView('test_file_to_node');
    $this->executeView($view);
    // We should only see a single file, the one on the user account. The other
    // account's UUID, nor the other unlinked file, should appear in the
    // results.
    $expected_result = [
      [
        'fid' => $file2->id(),
        'nid' => $node2->id(),
      ],
    ];
    $column_map = ['fid' => 'fid', 'nid' => 'nid'];
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

  /**
   * Tests using the views node_to_file relationship.
   */
  public function testViewsHandlerRelationshipNodeToFile(): void {
    $file1 = File::create([
      'filename' => 'image-test.jpg',
      'uri' => "public://image-test.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file1->enforceIsNew();
    file_put_contents($file1->getFileUri(), file_get_contents('core/tests/fixtures/files/image-1.png'));
    $file1->save();

    $file2 = File::create([
      'filename' => 'image-test-2.jpg',
      'uri' => "public://image-test-2.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file2->enforceIsNew();
    file_put_contents($file2->getFileUri(), file_get_contents('core/tests/fixtures/files/image-1.png'));
    $file2->save();

    $node1 = Node::create([
      'type' => 'file_test',
      'title' => $this->randomMachineName(8),
      'created' => 1,
      'changed' => 1,
      'status' => NodeInterface::PUBLISHED,
    ]);
    $node1->save();

    $node2 = Node::create([
      'type' => 'file_test',
      'title' => $this->randomMachineName(8),
      'created' => 1,
      'changed' => 1,
      'status' => NodeInterface::PUBLISHED,
      'node_file' => ['target_id' => $file2->id()],
    ]);
    $node2->save();

    $view = Views::getView('test_node_to_file');
    $this->executeView($view);
    // We should only see a single file, the one on the user account. The other
    // account's UUID, nor the other unlinked file, should appear in the
    // results.
    $expected_result = [
      [
        'fid' => $file2->id(),
        'nid' => $node2->id(),
      ],
    ];
    $column_map = ['fid' => 'fid', 'nid' => 'nid'];
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

}
