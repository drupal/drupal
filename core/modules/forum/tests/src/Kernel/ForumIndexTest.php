<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Defines a class for testing the forum_index table.
 *
 * @group forum
 */
final class ForumIndexTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'history',
    'taxonomy',
    'forum',
    'comment',
    'options',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('forum', ['forum_index']);
  }

  /**
   * Tests there's a primary key on the forum_index table.
   */
  public function testForumIndexIndex(): void {
    $schema = \Drupal::database()->schema();
    $this->assertTrue($schema->tableExists('forum_index'));
    $this->assertTrue($schema->indexExists('forum_index', 'PRIMARY'));
  }

}
