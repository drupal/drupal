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
    // We can't reliably call ::indexExists for each database driver as sqlite
    // doesn't have named indexes for primary keys like mysql (PRIMARY) and
    // pgsql (pkey).
    $find_primary_key_columns = new \ReflectionMethod(get_class($schema), 'findPrimaryKeyColumns');
    $this->assertEquals(['nid', 'tid'], $find_primary_key_columns->invoke($schema, 'forum_index'));
  }

}
