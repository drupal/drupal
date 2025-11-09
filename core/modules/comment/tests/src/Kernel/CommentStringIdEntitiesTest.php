<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Entity\CommentType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that comment fields cannot be added to entities with non-integer IDs.
 */
#[Group('comment')]
#[RunTestsInSeparateProcesses]
class CommentStringIdEntitiesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'user',
    'field',
    'entity_test',
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
  public function testCommentFieldNonStringId(): void {
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
