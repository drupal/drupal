<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests access to block_content routes.
 */
#[Group('block_content')]
#[RunTestsInSeparateProcesses]
class BlockContentRouteAccessTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_content_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests access to block_content entity routes based on the reusable field.
   */
  public function testBlockContentReusableAccess(): void {
    $block = $this->createBlockContent();

    $this->assertTrue($block->isReusable());
    $this->assertTrue($block->toUrl()->access($this->adminUser));
    $this->assertTrue($block->toUrl('edit-form')->access($this->adminUser));
    $this->assertTrue($block->toUrl('delete-form')->access($this->adminUser));

    $block->setNonReusable()->save();
    \Drupal::entityTypeManager()->getAccessControlHandler('block_content')->resetCache();
    $this->assertFalse($block->toUrl()->access($this->adminUser));
    $this->assertFalse($block->toUrl('edit-form')->access($this->adminUser));
    $this->assertFalse($block->toUrl('delete-form')->access($this->adminUser));
  }

}
