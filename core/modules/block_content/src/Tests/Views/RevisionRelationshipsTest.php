<?php

namespace Drupal\block_content\Tests\Views;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\block_content\Entity\BlockContent;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the integration of block_content_revision table of block_content module.
 *
 * @group block_content
 */
class RevisionRelationshipsTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block_content' , 'block_content_test_views'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_block_content_revision_id', 'test_block_content_revision_revision_id'];

  protected function setUp() {
    parent::setUp();
    BlockContentType::create([
      'id' => 'basic',
      'label' => 'basic',
      'revision' => TRUE,
    ]);
    ViewTestData::createTestViews(get_class($this), ['block_content_test_views']);
  }

  /**
   * Create a block_content with revision and rest result count for both views.
   */
  public function testBlockContentRevisionRelationship() {
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
    $view_id = Views::getView('test_block_content_revision_id');
    $this->executeView($view_id, [$block_content->id()]);
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
    $this->assertIdenticalResultset($view_id, $resultset_id, $column_map);

    // There should be only one row with active revision 2.
    $view_revision_id = Views::getView('test_block_content_revision_revision_id');
    $this->executeView($view_revision_id, [$block_content->id()]);
    $resultset_revision_id = [
      [
        'revision_id' => '2',
        'id_1' => '1',
        'block_content_field_data_block_content_field_revision_id' => '1',
      ],
    ];
    $this->assertIdenticalResultset($view_revision_id, $resultset_revision_id, $column_map);
  }

}
