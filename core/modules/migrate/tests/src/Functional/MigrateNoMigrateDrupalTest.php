<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Execute migration.
 *
 * This is intentionally a Functional test instead of a Kernel test because
 * Kernel tests have proven to not catch all edge cases that are encountered
 * via a Functional test.
 *
 * @group migrate
 */
class MigrateNoMigrateDrupalTest extends BrowserTestBase {
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'migrate_no_migrate_drupal_test',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createContentType(['type' => 'no_migrate_drupal']);
  }

  /**
   * Tests execution of a migration.
   */
  public function testExecutionNoMigrateDrupal(): void {
    $this->drupalGet('/migrate_no_migrate_drupal_test/execute');
    $this->assertSession()->pageTextContains('Migration was successful.');
    $node_1 = Node::load(1);
    $node_2 = Node::load(2);
    $this->assertEquals('Node 1', $node_1->label());
    $this->assertEquals('Node 2', $node_2->label());
  }

}
