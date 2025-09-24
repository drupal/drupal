<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional\Update;

use Drupal\block\Entity\Block;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore anotherblock
/**
 * Tests block_content_post_update_remove_block_content_status_info_keys.
 */
#[Group('block_content')]
#[RunTestsInSeparateProcesses]
class BlockContentStatusInfoUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests block_content_post_update_remove_block_content_status_info_keys.
   */
  public function testRunUpdates(): void {
    $this->assertArrayHasKey('info', Block::load('anotherblock')->get('settings'));
    $this->assertArrayHasKey('status', Block::load('anotherblock')->get('settings'));

    $this->runUpdates();

    $this->assertArrayNotHasKey('info', Block::load('anotherblock')->get('settings'));
    $this->assertArrayNotHasKey('status', Block::load('anotherblock')->get('settings'));
  }

}
