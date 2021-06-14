<?php

namespace Drupal\Tests\block_content\Kernel\Views;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the integration of block_content_revision table.
 *
 * @group block_content
 */
class RevisionRelationshipsTest extends KernelTestBase {

  use ViewResultAssertionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'block_content_test_views',
    'system',
    'user',
    'views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = [
    'test_block_content_revision_id',
    'test_block_content_revision_revision_id',
  ];

  /**
   * Create a block_content with revision and rest result count for both views.
   */
  public function testBlockContentRevisionRelationship() {
    $this->installEntitySchema('block_content');
    ViewTestData::createTestViews(static::class, ['block_content_test_views']);

    BlockContentType::create([
      'id' => 'basic',
      'label' => 'basic',
      'revision' => TRUE,
    ]);
    $block_content = BlockContent::create([
      'info' => $this->randomMachineName(),
      'type' => 'basic',
      'langcode' => 'en',
    ]);
    $block_content->save();
    // Create revision of the block_content.
    $block_content_revision = clone $block_content;
    $block_content_revision->setNewRevision();
    $block_content_revision->save();
    $column_map = [
      'revision_id' => 'revision_id',
      'id_1' => 'id_1',
      'block_content_field_data_block_content_field_revision_id' => 'block_content_field_data_block_content_field_revision_id',
    ];

    // Here should be two rows.
    $view = Views::getView('test_block_content_revision_id');
    $view->preview(NULL, [$block_content->id()]);
    $resultset_id = [
      [
        'revision_id' => '1',
        'id_1' => '1',
        'block_content_field_data_block_content_field_revision_id' => '1',
      ],
      [
        'revision_id' => '2',
        'id_1' => '1',
        'block_content_field_data_block_content_field_revision_id' => '1',
      ],
    ];
    $this->assertIdenticalResultset($view, $resultset_id, $column_map);

    // There should be only one row with active revision 2.
    $view_revision = Views::getView('test_block_content_revision_revision_id');
    $view_revision->preview(NULL, [$block_content->id()]);
    $resultset_revision_id = [
      [
        'revision_id' => '2',
        'id_1' => '1',
        'block_content_field_data_block_content_field_revision_id' => '1',
      ],
    ];
    $this->assertIdenticalResultset($view_revision, $resultset_revision_id, $column_map);
  }

}
