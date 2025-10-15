<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the removal of the empty listing plugin.
 *
 * @see block_content_post_update_remove_block_content_listing_empty()
 */
#[Group('Update')]
#[RunTestsInSeparateProcesses]
class BlockContentRemoveListingEmptyUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests the upgrade path to remove the empty listing plugin.
   */
  public function testBlockContentPostUpdateRemoveBlockContentListingEmpty(): void {
    $view = View::load('block_content');
    $data = $view->toArray();
    // Plugin exists in the view before updates.
    $this->assertNotEmpty($data['display']['default']['display_options']['empty']['block_content_listing_empty']);

    $this->runUpdates();

    $view = View::load('block_content');
    $data = $view->toArray();
    // Plugin is removed from the view after updates.
    $this->assertArrayNotHasKey('block_content_listing_empty', $data['display']['default']['display_options']['empty']);
    // Other empty plugins still remain.
    $this->assertNotEmpty($data['display']['default']['display_options']['empty']);
  }

}
