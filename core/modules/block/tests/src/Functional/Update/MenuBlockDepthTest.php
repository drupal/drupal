<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Functional\Update;

use Drupal\block\Entity\Block;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update path for the `depth` setting of menu blocks.
 *
 * @group system
 */
final class MenuBlockDepthTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/add-menu-block-with-zero-depth.php',
    ];
  }

  /**
   * Tests that menu blocks with a `depth` setting of 0 are changed to NULL.
   */
  public function testUpdate(): void {
    $settings = Block::load('olivero_account_menu')?->get('settings');
    $this->assertIsArray($settings);
    $this->assertSame(0, $settings['depth']);

    $this->runUpdates();

    $settings = Block::load('olivero_account_menu')?->get('settings');
    $this->assertIsArray($settings);
    $this->assertNull($settings['depth']);
  }

}
