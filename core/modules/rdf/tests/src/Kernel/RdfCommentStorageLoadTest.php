<?php

namespace Drupal\Tests\rdf\Kernel;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\Plugin\Field\FieldType\CreatedItem;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\comment\Entity\Comment;

/**
 * Tests rdf_comment_storage_load.
 *
 * @group rdf
 */
class RdfCommentStorageLoadTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'rdf'];

  /**
   * Tests rdf_comment_storage_load.
   */
  public function testRdfCommentStorageLoad() {
    $field_created_item = $this->prophesize(CreatedItem::class);
    $field_created_item->setValue([time()]);

    $field_list = $this->prophesize(FieldItemList::class);
    $field_list->reveal();
    $field_list->first()->willReturn($field_created_item->reveal());

    $comment = $this->prophesize(Comment::class);
    $comment->bundle()->willReturn('page');
    $comment->get('created')->willReturn($field_list);
    $comment->getFieldDefinitions()->willReturn(NULL);
    // Set commented entity and parent entity to NULL.
    $comment->getCommentedEntity()->willReturn(NULL);
    $comment->getParentComment()->willReturn(NULL);

    /** @var \Drupal\Core\Extension\ModuleHandler $module_handler */
    $module_handler = \Drupal::service('module_handler');
    $module_handler->invoke('rdf', 'comment_storage_load', [[$comment->reveal()]]);
  }

}
