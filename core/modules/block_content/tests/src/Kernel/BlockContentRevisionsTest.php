<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Kernel;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests revision based functions for Block Content.
 */
#[Group('block_content')]
#[RunTestsInSeparateProcesses]
class BlockContentRevisionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');
  }

  /**
   * Tests block content revision user id doesn't throw error with null field.
   */
  public function testNullRevisionUser(): void {
    BlockContentType::create([
      'id' => 'basic',
      'label' => 'A basic block type',
    ])->save();

    $block = BlockContent::create([
      'info' => 'Test',
      'type' => 'basic',
      'revision_user' => NULL,
    ]);
    $block->save();
    $this->assertNull($block->getRevisionUserId());
  }

}
