<?php

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Entity\CommentType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests that comment fields cannot be added to entities with non-integer IDs.
 *
 * @group comment
 */
class CommentStringIdEntitiesTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'comment',
    'user',
    'field',
    'field_ui',
    'entity_test',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('comment');
    $this->installEntitySchema('entity_test_string_id');
    $this->installSchema('comment', ['comment_entity_statistics']);
    // Create the comment body field storage.
    $this->installConfig(['field']);
  }

  /**
   * Tests that comment fields cannot be added entities with non-integer IDs.
   */
  public function testCommentFieldNonStringId() {
    $this->expectException(\UnexpectedValueException::class);
    $bundle = CommentType::create([
      'id' => 'foo',
      'label' => 'foo',
      'description' => '',
      'target_entity_type_id' => 'entity_test_string_id',
    ]);
    $bundle->save();
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'foo',
      'entity_type' => 'entity_test_string_id',
      'settings' => [
        'comment_type' => 'entity_test_string_id',
      ],
      'type' => 'comment',
    ]);
    $field_storage->save();
  }

}
