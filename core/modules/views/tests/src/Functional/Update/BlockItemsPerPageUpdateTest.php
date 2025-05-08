<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Update;

use Drupal\block\Entity\Block;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * @group Update
 * @covers views_post_update_block_items_per_page
 */
final class BlockItemsPerPageUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/views-block-items-per-page.php',
    ];
  }

  /**
   * Tests changing an `items_per_page` setting of `none` to NULL.
   */
  public function testUpdateItemsPerPage(): void {
    $settings = Block::load('olivero_who_s_online')?->get('settings');
    $this->assertIsArray($settings);
    $this->assertSame('none', $settings['items_per_page']);

    $this->runUpdates();

    $settings = Block::load('olivero_who_s_online')?->get('settings');
    $this->assertIsArray($settings);
    $this->assertNull($settings['items_per_page']);
  }

}
