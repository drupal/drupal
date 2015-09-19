<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentItemTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Tests the new entity API for the comment field type.
 *
 * @group comment
 */
class CommentItemTest extends FieldUnitTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['comment', 'entity_test', 'user'];

  protected function setUp() {
    parent::setUp();
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['comment']);
  }

  /**
   * Tests using entity fields of the comment field type.
   */
  public function testCommentItem() {
    $this->addDefaultCommentField('entity_test', 'entity_test', 'comment');

    // Verify entity creation.
    $entity = entity_create('entity_test');
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = entity_load('entity_test', $id, TRUE);
    $this->assertTrue($entity->comment instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->comment[0] instanceof CommentItemInterface, 'Field item implements interface.');

    // Test sample item generation.
    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = entity_create('entity_test');
    $entity->comment->generateSampleItems();
    $this->entityValidateAndSave($entity);
    $this->assertTrue(in_array($entity->get('comment')->status, [
      CommentItemInterface::HIDDEN,
      CommentItemInterface::CLOSED,
      CommentItemInterface::OPEN,
    ]), 'Comment status value in defined range');

    $mainProperty = $entity->comment[0]->mainPropertyName();
    $this->assertEqual('status', $mainProperty);
  }

}
